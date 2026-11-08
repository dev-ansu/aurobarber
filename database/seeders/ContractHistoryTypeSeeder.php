<?php

namespace Database\Seeders;

use App\Models\ContractHistoryType;
use Illuminate\Database\Seeder;

class ContractHistoryTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['slug' => 'general', 'name' => 'Acompanhamento', 'sort_order' => 10],
            ['slug' => 'post_sale', 'name' => 'Pós-venda', 'sort_order' => 20],
            ['slug' => 'absence_follow_up', 'name' => 'Contato por falta', 'sort_order' => 30],
            ['slug' => 'financial_follow_up', 'name' => 'Acompanhamento financeiro', 'sort_order' => 40],
            ['slug' => 'pedagogical_follow_up', 'name' => 'Acompanhamento pedagógico', 'sort_order' => 50],
        ] as $type) {
            ContractHistoryType::query()->firstOrCreate(
                ['slug' => $type['slug']],
                [...$type, 'is_active' => true],
            );
        }
    }
}
