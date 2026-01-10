<aside class="w-64 min-h-screen bg-[#1a2c5a] text-white flex flex-col transition-all duration-300">
    <div class="h-16 flex items-center px-6 border-b border-white/10">
        <span class="material-icons-round text-2xl mr-2 text-blue-400">precision_manufacturing</span>
        <div>
            <h1 class="font-bold text-lg tracking-wide">KPI Bubut</h1>
            <p class="text-[10px] text-blue-200 uppercase tracking-wider">Tracking System</p>
        </div>
    </div>

    <nav class="flex-1 py-6 px-3 space-y-1 text-sm">

        <p class="px-3 text-[10px] font-semibold text-blue-300 uppercase tracking-wider mb-2">Menu Utama</p>

        <a href="{{ url('/dashboard') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('dashboard') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <span class="material-icons-round text-xl">dashboard</span>
            <span class="font-medium">Dashboard</span>
        </a>

        <div class="mt-6 mb-2 px-3 text-[10px] font-semibold text-blue-300 uppercase tracking-wider">Produksi</div>

        <a href="{{ route('production.create') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('production.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <span class="material-icons-round text-xl">add_circle_outline</span>
            <span class="font-medium">Input Produksi</span>
        </a>

        <a href="{{ route('reject.create') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('reject.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <span class="material-icons-round text-xl">error_outline</span>
            <span class="font-medium">Input Reject</span>
        </a>

        <a href="{{ route('downtime.create') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('downtime.create') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <span class="material-icons-round text-xl">timer_off</span>
            <span class="font-medium">Input Downtime</span>
        </a>

        <div class="mt-6 mb-2 px-3 text-[10px] font-semibold text-blue-300 uppercase tracking-wider">Laporan</div>

        <a href="{{ url('/tracking/operator') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('tracking/operator') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <span class="material-icons-round text-xl">people_outline</span>
            <span class="font-medium">Operator KPI</span>
        </a>

        <a href="{{ url('/tracking/mesin') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('tracking/mesin') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <span class="material-icons-round text-xl">precision_manufacturing</span>
            <span class="font-medium">Mesin KPI</span>
        </a>

        <a href="{{ url('/downtime') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('downtime') && !request()->is('downtime/input') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <span class="material-icons-round text-xl">history</span>
            <span class="font-medium">Riwayat Downtime</span>
        </a>

    </nav>

    {{-- User Profile / Footer --}}
    <div class="p-4 border-t border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-xs font-bold shadow-md">
                AD
            </div>
            <div>
                <p class="text-sm font-medium">Admin Bubut</p>
                <p class="text-xs text-blue-300">Kepala Shift</p>
            </div>
        </div>
    </div>
</aside>