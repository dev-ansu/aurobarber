<?php

namespace Database\Seeders;

use App\Models\FinancialType;
use Illuminate\Database\Seeder;

class FinancialTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Mensalidade', 'slug' => 'mensalidade', 'is_active' => true],
            ['name' => 'Taxa de Matrícula', 'slug' => 'taxa_de_matricula', 'is_active' => true],
            ['name' => 'Multa de Rescisão', 'slug' => 'multa_de_rescisao', 'is_active' => true],
            ['name' => 'Taxa de Reposição de Aula', 'slug' => 'taxa_de_reposicao_de_aula', 'is_active' => true],
            ['name' => 'Material Didático', 'slug' => 'material_didatico', 'is_active' => true],
            ['name' => 'Taxa de Emissão de Certificado', 'slug' => 'taxa_de_emissao_de_certificado', 'is_active' => true],
            ['name' => 'Outras Taxas', 'slug' => 'outras_taxas', 'is_active' => true],
        ];

        foreach ($types as $type) {
            FinancialType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}

