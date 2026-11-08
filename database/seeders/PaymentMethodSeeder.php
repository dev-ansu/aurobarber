<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Dinheiro', 'slug' => 'dinheiro', 'is_active' => true],
            ['name' => 'PIX', 'slug' => 'pix', 'is_active' => true],
            ['name' => 'Cartão de Crédito', 'slug' => 'cartao_de_credito', 'is_active' => true],
            ['name' => 'Cartão de Débito', 'slug' => 'cartao_de_debito', 'is_active' => true],
            ['name' => 'Boleto Bancário', 'slug' => 'boleto_bancario', 'is_active' => true],
            ['name' => 'Carnê', 'slug' => 'carne', 'is_active' => true],
            ['name' => 'Transferência Bancária', 'slug' => 'transferencia_bancaria', 'is_active' => true],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['slug' => $method['slug']],
                $method
            );
        }
    }
}
