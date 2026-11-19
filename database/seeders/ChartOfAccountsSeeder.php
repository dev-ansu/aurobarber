<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FinancialType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(?int $barbershopId = null): void
    {
        $accounts = [
            // 1. RECEITAS OPERACIONAIS
            [
                'code' => '1',
                'name' => 'RECEITAS OPERACIONAIS',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => true,
                'parent_code' => null,
            ],
            [
                'code' => '1.1',
                'name' => 'Receitas de Serviços de Barbearia',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => true,
                'parent_code' => '1',
            ],
            [
                'code' => '1.1.1',
                'name' => 'Cortes e Acabamentos',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.1',
            ],
            [
                'code' => '1.1.2',
                'name' => 'Barba e Barboterapia',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.1',
            ],
            [
                'code' => '1.1.3',
                'name' => 'Química, Coloração e Luzes',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.1',
            ],
            [
                'code' => '1.1.4',
                'name' => 'Sobrancelha e Estética Masculina',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.1',
            ],
            [
                'code' => '1.2',
                'name' => 'Venda de Produtos e Cosméticos',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => true,
                'parent_code' => '1',
            ],
            [
                'code' => '1.2.1',
                'name' => 'Pomadas, Ceras e Fixadores',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.2',
            ],
            [
                'code' => '1.2.2',
                'name' => 'Shampoos, Condicionadores e Tônicos',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.2',
            ],
            [
                'code' => '1.2.3',
                'name' => 'Óleos e Balms para Barba',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.2',
            ],
            [
                'code' => '1.2.4',
                'name' => 'Bebidas, Cervejas e Bar da Barbearia',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.2',
            ],
            [
                'code' => '1.3',
                'name' => 'Planos e Assinaturas',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => true,
                'parent_code' => '1',
            ],
            [
                'code' => '1.3.1',
                'name' => 'Assinaturas Mensais / Clube da Barba',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.3',
            ],
            [
                'code' => '1.3.2',
                'name' => 'Pacotes Promocionais',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.3',
            ],
            [
                'code' => '1.4',
                'name' => 'Outras Receitas Operacionais',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => true,
                'parent_code' => '1',
            ],
            [
                'code' => '1.4.1',
                'name' => 'Gorjetas e Taxas Diversas',
                'type' => FinancialType::TYPE_REVENUE,
                'is_synthetic' => false,
                'parent_code' => '1.4',
            ],

            // 2. CUSTOS DIRETOS OPERACIONAIS (SERVIÇOS E BANCADA)
            [
                'code' => '2',
                'name' => 'CUSTOS DIRETOS OPERACIONAIS',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => true,
                'parent_code' => null,
            ],
            [
                'code' => '2.1',
                'name' => 'Comissões da Equipe de Barbeiros',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => true,
                'parent_code' => '2',
            ],
            [
                'code' => '2.1.1',
                'name' => 'Comissões sobre Serviços',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => false,
                'parent_code' => '2.1',
            ],
            [
                'code' => '2.1.2',
                'name' => 'Comissões sobre Venda de Produtos',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => false,
                'parent_code' => '2.1',
            ],
            [
                'code' => '2.1.3',
                'name' => 'Diárias e Pagamentos a Barbeiros Parceiros',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => false,
                'parent_code' => '2.1',
            ],
            [
                'code' => '2.2',
                'name' => 'Insumos de Bancada e Lavatório',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => true,
                'parent_code' => '2',
            ],
            [
                'code' => '2.2.1',
                'name' => 'Lâminas, Navalhetes e Descartáveis',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => false,
                'parent_code' => '2.2',
            ],
            [
                'code' => '2.2.2',
                'name' => 'Shampoos, Condicionadores e Cremes de Lavatório',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => false,
                'parent_code' => '2.2',
            ],
            [
                'code' => '2.2.3',
                'name' => 'Golas Higiênicas e Capas Descartáveis',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => false,
                'parent_code' => '2.2',
            ],
            [
                'code' => '2.2.4',
                'name' => 'Produtos para Barboterapia (Óleos, Toalhas)',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => false,
                'parent_code' => '2.2',
            ],
            [
                'code' => '2.3',
                'name' => 'Custo das Mercadorias para Revenda (CMV)',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => true,
                'parent_code' => '2',
            ],
            [
                'code' => '2.3.1',
                'name' => 'Compra de Cosméticos e Pomadas para Revenda',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => false,
                'parent_code' => '2.3',
            ],
            [
                'code' => '2.3.2',
                'name' => 'Compra de Bebidas para Revenda',
                'type' => FinancialType::TYPE_COST,
                'is_synthetic' => false,
                'parent_code' => '2.3',
            ],

            // 3. DESPESAS OPERACIONAIS FIXAS E OCUPAÇÃO
            [
                'code' => '3',
                'name' => 'DESPESAS OPERACIONAIS FIXAS E OCUPAÇÃO',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => true,
                'parent_code' => null,
            ],
            [
                'code' => '3.1',
                'name' => 'Ocupação e Imóvel',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => true,
                'parent_code' => '3',
            ],
            [
                'code' => '3.1.1',
                'name' => 'Aluguel do Ponto Comercial',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.1.2',
                'name' => 'Condomínio e IPTU',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.1.3',
                'name' => 'Energia Elétrica',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.1.4',
                'name' => 'Água e Esgoto',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.1.5',
                'name' => 'Internet Fibra, Wi-Fi e Telefonia',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.1.6',
                'name' => 'Limpeza, Higiene e Lavanderia de Toalhas',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.2',
                'name' => 'Manutenção e Conservação',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => true,
                'parent_code' => '3',
            ],
            [
                'code' => '3.2.1',
                'name' => 'Manutenção de Cadeiras, Máquinas e Equipamentos',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.2',
            ],
            [
                'code' => '3.2.2',
                'name' => 'Manutenção do Espaço Físico / Reformas',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.2',
            ],
            [
                'code' => '3.2.3',
                'name' => 'Afiação de Tesouras e Lâminas',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.2',
            ],
            [
                'code' => '3.3',
                'name' => 'Administrativas e Pessoal Geral',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => true,
                'parent_code' => '3',
            ],
            [
                'code' => '3.3.1',
                'name' => 'Salários Recepção / Pró-labore',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.3',
            ],
            [
                'code' => '3.3.2',
                'name' => 'Honorários Contábeis e Jurídicos',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.3',
            ],
            [
                'code' => '3.3.3',
                'name' => 'Software de Gestão (AuroBarber) e Sistemas',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.3',
            ],
            [
                'code' => '3.3.4',
                'name' => 'Café, Bebidas de Cortesia e Copa',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.3',
            ],
            [
                'code' => '3.3.5',
                'name' => 'Outras Despesas Administrativas',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '3.3',
            ],

            // 4. DESPESAS COMERCIAIS E MARKETING
            [
                'code' => '4',
                'name' => 'DESPESAS COMERCIAIS E MARKETING',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => true,
                'parent_code' => null,
            ],
            [
                'code' => '4.1',
                'name' => 'Captação e Marketing',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => true,
                'parent_code' => '4',
            ],
            [
                'code' => '4.1.1',
                'name' => 'Tráfego Pago (Instagram, Facebook e Google Ads)',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '4.1',
            ],
            [
                'code' => '4.1.2',
                'name' => 'Banners, Fachada e Material Gráfico',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '4.1',
            ],
            [
                'code' => '4.1.3',
                'name' => 'Ações Promocionais e Parcerias Locais',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '4.1',
            ],

            // 5. DESPESAS FINANCEIRAS E TRIBUTOS
            [
                'code' => '5',
                'name' => 'DESPESAS FINANCEIRAS E TRIBUTOS',
                'type' => FinancialType::TYPE_FINANCIAL,
                'is_synthetic' => true,
                'parent_code' => null,
            ],
            [
                'code' => '5.1',
                'name' => 'Tarifas e Meios de Pagamento',
                'type' => FinancialType::TYPE_FINANCIAL,
                'is_synthetic' => true,
                'parent_code' => '5',
            ],
            [
                'code' => '5.1.1',
                'name' => 'Taxas de Maquininha de Cartão (Débito e Crédito)',
                'type' => FinancialType::TYPE_FINANCIAL,
                'is_synthetic' => false,
                'parent_code' => '5.1',
            ],
            [
                'code' => '5.1.2',
                'name' => 'Tarifas Bancárias e Manutenção de Conta Corrente',
                'type' => FinancialType::TYPE_FINANCIAL,
                'is_synthetic' => false,
                'parent_code' => '5.1',
            ],
            [
                'code' => '5.1.3',
                'name' => 'Juros e Multas Pagos a Fornecedores',
                'type' => FinancialType::TYPE_FINANCIAL,
                'is_synthetic' => false,
                'parent_code' => '5.1',
            ],
            [
                'code' => '5.2',
                'name' => 'Tributos e Impostos',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => true,
                'parent_code' => '5',
            ],
            [
                'code' => '5.2.1',
                'name' => 'Simples Nacional / DAS MEI / Impostos',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '5.2',
            ],
            [
                'code' => '5.2.2',
                'name' => 'Alvarás, Vigilância Sanitária e Taxas Municipais',
                'type' => FinancialType::TYPE_EXPENSE,
                'is_synthetic' => false,
                'parent_code' => '5.2',
            ],
        ];

        $codeToId = [];

        foreach ($accounts as $account) {
            $parentCode = $account['parent_code'];
            $parentId = $parentCode ? ($codeToId[$parentCode] ?? null) : null;

            $existing = FinancialType::query()
                ->where('barbershop_id', $barbershopId)
                ->where(function ($query) use ($account) {
                    $query->where('code', $account['code'])
                        ->orWhere('name', $account['name']);
                })
                ->first();

            if ($existing) {
                $existing->update([
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'slug' => $existing->slug ?: Str::slug($account['name'], '_'),
                    'type' => $account['type'],
                    'is_synthetic' => $account['is_synthetic'],
                    'parent_id' => $parentId,
                    'is_active' => true,
                ]);
                $codeToId[$account['code']] = $existing->id;
            } else {
                $new = FinancialType::create([
                    'barbershop_id' => $barbershopId,
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'slug' => Str::slug($account['name'], '_'),
                    'type' => $account['type'],
                    'is_synthetic' => $account['is_synthetic'],
                    'parent_id' => $parentId,
                    'is_active' => true,
                ]);
                $codeToId[$account['code']] = $new->id;
            }
        }
    }
}
