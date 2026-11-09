<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Garante que o papel super_admin existe
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        // Atribui super_admin para todos os donos de barbearia e master_admin existentes
        $owners = User::whereIn('role', ['owner', 'master_admin'])
            ->orWhereHas('ownedBarbershops')
            ->get();

        foreach ($owners as $owner) {
            if (!$owner->hasRole('super_admin')) {
                $owner->assignRole($superAdmin);
            }
        }

        // Remove permissões de Barbershop que pertencem apenas ao SaaS Master
        Permission::where('name', 'like', '%:Barbershop')->delete();

        // Sincroniza permissões do Shield para o painel admin
        try {
            Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => 'admin',
                '--no-interaction' => true,
            ]);
        } catch (\Throwable $e) {
            // Ignora falha silenciosa caso executado fora do contexto HTTP/CLI completo
        }
    }

    public function down(): void
    {
        //
    }
};
