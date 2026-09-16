{{-- Mobile Drawer Backdrop --}}
<div x-show="mobileDrawerOpen" @click="mobileDrawerOpen = false" x-cloak
    x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs md:hidden"
    style="display: none;"></div>

{{-- Sidebar Container (Desktop Persistent + Mobile Slide-over Drawer) --}}
<aside :class="[
        mobileDrawerOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
        desktopCollapsed ? 'md:w-20' : 'md:w-64'
    ]"
    class="fixed md:sticky top-0 left-0 z-50 md:z-10 w-72 h-screen bg-[#1a2c5a] text-white flex flex-col overflow-y-auto overflow-x-hidden scrollbar-thin transition-all duration-300 ease-in-out shadow-2xl md:shadow-none shrink-0"
    style="scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.15) transparent">
    
    {{-- Header with Collapse/Expand Controls --}}
    <div class="h-16 flex items-center justify-between px-4 border-b border-white/10 shrink-0">
        <div class="flex items-center min-w-0" :class="desktopCollapsed ? 'md:justify-center md:w-full' : ''">
            <span class="material-icons-round text-2xl text-blue-400 shrink-0" :class="desktopCollapsed ? 'md:hidden' : 'mr-2.5'">precision_manufacturing</span>
            <div x-show="!desktopCollapsed" class="truncate" x-transition.opacity>
                <h1 class="font-bold text-base tracking-wide truncate">KPI Bubut</h1>
                <p class="text-[9px] text-blue-200 uppercase tracking-wider">Tracking System</p>
            </div>
        </div>

        {{-- Desktop Explicit Collapse/Expand Arrow Button [ ← ] / [ → ] --}}
        <button type="button" @click="toggleDesktopSidebar()"
            class="hidden md:flex items-center justify-center w-8 h-8 rounded-lg text-blue-200 hover:text-white hover:bg-white/10 active:bg-white/20 transition-colors cursor-pointer shrink-0"
            :title="desktopCollapsed ? 'Buka Sidebar (Expand)' : 'Kecilkan Sidebar (Collapse)'">
            <span class="material-icons-round text-xl" x-text="desktopCollapsed ? 'chevron_right' : 'chevron_left'">chevron_left</span>
        </button>

        {{-- Mobile Close Drawer Button [ ✕ ] with min 48px touch target --}}
        <button type="button" @click="mobileDrawerOpen = false"
            class="md:hidden text-white/70 hover:text-white p-2.5 rounded-xl min-h-[48px] min-w-[48px] flex items-center justify-center hover:bg-white/10 focus:outline-none"
            aria-label="Tutup Menu">
            <span class="material-icons-round text-2xl">close</span>
        </button>
    </div>

    {{-- User Profile Section --}}
    <div class="border-b border-white/10 bg-black/10 shrink-0 transition-all"
        :class="desktopCollapsed ? 'md:px-2 md:py-3 px-5 py-4' : 'px-5 py-4'">
        <div class="flex items-center justify-between group" :class="desktopCollapsed ? 'md:flex-col md:gap-2' : ''">
            <div class="flex items-center gap-3 overflow-hidden" :class="desktopCollapsed ? 'md:justify-center' : ''">
                <div
                    class="shrink-0 w-9 h-9 rounded-xl bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-xs font-black text-blue-300 shadow-inner uppercase"
                    :title="desktopCollapsed ? '{{ Auth::user()->name }} ({{ Auth::user()->role }})' : ''">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="truncate" x-show="!desktopCollapsed" x-transition.opacity>
                    <p class="text-xs font-bold text-white truncate leading-tight">{{ Auth::user()->name }}</p>
                    <p class="text-[9px] text-blue-300 uppercase tracking-tighter mt-0.5">{{ Auth::user()->role }}</p>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="shrink-0" :class="desktopCollapsed ? 'md:m-0' : 'ml-2'">
                @csrf
                <button type="submit"
                    class="p-1.5 rounded-lg text-blue-400 hover:text-white hover:bg-red-500/20 transition-all cursor-pointer"
                    title="Sign Out">
                    <span class="material-icons-round text-lg leading-none">logout</span>
                </button>
            </form>
        </div>
        <div class="mt-1.5 pl-12" x-show="!desktopCollapsed" x-transition.opacity>
            <p class="text-[8px] text-blue-400/60 truncate font-mono">{{ Auth::user()->email }}</p>
        </div>
    </div>

    @php
        $currUser = Auth::user();
        $isSpecialHr = in_array($currUser->email, ['adminhr@peroniks.com', 'managerhr@peroniks.com']);
        $isDirekturOrMR = in_array($currUser->role, ['direktur', 'mr', 'hr_admin', 'hr_manager', 'guest']) || $isSpecialHr;
        $isManager = $currUser->role === 'manager';
        $additionalDepts = $currUser->additional_department_codes ?? [];
        $hasAdditionalDepts = !empty($additionalDepts);
        $isReadOnly = $currUser->isReadOnly();
    @endphp

    {{-- Department Context Switcher (Hidden when collapsed on desktop) --}}
    @if($isDirekturOrMR || ($isManager && ($currUser->department_code || $hasAdditionalDepts)))
        <div class="px-4 py-3 border-b border-white/5 bg-black/10" x-show="!desktopCollapsed" x-transition.opacity>
            <label class="block text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1.5 px-1">
                {{ $isDirekturOrMR ? 'System Context' : 'Department Access' }}
            </label>
            <select id="contextSwitcher"
                class="w-full bg-[#1e3a8a]/50 border-white/10 rounded-xl text-xs font-bold text-blue-100 focus:ring-blue-500/50 focus:border-blue-400 transition-all cursor-pointer py-2 px-3">

                @if($isDirekturOrMR)
                    <option value="all" {{ !session('selected_department_code') ? 'selected' : '' }}>🌎 ALL DEPARTMENTS</option>
                    @foreach(\Illuminate\Support\Facades\DB::connection('master')->table('md_departments')->where('code', 'LIKE', '404%')->where('status', 'active')->orderBy('code')->get() as $dept)
                        <option value="{{ $dept->code }}" {{ session('selected_department_code') == $dept->code ? 'selected' : '' }}>
                            🏢 {{ $dept->code }} - {{ $dept->name }}
                        </option>
                    @endforeach
                @elseif($isManager)
                    @php
                        $allowedCodes = array_merge([$currUser->department_code], $additionalDepts);
                        $allowedDepts = \Illuminate\Support\Facades\DB::connection('master')
                            ->table('md_departments')
                            ->where(function ($q) use ($allowedCodes) {
                                foreach (array_filter($allowedCodes) as $code) {
                                    $q->orWhere('code', 'LIKE', $code . '%');
                                }
                            })
                            ->where('status', 'active')
                            ->orderBy('code')
                            ->get();
                    @endphp

                    <option value="all" {{ !session('selected_department_code') ? 'selected' : '' }}>🌎 ALL MY DEPARTMENTS</option>
                    @foreach($allowedDepts as $dept)
                        <option value="{{ $dept->code }}" {{ session('selected_department_code') == $dept->code ? 'selected' : '' }}>
                            🏢 {{ $dept->code }} - {{ $dept->name }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
        <script>
            document.getElementById('contextSwitcher')?.addEventListener('change', async function () {
                const code = this.value;
                try {
                    const res = await fetch('{{ route("api.context.set") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ department_code: code })
                    });
                    if (res.ok) {
                        window.location.reload();
                    }
                } catch (e) {
                    console.error('Context switch failed', e);
                }
            });
        </script>
    @endif

    {{-- Main Navigation Links --}}
    <nav class="flex-1 py-4 px-2.5 space-y-1 text-sm">

        <p x-show="!desktopCollapsed" x-transition.opacity class="px-2.5 text-[10px] font-semibold text-blue-300 uppercase tracking-wider mb-2">Menu Utama</p>

        <a href="{{ url('/dashboard') }}"
            :title="desktopCollapsed ? 'Dashboard' : ''"
            :class="{ 'md:justify-center': desktopCollapsed }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('dashboard') && !request()->is('dashboard/*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <div class="w-6 flex justify-center shrink-0">
                <span class="material-icons-round text-xl">dashboard</span>
            </div>
            <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Dashboard</span>
        </a>

        <a href="{{ url('/dashboard/operator') }}"
            :title="desktopCollapsed ? 'KPI Trend' : ''"
            :class="{ 'md:justify-center': desktopCollapsed }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('dashboard/operator') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <div class="w-6 flex justify-center shrink-0">
                <span class="material-icons-round text-xl">insights</span>
            </div>
            <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">KPI Trend</span>
        </a>

        <a href="{{ route('leaderboard.index') }}"
            :title="desktopCollapsed ? 'Leaderboard' : ''"
            :class="{ 'md:justify-center': desktopCollapsed }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('leaderboard.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <div class="w-6 flex justify-center shrink-0">
                <span class="material-icons-round text-xl">emoji_events</span>
            </div>
            <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Leaderboard</span>
        </a>

        {{-- Group Produksi --}}
        @if(!$isReadOnly)
            <div x-show="!desktopCollapsed" x-transition.opacity class="mt-5 mb-2 px-2.5 text-[10px] font-semibold text-blue-300 uppercase tracking-wider">Produksi</div>

            <a href="{{ route('production.create') }}"
                :title="desktopCollapsed ? 'Input Produksi' : ''"
                :class="{ 'md:justify-center': desktopCollapsed }"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('production.*') && !request()->has('scan') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                <div class="w-6 flex justify-center shrink-0">
                    <span class="material-icons-round text-xl">add_circle</span>
                </div>
                <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Input Produksi</span>
            </a>

            <a href="{{ route('production.create', ['scan' => 1]) }}"
                :title="desktopCollapsed ? 'Scan Kitir' : ''"
                :class="{ 'md:justify-center': desktopCollapsed }"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('production.*') && request()->has('scan') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                <div class="w-6 flex justify-center shrink-0">
                    <span class="material-icons-round text-xl">qr_code_scanner</span>
                </div>
                <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Scan Kitir</span>
            </a>

            <a href="{{ route('reject.create') }}"
                :title="desktopCollapsed ? 'Input Reject' : ''"
                :class="{ 'md:justify-center': desktopCollapsed }"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('reject.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                <div class="w-6 flex justify-center shrink-0">
                    <span class="material-icons-round text-xl">error</span>
                </div>
                <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Input Reject</span>
            </a>

            <a href="{{ route('downtime.create') }}"
                :title="desktopCollapsed ? 'Input Downtime' : ''"
                :class="{ 'md:justify-center': desktopCollapsed }"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('downtime.create') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                <div class="w-6 flex justify-center shrink-0">
                    <span class="material-icons-round text-xl">timer_off</span>
                </div>
                <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Input Downtime</span>
            </a>
        @endif

        {{-- Group Laporan --}}
        <div x-show="!desktopCollapsed" x-transition.opacity class="mt-5 mb-2 px-2.5 text-[10px] font-semibold text-blue-300 uppercase tracking-wider">Laporan</div>

        <a href="{{ url('/tracking/operator') }}"
            :title="desktopCollapsed ? 'Operator KPI' : ''"
            :class="{ 'md:justify-center': desktopCollapsed }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('tracking/operator') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <div class="w-6 flex justify-center shrink-0">
                <span class="material-icons-round text-xl">groups</span>
            </div>
            <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Operator KPI</span>
        </a>

        <a href="{{ url('/tracking/mesin') }}"
            :title="desktopCollapsed ? 'Mesin KPI' : ''"
            :class="{ 'md:justify-center': desktopCollapsed }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('tracking/mesin') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <div class="w-6 flex justify-center shrink-0">
                <span class="material-icons-round text-xl">precision_manufacturing</span>
            </div>
            <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Mesin KPI</span>
        </a>

        <a href="{{ url('/downtime') }}"
            :title="desktopCollapsed ? 'Riwayat Downtime' : ''"
            :class="{ 'md:justify-center': desktopCollapsed }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('downtime') && !request()->is('downtime/input') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <div class="w-6 flex justify-center shrink-0">
                <span class="material-icons-round text-xl">history</span>
            </div>
            <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Riwayat Downtime</span>
        </a>

        {{-- Group Daftar Harian --}}
        <div x-show="!desktopCollapsed" x-transition.opacity class="mt-5 mb-2 px-2.5 text-[10px] font-semibold text-blue-300 uppercase tracking-wider">Daftar Harian</div>

        <a href="{{ route('daily_report.operator.index') }}"
            :title="desktopCollapsed ? 'Operator' : ''"
            :class="{ 'md:justify-center': desktopCollapsed }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('daily_report.operator.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <div class="w-6 flex justify-center shrink-0">
                <span class="material-icons-round text-xl">assignment_ind</span>
            </div>
            <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Operator</span>
        </a>

        <a href="{{ route('daily_report.reject.index') }}"
            :title="desktopCollapsed ? 'Kerusakan' : ''"
            :class="{ 'md:justify-center': desktopCollapsed }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('daily_report.reject.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <div class="w-6 flex justify-center shrink-0">
                <span class="material-icons-round text-xl">error</span>
            </div>
            <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Kerusakan</span>
        </a>

        <a href="{{ route('daily_report.downtime.index') }}"
            :title="desktopCollapsed ? 'Downtime' : ''"
            :class="{ 'md:justify-center': desktopCollapsed }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('daily_report.downtime.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
            <div class="w-6 flex justify-center shrink-0">
                <span class="material-icons-round text-xl">timer_off</span>
            </div>
            <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Downtime</span>
        </a>

        {{-- Group System (Direktur / MR Only) --}}
        @if(in_array(Auth::user()->role, ['direktur', 'mr']))
            <div x-show="!desktopCollapsed" x-transition.opacity class="mt-5 mb-2 px-2.5 text-[10px] font-semibold text-blue-300 uppercase tracking-wider">System</div>

            <a href="{{ route('audit_logs.index') }}"
                :title="desktopCollapsed ? 'Audit Logs' : ''"
                :class="{ 'md:justify-center': desktopCollapsed }"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('audit_logs.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                <div class="w-6 flex justify-center shrink-0">
                    <span class="material-icons-round text-xl">security</span>
                </div>
                <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Audit Logs</span>
            </a>
        @endif

        {{-- Group HR / OEE / Capacity --}}
        @if(in_array(Auth::user()->role, ['direktur', 'mr', 'admin_dept', 'manager']))
            @if(!in_array(Auth::user()->role, ['direktur', 'mr']))
                <div x-show="!desktopCollapsed" x-transition.opacity class="mt-5 mb-2 px-2.5 text-[10px] font-semibold text-blue-300 uppercase tracking-wider">HR</div>
            @endif
            <a href="{{ route('tracking.cycle_time.index') }}"
                :title="desktopCollapsed ? 'Cycle Time Report' : ''"
                :class="{ 'md:justify-center': desktopCollapsed }"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->is('tracking/cycle-time*') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                <div class="w-6 flex justify-center shrink-0">
                    <span class="material-icons-round text-xl">timer</span>
                </div>
                <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Cycle Time Report</span>
            </a>

            <a href="{{ route('hr_report.index') }}"
                :title="desktopCollapsed ? 'Report HR' : ''"
                :class="{ 'md:justify-center': desktopCollapsed }"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('hr_report.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                <div class="w-6 flex justify-center shrink-0">
                    <span class="material-icons-round text-xl">report_problem</span>
                </div>
                <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Report HR</span>
            </a>

            <a href="{{ route('oee.index') }}"
                :title="desktopCollapsed ? 'Report OEE' : ''"
                :class="{ 'md:justify-center': desktopCollapsed }"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('oee.index') ? 'bg-blue-600 text-white shadow-lg' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                <div class="w-6 flex justify-center shrink-0">
                    <span class="material-icons-round text-xl">assessment</span>
                </div>
                <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Report OEE</span>
            </a>

            <a href="{{ route('planned_capacity.index') }}"
                :title="desktopCollapsed ? 'Planned Capacity' : ''"
                :class="{ 'md:justify-center': desktopCollapsed }"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('planned_capacity.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                <div class="w-6 flex justify-center shrink-0">
                    <span class="material-icons-round text-xl">calendar_today</span>
                </div>
                <span x-show="!desktopCollapsed" x-transition.opacity class="font-medium truncate">Planned Capacity</span>
            </a>
        @endif

    </nav>

</aside>