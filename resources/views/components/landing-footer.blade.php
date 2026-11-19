<footer class="bg-slate-950 border-t border-slate-900 py-12 text-slate-400 text-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-amber-600 via-amber-500 to-amber-300 flex items-center justify-center text-slate-950 font-black text-lg">
                    💈
                </div>
                <div>
                    <span class="text-sm font-black text-white">Auro<span class="text-amber-400">Barber</span> PRO</span>
                    <span class="text-[11px] text-slate-500 block">Tecnologia em Gestão & Agendamento para Barbearias</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-6 text-slate-400 font-medium">
                <a href="/#recursos" class="hover:text-amber-400 transition">Recursos</a>
                <a href="/#modulos" class="hover:text-amber-400 transition">Módulos</a>
                <a href="/#planos" class="hover:text-amber-400 transition">Planos</a>
                <a href="/#faq" class="hover:text-amber-400 transition">FAQ</a>
                <a href="{{ url('/admin') }}" class="hover:text-amber-400 transition">Login Administrativo</a>
            </div>
        </div>

        <div class="pt-6 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-slate-500">
            <div>
                &copy; {{ date('Y') }} AuroBarber &bull; Uma solução <strong class="text-slate-400">Aurosoft Tecnologia</strong>. Todos os direitos reservados.
            </div>
            <div class="flex items-center gap-4">
                <span>Ambiente Seguro SSL 256-bit</span>
                <span>•</span>
                <span>Conformidade com a Lei do Salão Parceiro</span>
            </div>
        </div>
    </div>
</footer>
