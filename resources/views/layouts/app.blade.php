<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'KPI Bubut')</title>

    {{-- Fonts & Icons --}}
    <style>
        @font-face {
            font-family: 'Material Icons Round';
            font-style: normal;
            font-weight: 400;
            src: url("{{ asset('fonts/material-icons-round-latin-400-normal.woff2') }}") format('woff2'),
                url("{{ asset('fonts/material-icons-round-latin-400-normal.woff') }}") format('woff');
        }
    </style>
    {{-- Local icons imported in app.css --}}

    {{-- Scripts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Page Specific Styles --}}
    @stack('styles')

    {{-- Select2 CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body class="bg-gray-100 text-gray-800 antialiased">
    <div class="min-h-screen flex">

        {{-- Sidebar --}}
        @include('layouts.sidebar')

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col">

            {{-- Topbar (optional, aman walau kosong) --}}
            @includeIf('layouts.topbar')

            <main class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </main>

            {{-- Footer (optional) --}}
            @includeIf('layouts.footer')

        </div>
    </div>

    {{-- Page Specific Scripts --}}
    @stack('scripts')

    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: "{{ session('success') }}",
                    // timer: 3000,
                    // showConfirmButton: false
                });
            });
        </script>
    @endif
</body>

</html>