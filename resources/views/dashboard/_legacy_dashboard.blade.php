{{-- DASHBOARD LAMA (PARTIAL) --}}

@php
    $date = $date ?? now()->format('d-m-Y');

    $avgKpiOperator = $avgKpiOperator ?? 0;
    $avgKpiMachine  = $avgKpiMachine ?? 0;
    $totalOutput    = $totalOutput ?? 0;
    $totalDowntime  = $totalDowntime ?? 0;
    $activeOperators = $activeOperators ?? 0;
    $activeMachines  = $activeMachines ?? 0;
@endphp

@if(isset($empty) && $empty)

    <x-card title="Dashboard KPI Bubut">
        <p class="text-gray-500">Belum ada data KPI.</p>
    </x-card>

@else

    <x-card title="Dashboard KPI Bubut ({{ $date }})">

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6">

            {{-- Avg KPI Operator --}}
            <div class="kpi-card
                {{ $avgKpiOperator >= 100 ? 'ok' : ($avgKpiOperator >= 90 ? 'warning' : 'bad') }}">
                <div class="label">Avg KPI Operator</div>
                <div class="value">{{ number_format($avgKpiOperator, 1) }}%</div>
            </div>

            {{-- Avg KPI Mesin --}}
            <div class="kpi-card
                {{ $avgKpiMachine >= 100 ? 'ok' : ($avgKpiMachine >= 90 ? 'warning' : 'bad') }}">
                <div class="label">Avg KPI Mesin</div>
                <div class="value">{{ number_format($avgKpiMachine, 1) }}%</div>
            </div>

            {{-- Total Output --}}
            <div class="kpi-card ok">
                <div class="label">Total Output</div>
                <div class="value">{{ number_format($totalOutput) }}</div>
            </div>

            {{-- Total Downtime --}}
            <div class="kpi-card {{ $totalDowntime == 0 ? 'ok' : 'warning' }}">
                <div class="label">Downtime</div>
                <div class="value">{{ $totalDowntime }} mnt</div>
            </div>

            {{-- Operator Aktif --}}
            <div class="kpi-card ok">
                <div class="label">Operator Aktif</div>
                <div class="value">{{ $activeOperators }}</div>
            </div>

            {{-- Mesin Aktif --}}
            <div class="kpi-card ok">
                <div class="label">Mesin Aktif</div>
                <div class="value">{{ $activeMachines }}</div>
            </div>

        </div>

    </x-card>

@endif
