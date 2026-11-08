<x-filament-widgets::widget>
    @php($perms = $this->permissions)
    <x-filament::section :collapsible="true" :compact="true">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span>⚡</span>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                    Atalhos Rápidos de Gestão
                </span>
            </div>
        </x-slot>

        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-2.5 pt-1">
            @if ($perms['canCreateContract'])
                <a href="{{ \Illuminate\Support\Facades\Route::has('filament.admin.resources.contracts.create') ? route('filament.admin.resources.contracts.create') : url('/admin/contracts/create') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-xl bg-amber-500/5 hover:bg-amber-500/15 border border-amber-500/20 transition min-w-0">
                    <span class="text-lg group-hover:scale-110 transition-transform shrink-0">🎓</span>
                    <div class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-gray-900 dark:text-gray-100 truncate">Matrícula</span>
                        <span class="block text-[10px] text-gray-400 truncate">Novo contrato</span>
                    </div>
                </a>
            @endif

            @if ($perms['canManageAttendance'])
                <a href="{{ \Illuminate\Support\Facades\Route::has('filament.admin.pages.agenda') ? route('filament.admin.pages.agenda') : url('/admin/agenda') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-xl bg-blue-500/5 hover:bg-blue-500/15 border border-blue-500/20 transition min-w-0">
                    <span class="text-lg group-hover:scale-110 transition-transform shrink-0">📋</span>
                    <div class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-gray-900 dark:text-gray-100 truncate">Agenda</span>
                        <span class="block text-[10px] text-gray-400 truncate">Presenças do dia</span>
                    </div>
                </a>
            @endif

            @if ($perms['canManageInstallments'])
                <a href="{{ \Illuminate\Support\Facades\Route::has('filament.admin.resources.installments.index') ? route('filament.admin.resources.installments.index') : url('/admin/installments') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-xl bg-emerald-500/5 hover:bg-emerald-500/15 border border-emerald-500/20 transition min-w-0">
                    <span class="text-lg group-hover:scale-110 transition-transform shrink-0">💵</span>
                    <div class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-gray-900 dark:text-gray-100 truncate">Mensalidades</span>
                        <span class="block text-[10px] text-gray-400 truncate">Cobranças & Baixa</span>
                    </div>
                </a>
            @endif

            @if ($perms['canViewDre'])
                <a href="{{ \Illuminate\Support\Facades\Route::has('filament.admin.pages.dre-gerencial') ? route('filament.admin.pages.dre-gerencial') : url('/admin/dre-gerencial') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-xl bg-purple-500/5 hover:bg-purple-500/15 border border-purple-500/20 transition min-w-0">
                    <span class="text-lg group-hover:scale-110 transition-transform shrink-0">📊</span>
                    <div class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-gray-900 dark:text-gray-100 truncate">DRE</span>
                        <span class="block text-[10px] text-gray-400 truncate">Relatório contábil</span>
                    </div>
                </a>
            @endif

            @if ($perms['canManageBackup'])
                <a href="{{ \Illuminate\Support\Facades\Route::has('filament.admin.pages.backup-management') ? route('filament.admin.pages.backup-management') : url('/admin/backup-management') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-xl bg-teal-500/5 hover:bg-teal-500/15 border border-teal-500/20 transition min-w-0">
                    <span class="text-lg group-hover:scale-110 transition-transform shrink-0">🛡️</span>
                    <div class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-gray-900 dark:text-gray-100 truncate">Backup</span>
                        <span class="block text-[10px] text-gray-400 truncate">Proteção</span>
                    </div>
                </a>
            @endif

            @if ($perms['canManageUpdate'])
                <a href="{{ \Illuminate\Support\Facades\Route::has('filament.admin.pages.system-update') ? route('filament.admin.pages.system-update') : url('/admin/system-update') }}" class="group flex items-center gap-2.5 px-3 py-2 rounded-xl bg-orange-500/5 hover:bg-orange-500/15 border border-orange-500/20 transition min-w-0">
                    <span class="text-lg group-hover:scale-110 transition-transform shrink-0">🚀</span>
                    <div class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-gray-900 dark:text-gray-100 truncate">Atualizações</span>
                        <span class="block text-[10px] text-gray-400 truncate">Versão Cloud</span>
                    </div>
                </a>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
