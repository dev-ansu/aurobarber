<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DataMaskingService
{
    /**
     * Verifica se o usuário atual tem permissão para visualizar dados confidenciais/LGPD.
     */
    public static function canViewSensitiveData(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        // Desenvolvedores e Administradores têm permissão irrestrita
        if ($user->is_developer || $user->hasRole('super_admin') || $user->hasRole('master_admin')) {
            return true;
        }

        return $user->can('ViewSensitiveData:Person') || $user->can('view_sensitive_data');
    }

    /**
     * Permissão Granular: Visualizar CPFs e Documentos Oficiais (RG/CNH).
     */
    public static function canViewCpf(?User $user = null): bool
    {
        $user ??= Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->is_developer || $user->hasRole('super_admin') || $user->hasRole('master_admin')) {
            return true;
        }

        return $user->can('ViewSensitive:Cpf') || $user->can('ViewSensitiveData:Person') || $user->can('view_sensitive_data');
    }

    /**
     * Permissão Granular: Visualizar Telefones e WhatsApp.
     */
    public static function canViewPhone(?User $user = null): bool
    {
        $user ??= Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->is_developer || $user->hasRole('super_admin') || $user->hasRole('master_admin')) {
            return true;
        }

        return $user->can('ViewSensitive:Phone') || $user->can('ViewSensitiveData:Person') || $user->can('view_sensitive_data');
    }

    /**
     * Permissão Granular: Visualizar E-mails.
     */
    public static function canViewEmail(?User $user = null): bool
    {
        $user ??= Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->is_developer || $user->hasRole('super_admin') || $user->hasRole('master_admin')) {
            return true;
        }

        return $user->can('ViewSensitive:Email') || $user->can('ViewSensitiveData:Person') || $user->can('view_sensitive_data');
    }

    /**
     * Permissão Granular: Visualizar Chaves Pix e Dados Bancários.
     */
    public static function canViewFinancial(?User $user = null): bool
    {
        $user ??= Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->is_developer || $user->hasRole('super_admin') || $user->hasRole('master_admin')) {
            return true;
        }

        return $user->can('ViewSensitive:Financial') || $user->can('ViewSensitiveData:Person') || $user->can('view_sensitive_data');
    }

    /**
     * Verifica permissão granular por tipo de dado.
     */
    public static function canViewType(string $type, ?User $user = null): bool
    {
        return match (strtolower($type)) {
            'cpf', 'rg', 'cnh', 'document', 'documents' => self::canViewCpf($user),
            'phone', 'whatsapp', 'cellphone', 'telefone', 'contato' => self::canViewPhone($user),
            'email', 'e-mail' => self::canViewEmail($user),
            'financial', 'pix', 'bank' => self::canViewFinancial($user),
            default => self::canViewSensitiveData($user),
        };
    }

    /**
     * Mascara um CPF mantendo apenas partes centrais ou extremas.
     * Exemplo: 123.456.789-00 vira ***.456.789-**
     */
    public static function maskCpf(?string $cpf): string
    {
        if (blank($cpf)) {
            return '—';
        }

        $clean = preg_replace('/\D/', '', (string) $cpf);

        if (strlen($clean) === 11) {
            return '***.' . substr($clean, 3, 3) . '.' . substr($clean, 6, 3) . '-**';
        }

        if (strlen($clean) > 4) {
            return substr($clean, 0, 2) . str_repeat('*', max(0, strlen($clean) - 4)) . substr($clean, -2);
        }

        return '***';
    }

    /**
     * Mascara um e-mail preservando os 2 primeiros caracteres e o domínio.
     * Exemplo: joao.silva@gmail.com vira jo***a@gmail.com
     */
    public static function maskEmail(?string $email): string
    {
        if (blank($email)) {
            return '—';
        }

        $parts = explode('@', trim((string) $email));
        if (count($parts) !== 2) {
            return '***@***';
        }

        [$username, $domain] = $parts;
        $len = strlen($username);

        if ($len <= 2) {
            $maskedUsername = substr($username, 0, 1) . '***';
        } else {
            $maskedUsername = substr($username, 0, 2) . '***' . substr($username, -1);
        }

        return $maskedUsername . '@' . $domain;
    }

    /**
     * Mascara um número de telefone ou WhatsApp.
     * Exemplo: (11) 98765-4321 vira (11) 9****-4321
     */
    public static function maskPhone(?string $phone): string
    {
        if (blank($phone)) {
            return '—';
        }

        $clean = preg_replace('/\D/', '', (string) $phone);

        if (strlen($clean) === 11) {
            return '(' . substr($clean, 0, 2) . ') ' . substr($clean, 2, 1) . '****-' . substr($clean, -4);
        }

        if (strlen($clean) === 10) {
            return '(' . substr($clean, 0, 2) . ') ****-' . substr($clean, -4);
        }

        if (strlen($clean) > 4) {
            return substr($clean, 0, 2) . '****' . substr($clean, -2);
        }

        return '****';
    }

    /**
     * Mascara documentos genéricos (RG, CNH, Passaporte, etc.).
     */
    public static function maskDocument(?string $doc, ?string $type = null): string
    {
        if (blank($doc)) {
            return '—';
        }

        $type = strtolower((string) $type);

        if ($type === 'cpf') {
            return self::maskCpf($doc);
        }

        $clean = preg_replace('/\D/', '', (string) $doc);

        if (strlen($clean) >= 6) {
            return substr($clean, 0, 2) . '.***.***-' . substr($clean, -1);
        }

        return '***.***';
    }

    /**
     * Mascara uma chave Pix identificando o tipo (CPF, email, telefone ou aleatória).
     */
    public static function maskPix(?string $pix): string
    {
        if (blank($pix)) {
            return '—';
        }

        $trimmed = trim((string) $pix);

        if (str_contains($trimmed, '@')) {
            return self::maskEmail($trimmed);
        }

        $digits = preg_replace('/\D/', '', $trimmed);
        if (strlen($digits) === 11 && ! str_starts_with($trimmed, '+')) {
            return self::maskCpf($digits);
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 13) {
            return self::maskPhone($digits);
        }

        if (strlen($trimmed) > 8) {
            return substr($trimmed, 0, 4) . '****' . substr($trimmed, -4);
        }

        return '****';
    }

    /**
     * Registra o evento de visualização de dado sensível no log de auditoria.
     */
    public static function logSensitiveDataView(Model $record, string $fieldName, ?string $reason = null): void
    {
        $userId = Auth::id();
        if (! $userId) {
            return;
        }

        $schoolId = null;
        if (class_exists(Filament::class) && Filament::getTenant()) {
            $schoolId = Filament::getTenant()->id;
        } elseif (isset($record->school_id)) {
            $schoolId = $record->school_id;
        }

        $contractId = null;
        if ($record instanceof Contract) {
            $contractId = $record->id;
        } elseif (isset($record->contract_id)) {
            $contractId = $record->contract_id;
        }

        $userName = Auth::user()?->person?->name ?? (Auth::user()?->email ?? 'Usuário');

        try {
            $data = [
                'contract_id' => $contractId,
                'user_id' => $userId,
                'auditable_type' => $record::class,
                'auditable_id' => $record->getKey(),
                'event' => 'view_sensitive_data',
                'changes' => [
                    'field' => $fieldName,
                    'reason' => $reason ?? 'Consulta pontual de dado sensível (LGPD)',
                    'operator' => $userName,
                    'viewed_at' => now()->toIso8601String(),
                ],
            ];

            if ($schoolId && Schema::hasColumn('audit_logs', 'school_id')) {
                $data['school_id'] = $schoolId;
            }

            AuditLog::create($data);
        } catch (\Throwable) {
            // Falha silenciosa para não quebrar a experiência do usuário caso o log falhe
        }
    }

    /**
     * Cria uma Action reutilizável para Filament Tables/Infolists que revela o dado completo em modal com auditoria.
     */
    public static function makeRevealModalAction(
        string $name,
        string $fieldLabel,
        callable $getRawValueCallback,
        ?string $icon = 'heroicon-m-eye',
        ?string $permissionType = null
    ): Action {
        return Action::make($name)
            ->label('Revelar ' . $fieldLabel)
            ->tooltip('Visualizar dado completo (Acesso Auditado sob LGPD)')
            ->icon($icon)
            ->color('gray')
            ->visible(fn (): bool => self::canViewType($permissionType ?? $name))
            ->requiresConfirmation()
            ->modalHeading("Consulta de {$fieldLabel} (LGPD)")
            ->modalDescription("A visualização do dado completo fica registrada no log de auditoria da unidade com seu nome e horário.")
            ->modalSubmitActionLabel('Confirmar e Visualizar')
            ->form(function (Model $record) use ($fieldLabel, $getRawValueCallback): array {
                $rawValue = (string) $getRawValueCallback($record);

                return [
                    TextInput::make('revealed_value')
                        ->label("{$fieldLabel} Completo")
                        ->default($rawValue)
                        ->readOnly()
                        ->copyable()
                        ->helperText('Clique no ícone ao lado para copiar para a área de transferência.'),
                ];
            })
            ->action(function (Model $record) use ($fieldLabel): void {
                self::logSensitiveDataView($record, $fieldLabel, "Consulta autorizada de {$fieldLabel}");

                Notification::make()
                    ->success()
                    ->title("{$fieldLabel} Visualizado")
                    ->body('A consulta foi registrada no histórico de auditoria.')
                    ->send();
            });
    }
}
