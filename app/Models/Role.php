<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'description',
    ];

    /**
     * Retorna o nome amigável para leigos do cargo.
     */
    public function getFriendlyName(): string
    {
        return match ($this->name) {
            'barbershop_owner' => 'Proprietário (Dono da Barbearia)',
            'barbershop_manager' => 'Gerente da Barbearia',
            'receptionist' => 'Recepcionista / Atendente',
            'barber' => 'Barbeiro / Profissional Parceiro',
            'super_admin' => 'Super Administrador (SaaS Master)',
            'panel_user' => 'Usuário Básico do Painel',
            default => Str::headline($this->name),
        };
    }

    /**
     * Retorna a descrição compreensível para leigos da finalidade do cargo.
     */
    public function getDescriptionForHumans(): string
    {
        if (filled($this->description)) {
            return $this->description;
        }

        return match ($this->name) {
            'barbershop_owner' => 'Acesso total e irrestrito. Gerencia financeiro da barbearia (faturamento e despesas), relatórios de lucro, fechamento de comissões da equipe, catálogo de serviços, controle de estoque e configurações do estabelecimento.',
            'barbershop_manager' => 'Gestão operacional do dia a dia. Controla a grade da agenda, abre e fecha o caixa diário, dá baixa em comandas, cadastra clientes e repõe produtos. Não tem acesso a exclusão irreversível de dados ou configurações confidenciais do dono.',
            'receptionist' => 'Operação de balcão e atendimento ao cliente. Realiza agendamentos de clientes, encaixes na agenda dos profissionais, cadastro de fichas de clientes e recebimento de pagamentos no caixa da recepção.',
            'barber' => 'Acesso restrito ao profissional. Visualiza exclusivamente sua própria agenda de cortes e horários marcados, lança produtos vendidos durante o atendimento e consulta o extrato das suas comissões de serviços e vendas.',
            'super_admin' => 'Administração técnica global. Acesso irrestrito a toda a plataforma Aurobarber, múltiplos estabelecimentos, suporte técnico e manutenções do sistema.',
            'panel_user' => 'Acesso básico de leitura para novos colaboradores ou aprendizes em fase de treinamento.',
            default => 'Cargo personalizado com permissões customizadas definidas pela barbearia.',
        };
    }

    /**
     * Badge do nível de acesso do cargo.
     */
    public function getAccessLevelBadge(): string
    {
        return match ($this->name) {
            'barbershop_owner' => 'Acesso Total',
            'barbershop_manager' => 'Gestão Operacional',
            'receptionist' => 'Balcão & Caixa',
            'barber' => 'Atendimento & Comissões',
            'super_admin' => 'Infraestrutura / TI',
            'panel_user' => 'Consulta Básica',
            default => 'Personalizado',
        };
    }

    /**
     * Cor do badge correspondente ao cargo.
     */
    public function getRoleColor(): string
    {
        return match ($this->name) {
            'barbershop_owner' => 'danger',
            'barbershop_manager' => 'warning',
            'receptionist' => 'info',
            'barber' => 'success',
            'super_admin' => 'gray',
            'panel_user' => 'secondary',
            default => 'primary',
        };
    }
}
