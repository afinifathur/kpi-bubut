<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'KPI Bubut')</title>

    {{-- Fonts & Icons (Localized via app.css) --}}

    {{-- Scripts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Page Specific Styles --}}
    @stack('styles')

    {{-- Select2 & jQuery Bundle (Local) --}}

</head>

<body class="bg-gray-100 text-gray-800 antialiased" x-data="layoutApp()">
    <div class="min-h-screen flex flex-col md:flex-row">

        {{-- Sidebar --}}
        @include('layouts.sidebar')

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col min-w-0 transition-all duration-300">

            {{-- Mobile Top Bar (Only visible on mobile < md) --}}
            <header class="md:hidden bg-[#1a2c5a] text-white h-14 flex items-center justify-between px-4 sticky top-0 z-30 shadow-md shrink-0">
                <div class="flex items-center gap-3">
                    <button type="button" @click="mobileDrawerOpen = true"
                        class="p-2 rounded-xl text-blue-200 hover:text-white hover:bg-white/10 active:bg-white/20 focus:outline-none min-h-[48px] min-w-[48px] flex items-center justify-center transition-colors"
                        aria-label="Buka Menu Navigasi">
                        <span class="material-icons-round text-2xl">menu</span>
                    </button>
                    <div class="flex items-center gap-2">
                        <span class="material-icons-round text-xl text-blue-400">precision_manufacturing</span>
                        <span class="font-bold text-base tracking-wide">KPI Bubut</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-xs font-black text-blue-300 uppercase">
                        {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                    </div>
                </div>
            </header>

            {{-- Topbar (optional, aman walau kosong) --}}
            @includeIf('layouts.topbar')

            <main class="flex-1 p-3.5 sm:p-6">
                <div class="w-full">
                    @yield('content')
                </div>
            </main>

            {{-- Footer (optional) --}}
            @includeIf('layouts.footer')

        </div>
    </div>

    {{-- Layout Alpine Handler --}}
    <script>
        function layoutApp() {
            return {
                mobileDrawerOpen: false,
                desktopCollapsed: localStorage.getItem('kpi_sidebar_collapsed') === 'true',
                toggleDesktopSidebar() {
                    this.desktopCollapsed = !this.desktopCollapsed;
                    localStorage.setItem('kpi_sidebar_collapsed', this.desktopCollapsed);
                }
            };
        }
    </script>

    {{-- Page Specific Scripts --}}
    @stack('scripts')

    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: "{{ session('success') }}",
                });
            });
        </script>
    @endif
</body>

</html>