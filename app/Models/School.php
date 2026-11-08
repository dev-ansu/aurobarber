<?php

namespace App\Models;

use App\Actions\FillAddressFromZipCode;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model implements HasName, HasAvatar
{
    public $incrementing = false;

    public function getFilamentName(): string
    {
        return $this->trade_name ?: ($this->legal_name ?: "Escola #{$this->id}");
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->logo_url;
    }

    protected $fillable = [
        'legal_name',
        'trade_name',
        'is_headquarters',
        'inherit_financial_policy',
        'inherit_pix_settings',
        'inherit_ouro_moderno_ead',
        'logo_path',
        'brand_color',
        'cnpj',
        'state_registration',
        'municipal_registration',
        'email',
        'phone',
        'whatsapp',
        'website',
        'instagram',
        'facebook',
        'hero_headline',
        'hero_subheadline',
        'custom_students_count_text',
        'show_network_units',
        'showcase_enabled',
        'show_satisfaction_rating',
        'custom_satisfaction_score',
        'allowed_ips',
        'enforce_school_ip_for_lms',
        'enforce_school_ip_for_attendance',
        'postal_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'pix_enabled',
        'pix_key',
        'pix_key_type',
        'pix_merchant_name',
        'pix_merchant_city',
        'pix_instructions',
        'late_fine_percentage',
        'daily_interest_percentage',
        'grace_days',
        'punctuality_discount_enabled',
        'punctuality_discount_type',
        'punctuality_discount_value',
        'punctuality_discount_days_tolerance',
        'ouro_moderno_ead_enabled',
        'ouro_moderno_ead_api_key',
        'ouro_moderno_ead_unidade_id',
        'ouro_moderno_ead_unidade_nome',
        'google_drive_backup_enabled',
        'google_drive_client_id',
        'google_drive_client_secret',
        'google_drive_refresh_token',
        'google_drive_folder_id',
        'google_drive_last_backup_at',
        'google_drive_last_backup_status',
    ];

    protected $casts = [
        'is_headquarters' => 'boolean',
        'inherit_financial_policy' => 'boolean',
        'inherit_pix_settings' => 'boolean',
        'inherit_ouro_moderno_ead' => 'boolean',
        'pix_enabled' => 'boolean',
        'ouro_moderno_ead_enabled' => 'boolean',
        'google_drive_backup_enabled' => 'boolean',
        'google_drive_last_backup_at' => 'datetime',
        'punctuality_discount_enabled' => 'boolean',
        'late_fine_percentage' => 'float',
        'daily_interest_percentage' => 'float',
        'grace_days' => 'integer',
        'punctuality_discount_value' => 'float',
        'punctuality_discount_days_tolerance' => 'integer',
        'show_network_units' => 'boolean',
        'showcase_enabled' => 'boolean',
        'show_satisfaction_rating' => 'boolean',
        'enforce_school_ip_for_lms' => 'boolean',
        'enforce_school_ip_for_attendance' => 'boolean',
    ];

    public function getCleanWhatsappAttribute(): ?string
    {
        $raw = $this->whatsapp ?: $this->phone;
        if (! $raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            return '55' . $digits;
        }

        return $digits;
    }

    public function isClientIpAllowed(?string $clientIp = null): bool
    {
        if (blank($this->allowed_ips)) {
            return true;
        }

        $ip = $clientIp ?? request()->ip();
        if (! $ip) {
            return true;
        }

        // Permite testes locais
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        $allowedList = array_filter(array_map('trim', explode(',', str_replace(["\r\n", "\n", ";"], ',', (string) $this->allowed_ips))));
        if (empty($allowedList)) {
            return true;
        }

        return in_array($ip, $allowedList, true);
    }

    public function getActiveStudentsCountAttribute(): int
    {
        try {
            return \App\Models\Contract::where('school_id', $this->id)
                ->whereIn('status', ['signed', 'active', 'in_progress'])
                ->distinct('person_id')
                ->count('person_id');
        } catch (\Throwable) {
            return 0;
        }
    }

    public function getSatisfactionScoreAttribute(): string
    {
        if (filled($this->custom_satisfaction_score)) {
            return (string) $this->custom_satisfaction_score;
        }

        try {
            $lmsRating = \App\Models\LmsStudentLessonProgress::whereNotNull('student_rating')
                ->whereHas('contract', fn ($q) => $q->withoutGlobalScopes()->where('school_id', $this->id))
                ->avg('student_rating');

            $surveyRating = \App\Models\SatisfactionSurveyAnswer::whereNotNull('rating_value')
                ->whereHas('question', fn ($q) => $q->where('type', 'rating_5'))
                ->whereHas('response.contract', fn ($q) => $q->withoutGlobalScopes()->where('school_id', $this->id))
                ->avg('rating_value');

            $ratings = array_filter([$lmsRating, $surveyRating], fn ($v) => $v !== null && $v > 0);

            if (count($ratings) > 0) {
                return number_format(array_sum($ratings) / count($ratings), 1, '.', '') . ' / 5.0';
            }
        } catch (\Throwable $e) {
            // fallback silencioso
        }

        return '4.9 / 5.0';
    }

    protected static function booted(): void
    {
        static::creating(function (School $school): void {
            if (empty($school->id)) {
                $school->id = ((int) self::query()->max('id')) + 1;
            }

            // Herda configuração de backup da Matriz / rede se não informada
            if (empty($school->google_drive_client_id)) {
                $matriz = self::getHeadquarters() ?? self::query()->whereNotNull('google_drive_client_id')->orderBy('id')->first();
                if ($matriz) {
                    $school->google_drive_backup_enabled ??= $matriz->google_drive_backup_enabled;
                    $school->google_drive_client_id ??= $matriz->google_drive_client_id;
                    $school->google_drive_client_secret ??= $matriz->google_drive_client_secret;
                    $school->google_drive_refresh_token ??= $matriz->google_drive_refresh_token;
                    $school->google_drive_folder_id ??= $matriz->google_drive_folder_id;
                    $school->google_drive_last_backup_at ??= $matriz->google_drive_last_backup_at;
                    $school->google_drive_last_backup_status ??= $matriz->google_drive_last_backup_status;
                }
            }
        });

        static::saving(function (School $school): void {
            if ($school->is_headquarters) {
                // Se esta unidade foi marcada como Matriz, desmarca todas as outras da rede
                static::where('id', '!=', $school->id ?? 0)
                    ->where('is_headquarters', true)
                    ->update(['is_headquarters' => false]);
            }

            if (empty($school->city) && filled($school->postal_code)) {
                try {
                    $address = app(FillAddressFromZipCode::class)->execute($school->postal_code);
                    if ($address) {
                        $school->city ??= $address['city'];
                        $school->state ??= $address['state'];
                        $school->street ??= $address['street'];
                        $school->neighborhood ??= $address['neighborhood'];
                    }
                } catch (\Throwable) {
                    // Silently ignore if offline or invalid CEP
                }
            }
        });

        static::updated(function (self $school): void {
            if ($school->wasChanged('logo_path') && $oldPath = $school->getOriginal('logo_path')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
        });

        static::deleted(function (self $school): void {
            if ($school->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($school->logo_path);
            }
        });
    }

    public static function getHeadquarters(): ?self
    {
        return static::query()->where('is_headquarters', true)->first()
            ?? static::query()->orderBy('id')->first();
    }

    public function partners(): HasMany
    {
        return $this->hasMany(SchoolPartner::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    protected function ouroModernoEadApiKey(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                try {
                    return \Illuminate\Support\Facades\Crypt::decryptString($value);
                } catch (\Throwable) {
                    return $value;
                }
            },
            set: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                return \Illuminate\Support\Facades\Crypt::encryptString(trim($value));
            }
        );
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'school_person');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function commercialLeads(): HasMany
    {
        return $this->hasMany(CommercialLead::class);
    }

    public function commercialVisits(): HasMany
    {
        return $this->hasMany(CommercialVisit::class);
    }

    public function studentSchedules(): HasMany
    {
        return $this->hasMany(StudentSchedule::class);
    }

    public function studentAttendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function taskBoards(): HasMany
    {
        return $this->hasMany(TaskBoard::class);
    }

    public function schoolAppointments(): HasMany
    {
        return $this->hasMany(SchoolAppointment::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function schoolGoals(): HasMany
    {
        return $this->hasMany(SchoolGoal::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($this->logo_path, ['http://', 'https://', '/'])) {
            return $this->logo_path;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path);
    }

    public function resolveFinancialPolicy(): array
    {
        if ($this->inherit_financial_policy && ! $this->is_headquarters) {
            $headquarters = static::getHeadquarters();
            if ($headquarters && $headquarters->id !== $this->id) {
                $hqPolicy = $headquarters->resolveFinancialPolicy();
                $hqPolicy['is_inherited'] = true;

                return $hqPolicy;
            }
        }

        return [
            'late_fine_percentage' => (float) ($this->late_fine_percentage ?? 2.00),
            'daily_interest_percentage' => (float) ($this->daily_interest_percentage ?? 0.0333),
            'grace_days' => (int) ($this->grace_days ?? 0),
            'punctuality_discount_enabled' => (bool) ($this->punctuality_discount_enabled ?? false),
            'punctuality_discount_type' => $this->punctuality_discount_type ?? 'fixed',
            'punctuality_discount_value' => (float) ($this->punctuality_discount_value ?? 0.00),
            'punctuality_discount_days_tolerance' => (int) ($this->punctuality_discount_days_tolerance ?? 0),
            'is_inherited' => (bool) ($this->inherit_financial_policy && ! $this->is_headquarters),
        ];
    }

    public function resolvePixSettings(): array
    {
        if ($this->inherit_pix_settings && ! $this->is_headquarters) {
            $headquarters = static::getHeadquarters();
            if ($headquarters && $headquarters->id !== $this->id) {
                $hqPix = $headquarters->resolvePixSettings();
                $hqPix['is_inherited'] = true;

                return $hqPix;
            }
        }

        return [
            'pix_enabled' => (bool) ($this->pix_enabled ?? true),
            'pix_key' => $this->pix_key,
            'pix_key_type' => $this->pix_key_type ?? 'cnpj',
            'pix_merchant_name' => $this->pix_merchant_name ?: ($this->trade_name ?: ($this->legal_name ?: '')),
            'pix_merchant_city' => $this->pix_merchant_city ?: ($this->city ?: ''),
            'pix_instructions' => $this->pix_instructions,
            'is_inherited' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toContractSnapshot(): array
    {
        $pix = $this->resolvePixSettings();

        return [
            'legal_name' => $this->legal_name,
            'trade_name' => $this->trade_name,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path) : null,
            'cnpj' => $this->cnpj,
            'state_registration' => $this->state_registration,
            'municipal_registration' => $this->municipal_registration,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'postal_code' => $this->postal_code,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'pix_enabled' => (bool) ($pix['pix_enabled'] ?? true),
            'pix_key' => $pix['pix_key'] ?? null,
            'pix_key_type' => $pix['pix_key_type'] ?? null,
            'pix_merchant_name' => $pix['pix_merchant_name'] ?? null,
            'pix_merchant_city' => $pix['pix_merchant_city'] ?? null,
            'pix_instructions' => $pix['pix_instructions'] ?? null,
            'partners' => $this->partners()
                ->orderBy('id')
                ->get(['name', 'cpf', 'role', 'ownership_percentage', 'legal_representative'])
                ->toArray(),
        ];
    }

    public static function getActiveSchool(): ?self
    {
        if (class_exists(\Filament\Facades\Filament::class)) {
            try {
                $tenant = \Filament\Facades\Filament::getTenant();
                if ($tenant instanceof self) {
                    return $tenant;
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        $kioskCookieId = request()->cookie('kiosk_school_id') ?? session('kiosk_school_id');
        if (filled($kioskCookieId) && is_numeric($kioskCookieId)) {
            $school = static::query()->find((int) $kioskCookieId);
            if ($school) {
                return $school;
            }
        }

        $portalSchoolId = session('portal_school_id');
        if (filled($portalSchoolId) && is_numeric($portalSchoolId)) {
            $school = static::query()->find((int) $portalSchoolId);
            if ($school) {
                return $school;
            }
        }

        $user = auth()->user();
        if ($user && method_exists($user, 'schools')) {
            $userSchool = $user->schools()->first();
            if ($userSchool instanceof self) {
                return $userSchool;
            }
        }

        return static::query()->where('is_headquarters', true)->first()
            ?? static::query()->first();
    }

    public static function getSchoolName(?self $school = null): string
    {
        $school ??= static::getActiveSchool() ?? static::query()->first();

        return ($school?->trade_name ?: $school?->legal_name) ?: 'Aurosoft';
    }
}
