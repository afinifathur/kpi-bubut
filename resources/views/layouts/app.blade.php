<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'KPI Bubut')</title>

    {{-- Tailwind CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.min.css') }}">

    {{-- Custom UI / Enhancement --}}
    <link rel="stylesheet" href="{{ asset('assets/css/ui-modern.css') }}">

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
