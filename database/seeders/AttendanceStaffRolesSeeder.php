<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AttendanceStaffRolesSeeder extends Seeder
{
    public function run(?PermissionRegistrar $permissionRegistrar = null): void
    {
        $permissionRegistrar ??= app(PermissionRegistrar::class);
        $secretaria = Role::firstOrCreate([
            'name' => 'secretaria',
            'guard_name' => 'web',
        ]);

        $professor = Role::firstOrCreate([
            'name' => 'professor',
            'guard_name' => 'web',
        ]);

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $permissions = [
            // Treino de digitação
            'ViewAny:TypingExercise',
            'View:TypingExercise',
            'Create:TypingExercise',
            'Update:TypingExercise',
            'Delete:TypingExercise',

            // Monitor de Laboratório (Ao vivo)
            'View:ClassroomLiveMonitor',
            'page_ClassroomLiveMonitor',

            // Estúdio LMS
            'View:LmsStudio',
            'page_LmsStudio',
            'View:LmsLesson',
            'Create:LmsLesson',
            'Update:LmsLesson',

            // Agenda e Calendário da Escola
            'View:SchoolAppointmentsCalendar',
            'page_SchoolAppointmentsCalendar',
            'ViewAny:SchoolAppointment',
            'View:SchoolAppointment',
            'Create:SchoolAppointment',
            'Update:SchoolAppointment',

            // Chamada, Horários e Salas
            'View:Agenda',
            'View:ScheduleBoard',
            'ViewAny:StudentAttendance',
            'View:StudentAttendance',
            'Create:StudentAttendance',
            'Update:StudentAttendance',
            'ViewAny:StudentSchedule',
            'View:StudentSchedule',
            'ViewAny:Course',
            'View:Course',
            'ViewAny:Classroom',
            'View:Classroom',

            // Acesso ao Portal e Player
            'View:StudentPortal',
            'View:LmsPlayer',
            'View:LmsStudentProgress',
            'Reset:LmsStudentProgress',

            // Relatórios Pedagógicos
            'View:PedagogicalFollowUpReport',
            'View:OuroModernoTranscript',
            'View:OuroModernoStudentLogsReport',
            'View:StudentAbsencesReport',
        ];

        // Secretaria recebe permissões de chamada, agenda, horários, alunos e relatórios
        $secretariaPermissions = [
            'View:Agenda',
            'View:ScheduleBoard',
            'View:SchoolAppointmentsCalendar',
            'page_SchoolAppointmentsCalendar',
            'ViewAny:SchoolAppointment',
            'View:SchoolAppointment',
            'Create:SchoolAppointment',
            'Update:SchoolAppointment',
            'ViewAny:StudentAttendance',
            'View:StudentAttendance',
            'Create:StudentAttendance',
            'Update:StudentAttendance',
            'ViewAny:StudentSchedule',
            'View:StudentSchedule',
            'View:ClassroomLiveMonitor',
            'page_ClassroomLiveMonitor',
            'ViewAny:TypingExercise',
            'View:TypingExercise',
            'View:StudentPortal',
            'View:StudentAbsencesReport',
            'View:StudentContactsReport',
            'View:OuroModernoLmsStudents',
            'Sync:OuroModernoLmsStudents',
            'ViewAny:Person',
            'View:Person',
            'Create:Person',
            'Update:Person',
            'ViewAny:Contract',
            'View:Contract',
            'Create:Contract',
            'Update:Contract',
            'ViewAny:Responsible',
            'View:Responsible',
            'Create:Responsible',
            'Update:Responsible',
            'ViewAny:ContractType',
            'View:ContractType',
            'ViewAny:Formation',
            'View:Formation',
        ];

        $permissionRegistrar->forgetCachedPermissions();

        // Garante a criação de todas as permissões customizadas declaradas na configuração do Filament Shield
        $customPermissions = array_keys(config('filament-shield.custom_permissions', []));
        foreach ($customPermissions as $customPerm) {
            Permission::findOrCreate($customPerm, 'web');
        }

        $allPermissions = array_unique(array_merge($permissions, $secretariaPermissions, [
            'View:SatisfactionSurveyReport',
            'Export:SatisfactionSurveyReport',
            'page_SatisfactionSurveyReport',
            'View:NetworkOverview',
            'page_NetworkOverview',
            'Transfer:SchoolData',
            'View:CommissionReport',
            'Export:CommissionReport',
            'page_CommissionReport',
            'View:LmsQuestionBank',
            'page_LmsQuestionBank',
            'View:SystemUpdate',
            'Apply:SystemUpdate',
            'page_SystemUpdate',
        ]));

        foreach ($allPermissions as $permName) {
            Permission::findOrCreate($permName, 'web');
        }

        $permissionRegistrar->forgetCachedPermissions();

        $professor->syncPermissions($permissions);
        $secretaria->syncPermissions($secretariaPermissions);

        // Super Admin possui todas as permissões cadastradas no sistema
        $superAdmin->syncPermissions(Permission::all());

        $permissionRegistrar->forgetCachedPermissions();
    }
}
