<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Exception;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['person_id', 'email', 'password', 'panel_access', 'is_developer', 'last_seen_at', 'two_factor_enabled', 'two_factor_code', 'two_factor_expires_at', 'must_change_password'])]
#[Hidden(['password', 'remember_token', 'two_factor_code'])]
class User extends Authenticatable implements FilamentUser, HasTenants, HasDefaultTenant
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(UserTrustedDevice::class);
    }

    protected $attributes = [
        'panel_access' => true,
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        // Painel do desenvolvedor: acesso restrito a contas com flag is_developer
        if ($panel->getId() === 'developer') {
            return (bool) $this->is_developer;
        }

        // Painel SaaS Master: Acesso restrito exclusivamente ao proprietário / Administrador Master do Aurosoft
        if ($panel->getId() === 'saas') {
            if ($this->is_developer) {
                return true;
            }

            $host = strtolower(trim(explode(':', request()->getHost())[0]));
            if (str_starts_with($host, 'master.') || $host === 'master.aurosoft.com.br' || $host === 'localhost' || $host === '127.0.0.1') {
                return (bool) ($this->is_master_admin ?? false) || $this->hasRole('master_admin') || $this->hasRole('super_admin');
            }

            return false;
        }

        // Painel Admin da Escola: NUNCA pode ser acessado a partir do domínio master
        if ($panel->getId() === 'admin') {
            $host = strtolower(trim(explode(':', request()->getHost())[0]));
            if (str_starts_with($host, 'master.') || $host === 'master.aurosoft.com.br') {
                return false;
            }
        }

        // Se for a conta oculta de desenvolvedor, bloqueia acesso aos painéis de dados do cliente (admin, estúdio, quiosque)
        if ($this->is_developer) {
            return false;
        }

        // Se a sessão atual foi iniciada especificamente com credencial de contrato de aluno,
        // bloqueia o acesso ao painel administrativo da escola
        if (session('portal_auth_type') === 'student_contract') {
            return false;
        }

        // Colaboradores sem papel definido no sistema não podem acessar nenhum painel (Aurosoft, Estúdio, etc.)
        if ($this->isCollaborator() && ! $this->hasDefinedRole()) {
            return false;
        }

        // Restringe o painel dedicado do Estúdio LMS com base nas permissões concedidas pelo cliente
        if ($panel->getId() === 'studio') {
            return $this->can('View:LmsStudio')
                || $this->can('page_LmsStudio')
                || $this->can('view_lms_studio')
                || $this->can('View:LmsLesson');
        }

        // Apenas usuários explicitamente com painel desativado são bloqueados
        if ($this->panel_access === false) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se o usuário possui permissão para gerenciar a integração de alunos do LMS.
     * Concede automaticamente as permissões interligadas do fluxo de matrícula (Pessoas e Contratos).
     */
    public function canManageLmsOnboarding(): bool
    {
        return $this->can('View:OuroModernoLmsStudents')
            || $this->can('View:OuroModernoEadStudents')
            || $this->can('Sync:OuroModernoLmsStudents')
            || (method_exists($this, 'hasRole') && $this->hasRole('super_admin'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if (Auth::check() && $user->id === Auth::id()) {
                throw new Exception('Você não pode excluir sua própria conta.');
            }
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class)->withoutGlobalScopes();
    }

    public function scopeWithoutTechnicalAdmin(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q): void {
                $q->whereNull('is_developer')
                    ->orWhere('is_developer', false);
            })
            ->whereRaw('LOWER(email) != ?', ['admin@admin.com'])
            ->whereDoesntHave('person', fn (Builder $pq) => $pq
                ->withoutGlobalScopes()
                ->whereRaw('LOWER(TRIM(name)) = ?', ['admin'])
                ->whereDoesntHave('contracts')
                ->whereDoesntHave('collaborators')
                ->whereDoesntHave('responsibles')
            );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->person?->name,
            set: fn (?string $value) => null,
        );
    }

    public function getFilamentName(): string
    {
        return $this->person?->name ?? $this->email;
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class, 'person_id', 'person_id');
    }

    public function isCollaborator(): bool
    {
        if ($this->person_id) {
            return Collaborator::query()
                ->where('person_id', $this->person_id)
                ->exists();
        }

        return false;
    }

    public function hasDefinedRole(): bool
    {
        return $this->roles()->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function touchOnlinePresence(): void
    {
        Cache::put("user_online_{$this->id}", now()->timestamp, now()->addSeconds(45));
        Cache::forget("user_logged_out_{$this->id}");

        if (Schema::hasColumn('users', 'last_seen_at')) {
            if (! $this->last_seen_at || $this->last_seen_at->lt(now()->subSeconds(20))) {
                $this->forceFill(['last_seen_at' => now()])->saveQuietly();
            }
        }
    }

    public function markOffline(): void
    {
        Cache::forget("user_online_{$this->id}");
        Cache::put("user_logged_out_{$this->id}", true, now()->addHours(12));

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $this->id)
                ->delete();
        }
    }

    public function isOnline(): bool
    {
        if (Cache::has("user_logged_out_{$this->id}")) {
            return false;
        }

        if (Cache::has("user_online_{$this->id}")) {
            return true;
        }

        if (Schema::hasTable('sessions')) {
            $hasRecentSession = DB::table('sessions')
                ->where('user_id', $this->id)
                ->where('last_activity', '>=', now()->subSeconds(45)->timestamp)
                ->exists();

            if ($hasRecentSession) {
                return true;
            }
        }

        return false;
    }

    public function lastSeenLabel(): string
    {
        if ($this->isOnline()) {
            return 'Online agora';
        }

        if ($this->last_seen_at) {
            return 'Visto '.$this->last_seen_at->diffForHumans();
        }

        return 'Offline';
    }

    public function studentContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'student_id', 'person_id');
    }

    public function activeStudentContracts(): HasMany
    {
        return $this->studentContracts()->where('status', 'active');
    }

    public function hasActiveStudentContract(): bool
    {
        return $this->activeStudentContracts()->exists();
    }

    public function isStaff(): bool
    {
        // Colaborador sem papel definido não possui privilégios de staff
        if ($this->isCollaborator() && ! $this->hasDefinedRole()) {
            return false;
        }

        if ($this->hasDefinedRole()) {
            return true;
        }

        return false;
    }

    public function isEducator(): bool
    {
        // Colaborador sem papel definido não possui privilégios de educador
        if ($this->isCollaborator() && ! $this->hasDefinedRole()) {
            return false;
        }

        if ($this->person_id) {
            return Collaborator::query()
                ->where('person_id', $this->person_id)
                ->where('active', true)
                ->where('is_educator', true)
                ->exists() && $this->hasDefinedRole();
        }

        return false;
    }

    public function assignedTaskCards(): BelongsToMany
    {
        return $this->belongsToMany(TaskCard::class, 'task_card_user');
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class);
    }

    public function getTenants(Panel $panel): array|Collection
    {
        if ($this->hasRole('super_admin')) {
            return School::query()->withoutGlobalScopes()->get();
        }

        $schools = $this->schools()->withoutGlobalScopes()->get();
        if ($schools->isEmpty()) {
            return School::query()->withoutGlobalScopes()->get();
        }

        return $schools;
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        $matriz = School::getHeadquarters();
        if ($matriz && $this->canAccessTenant($matriz)) {
            return $matriz;
        }

        return $this->getTenants($panel)->first();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        if ($this->schools()->count() === 0) {
            return true;
        }

        return $this->schools()->where('schools.id', $tenant->getKey())->exists();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'panel_access' => 'boolean',
            'is_developer' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }
}
