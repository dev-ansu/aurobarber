<?php

namespace Database\Seeders;

use App\Models\ContractType;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $version = now()->format('Y').'.1-demo';

        $contractType = ContractType::firstOrCreate(
            [
                'name' => 'Contrato de Prestação de Serviços Educacionais - Demonstração',
                'version' => $version,
            ],
            [
                'description' => 'Modelo de contrato de demonstração para testes locais.',
                'content' => <<<'TEXT'
CONTRATO DE PRESTAÇÃO DE SERVIÇOS EDUCACIONAIS

Este documento de demonstração registra a contratação dos serviços educacionais descritos no contrato. Os dados e condições aqui apresentados são fictícios e destinam-se exclusivamente a testes locais.

O responsável financeiro declara estar ciente dos valores e vencimentos das parcelas cadastradas no sistema.

Este modelo não substitui o contrato oficial da instituição.
TEXT,
                'is_active' => true,
            ],
        );

        ContractType::query()
            ->where('is_active', true)
            ->where('id', '!=', $contractType->id)
            ->update(['is_active' => false]);

        if (! $contractType->is_active) {
            $contractType->update(['is_active' => true]);
        }
    }
}
