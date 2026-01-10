<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'KPI Bubut')</title>

    {{-- Fonts & Icons --}}
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    {{-- Scripts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Page Specific Styles --}}
    @stack('styles')
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
</body>

</html>