<?php

use App\Models\Barbershop;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a deleted barbershop slug can be reused while active slugs stay unique', function () {
    $deletedBarbershop = Barbershop::create([
        'name' => 'Barbearia Teste',
        'slug' => 'teste',
    ]);

    $deletedBarbershop->delete();

    $replacement = Barbershop::create([
        'name' => 'Nova Barbearia Teste',
        'slug' => 'teste',
    ]);

    expect($replacement->exists)->toBeTrue()
        ->and(Barbershop::where('slug', 'teste')->count())->toBe(1)
        ->and(Barbershop::withTrashed()->where('slug', 'teste')->count())->toBe(2);

    $this->expectException(QueryException::class);

    Barbershop::create([
        'name' => 'Outra Barbearia Teste',
        'slug' => 'teste',
    ]);
});
