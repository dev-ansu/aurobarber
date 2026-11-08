<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            // Permissões do Estúdio e Gestão de Conteúdo LMS
            'ViewAny:LmsCourse',
            'View:LmsCourse',
            'Create:LmsCourse',
            'Update:LmsCourse',
            'Delete:LmsCourse',

            // Gestão de Aulas e Passos Interativos (Método Jesuítico)
            'View:LmsLesson',
            'Create:LmsLesson',
            'Update:LmsLesson',
            'Delete:LmsLesson',

            // Monitoramento e Auditoria Pedagógica dos Alunos
            'View:LmsStudentProgress',
            'Reset:LmsStudentProgress',

            // Player Interativo e Portal do Aluno
            'View:LmsPlayer',
            'View:StudentPortal',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Atribui ao Super Admin por padrão na política Zero-Trust
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'ViewAny:LmsCourse',
            'View:LmsCourse',
            'Create:LmsCourse',
            'Update:LmsCourse',
            'Delete:LmsCourse',
            'View:LmsLesson',
            'Create:LmsLesson',
            'Update:LmsLesson',
            'Delete:LmsLesson',
            'View:LmsStudentProgress',
            'Reset:LmsStudentProgress',
            'View:LmsPlayer',
            'View:StudentPortal',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
