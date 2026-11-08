<?php

namespace Database\Seeders;

use App\Models\FinancialType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // 1. RECEITAS OPERACIONAIS
            [
                'code' => '1',
                'name' => 'RECEITAS OPERACIONAIS',
                'type' => 'revenue',
                'is_synthetic' => true,
                'parent_code' => null,
            ],
            [
                'code' => '1.1',
                'name' => 'Receitas Educacionais',
                'type' => 'revenue',
                'is_synthetic' => true,
                'parent_code' => '1',
            ],
            [
                'code' => '1.1.1',
                'name' => 'Mensalidade',
                'type' => 'revenue',
                'is_synthetic' => false,
                'parent_code' => '1.1',
            ],
            [
                'code' => '1.1.2',
                'name' => 'Taxa de Matrícula',
                'type' => 'revenue',
                'is_synthetic' => false,
                'parent_code' => '1.1',
            ],
            [
                'code' => '1.1.3',
                'name' => 'Material Didático',
                'type' => 'revenue',
                'is_synthetic' => false,
                'parent_code' => '1.1',
            ],
            [
                'code' => '1.1.4',
                'name' => 'Taxa de Emissão de Certificado',
                'type' => 'revenue',
                'is_synthetic' => false,
                'parent_code' => '1.1',
            ],
            [
                'code' => '1.1.5',
                'name' => 'Taxa de Reposição de Aula',
                'type' => 'revenue',
                'is_synthetic' => false,
                'parent_code' => '1.1',
            ],
            [
                'code' => '1.2',
                'name' => 'Outras Receitas Operacionais',
                'type' => 'revenue',
                'is_synthetic' => true,
                'parent_code' => '1',
            ],
            [
                'code' => '1.2.1',
                'name' => 'Multa de Rescisão',
                'type' => 'revenue',
                'is_synthetic' => false,
                'parent_code' => '1.2',
            ],
            [
                'code' => '1.2.2',
                'name' => 'Outras Taxas',
                'type' => 'revenue',
                'is_synthetic' => false,
                'parent_code' => '1.2',
            ],
            [
                'code' => '1.2.3',
                'name' => 'Juros e Multas por Atraso Recebidos',
                'type' => 'revenue',
                'is_synthetic' => false,
                'parent_code' => '1.2',
            ],

            // 2. CUSTOS DIRETOS DO ENSINO
            [
                'code' => '2',
                'name' => 'CUSTOS DIRETOS DO ENSINO',
                'type' => 'cost',
                'is_synthetic' => true,
                'parent_code' => null,
            ],
            [
                'code' => '2.1',
                'name' => 'Corpo Docente e Instrutores',
                'type' => 'cost',
                'is_synthetic' => true,
                'parent_code' => '2',
            ],
            [
                'code' => '2.1.1',
                'name' => 'Salários e Pagamentos de Instrutores',
                'type' => 'cost',
                'is_synthetic' => false,
                'parent_code' => '2.1',
            ],
            [
                'code' => '2.2',
                'name' => 'Plataformas e Material Didático de Ensino',
                'type' => 'cost',
                'is_synthetic' => true,
                'parent_code' => '2',
            ],
            [
                'code' => '2.2.1',
                'name' => 'Licenciamento Plataforma LMS (Ouro Moderno)',
                'type' => 'cost',
                'is_synthetic' => false,
                'parent_code' => '2.2',
            ],
            [
                'code' => '2.2.2',
                'name' => 'Custo de Gráfica, Livros e Impressão de Apostilas',
                'type' => 'cost',
                'is_synthetic' => false,
                'parent_code' => '2.2',
            ],
            [
                'code' => '2.2.3',
                'name' => 'Manutenção dos Laboratórios de Informática',
                'type' => 'cost',
                'is_synthetic' => false,
                'parent_code' => '2.2',
            ],

            // 3. DESPESAS OPERACIONAIS FIXAS
            [
                'code' => '3',
                'name' => 'DESPESAS OPERACIONAIS FIXAS',
                'type' => 'expense',
                'is_synthetic' => true,
                'parent_code' => null,
            ],
            [
                'code' => '3.1',
                'name' => 'Ocupação e Instalações',
                'type' => 'expense',
                'is_synthetic' => true,
                'parent_code' => '3',
            ],
            [
                'code' => '3.1.1',
                'name' => 'Aluguel do Prédio e Condomínio',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.1.2',
                'name' => 'Energia Elétrica',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.1.3',
                'name' => 'Água e Esgoto',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.1.4',
                'name' => 'Internet Fibra e Telefonia',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.1.5',
                'name' => 'Limpeza, Higiene e Copa',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '3.1',
            ],
            [
                'code' => '3.2',
                'name' => 'Administrativas e Pessoal Geral',
                'type' => 'expense',
                'is_synthetic' => true,
                'parent_code' => '3',
            ],
            [
                'code' => '3.2.1',
                'name' => 'Salários Secretaria e Recepção',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '3.2',
            ],
            [
                'code' => '3.2.2',
                'name' => 'Honorários Contábeis e Jurídicos',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '3.2',
            ],
            [
                'code' => '3.2.3',
                'name' => 'Sistemas, Softwares e Servidores',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '3.2',
            ],
            [
                'code' => '3.2.4',
                'name' => 'Material de Escritório e Papelaria',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '3.2',
            ],

            // 4. DESPESAS COMERCIAIS E MARKETING
            [
                'code' => '4',
                'name' => 'DESPESAS COMERCIAIS E MARKETING',
                'type' => 'expense',
                'is_synthetic' => true,
                'parent_code' => null,
            ],
            [
                'code' => '4.1',
                'name' => 'Captação e Marketing',
                'type' => 'expense',
                'is_synthetic' => true,
                'parent_code' => '4',
            ],
            [
                'code' => '4.1.1',
                'name' => 'Tráfego Pago (Meta Ads / Google Ads)',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '4.1',
            ],
            [
                'code' => '4.1.2',
                'name' => 'Panfletagem, Banners e Mídia Offline',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '4.1',
            ],
            [
                'code' => '4.2',
                'name' => 'Equipe Comercial',
                'type' => 'expense',
                'is_synthetic' => true,
                'parent_code' => '4',
            ],
            [
                'code' => '4.2.1',
                'name' => 'Comissões de Vendas e Matrículas',
                'type' => 'expense',
                'is_synthetic' => false,
                'parent_code' => '4.2',
            ],

            // 5. DESPESAS FINANCEIRAS
            [
                'code' => '5',
                'name' => 'DESPESAS FINANCEIRAS',
                'type' => 'financial',
                'is_synthetic' => true,
                'parent_code' => null,
            ],
            [
                'code' => '5.1',
                'name' => 'Tarifas e Meios de Pagamento',
                'type' => 'financial',
                'is_synthetic' => true,
                'parent_code' => '5',
            ],
            [
                'code' => '5.1.1',
                'name' => 'Taxas de Boletos, Pix e Gateways',
                'type' => 'financial',
                'is_synthetic' => false,
                'parent_code' => '5.1',
            ],
            [
                'code' => '5.1.2',
                'name' => 'Tarifas de Maquininha de Cartão de Crédito/Débito',
                'type' => 'financial',
                'is_synthetic' => false,
                'parent_code' => '5.1',
            ],
            [
                'code' => '5.1.3',
                'name' => 'Tarifas Bancárias e Manutenção de Conta Corrente',
                'type' => 'financial',
                'is_synthetic' => false,
                'parent_code' => '5.1',
            ],
            [
                'code' => '5.1.4',
                'name' => 'Juros e Multas Pagos a Fornecedores',
                'type' => 'financial',
                'is_synthetic' => false,
                'parent_code' => '5.1',
            ],
        ];

        // Mapeamento code -> id para resolver relacionamentos parent_id
        $codeToId = [];

        foreach ($accounts as $account) {
            $parentCode = $account['parent_code'];
            $parentId = $parentCode ? ($codeToId[$parentCode] ?? null) : null;

            // Tenta encontrar por código ou por nome equivalente (para preservar tipos existentes)
            $existing = FinancialType::where('code', $account['code'])
                ->orWhere(function ($query) use ($account) {
                    $query->whereNull('code')->where('name', $account['name']);
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

