@extends('layouts.app')

@section('title', 'Dashboard KPI Bubut')

@section('content')
    {{-- Header Content --}}
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Departemen Bubut</h1>
            <p class="text-sm text-gray-500">Sistem Tracking & Input KPI</p>
        </div>
        <div class="text-right">
            <p class="text-xs font-semibold text-gray-400 uppercase">
                {{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }}
            </p>
            <p class="text-sm font-medium text-gray-600">
                {{ \Carbon\Carbon::parse($date)->translatedFormat('l') }}
            </p>
        </div>
    </div>

    {{-- Info / Catatan --}}
    <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl flex gap-3 mb-6">
        <span class="material-icons-round text-blue-500 text-sm mt-0.5">info</span>
        <p class="text-xs text-blue-800 leading-relaxed">
            <span class="font-bold">Catatan:</span> Data yang ditampilkan adalah rekapitulasi tanggal
            <span class="font-bold">{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</span>.
        </p>
    </div>

    {{-- Grid KPI Utama --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- Card: Target Harian --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex justify-between items-start mb-2">
                <span class="material-icons-round text-blue-500 bg-blue-50 p-2 rounded-lg text-xl">track_changes</span>
            </div>
            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">Target Harian</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-bold text-slate-800">
                    {{ number_format($dailyStats->total_target ?? 0) }}
                </h3>
                <span class="text-xs text-slate-400 font-medium">pcs</span>
            </div>
            <div class="mt-3 flex items-center gap-1 text-xs text-slate-400">
                <span class="material-icons-round text-[14px]">check_circle</span>
                <span>Standar</span>
            </div>
        </div>

        {{-- Card: Realisasi --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex justify-between items-start mb-2">
                <span class="material-icons-round text-emerald-500 bg-emerald-50 p-2 rounded-lg text-xl">bolt</span>
            </div>
            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">Realisasi</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-bold text-slate-800">
                    {{ number_format($dailyStats->total_actual ?? 0) }}
                </h3>
                <span class="text-xs text-slate-400 font-medium">pcs</span>
            </div>

            {{-- Gap Logic --}}
            @php
                $gap = ($dailyStats->total_actual ?? 0) - ($dailyStats->total_target ?? 0);
            @endphp

            <div
                class="mt-3 flex items-center gap-1 text-xs {{ $gap >= 0 ? 'text-emerald-500' : 'text-red-500' }} font-bold">
                <span class="material-icons-round text-[14px]">{{ $gap >= 0 ? 'trending_up' : 'trending_down' }}</span>
                <span>
                    {{ $gap >= 0 ? '+' : '' }}{{ number_format($gap) }} dari target
                </span>
            </div>
        </div>

        {{-- Card: Efisiensi --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex justify-between items-start mb-2">
                <span class="material-icons-round text-orange-500 bg-orange-50 p-2 rounded-lg text-xl">speed</span>
            </div>
            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">Efisiensi</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-bold text-slate-800">
                    {{ number_format($efficiency, 1) }}%
                </h3>
            </div>
            <div
                class="mt-3 flex items-center gap-1 text-xs {{ $efficiency >= 100 ? 'text-emerald-500' : 'text-orange-500' }} font-bold">
                <span class="material-icons-round text-[14px]">auto_awesome</span>
                <span>{{ $efficiency >= 100 ? 'Sangat Baik' : 'Cukup' }}</span>
            </div>
        </div>

        {{-- Card: Overall KPI --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex justify-between items-start mb-2">
                <span class="material-icons-round text-teal-500 bg-teal-50 p-2 rounded-lg text-xl">donut_large</span>
            </div>
            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">Overall KPI</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-bold text-slate-800">
                    {{ number_format($overallKpi, 1) }}%
                </h3>
            </div>
            <div
                class="mt-3 flex items-center gap-1 text-xs {{ $overallKpi >= 90 ? 'text-teal-600' : 'text-orange-500' }} font-bold">
                <span class="material-icons-round text-[14px]">insights</span>
                <span>{{ $overallKpi >= 90 ? 'On Track' : 'Need Improvement' }}</span>
            </div>
        </div>

    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Column 1 & 2 (Charts) --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Weekly Production Chart --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-sm font-bold text-slate-800">Grafik Produksi (7 Hari Aktif)</h4>
                    <span class="material-icons-round text-slate-400">bar_chart</span>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="weeklyProductionChart"></canvas>
                </div>
            </div>

            {{-- Line Production Chart --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-sm font-bold text-slate-800">Produksi per Line (PCS)</h4>
                    <span class="material-icons-round text-slate-400">show_chart</span>
                </div>
                {{-- NOTE: Line data not yet split in backend, using placeholder line chart based on total --}}
                <div class="relative h-64 w-full">
                    <canvas id="lineProductionChart"></canvas>
                </div>

            </div>

        </div>

        {{-- Column 3 (Analysis & Ranking) --}}
        <div class="space-y-6">

            {{-- Defect Analysis --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h4 class="text-sm font-bold text-slate-800">Analisis Kerusakan</h4>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $monthLabel }}</p>
                    </div>
                    <span class="material-icons-round text-slate-400">error_outline</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-48 h-48 relative">
                        <canvas id="defectAnalysisChart"></canvas>
                    </div>
                    <div class="grid grid-cols-1 gap-3 mt-6 w-full text-xs">
                        @forelse($rejectAnalysis as $reject)
                            <div class="flex items-center gap-2">
                                {{-- Dynamic color helper or standard --}}
                                <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                                <span class="text-slate-600">
                                    {{ $reject->reject_reason }} ({{ number_format($reject->total_qty) }})
                                </span>
                            </div>
                        @empty
                            <div class="text-center text-gray-400 italic">Belum ada reject bulan ini</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Top Operators --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-4">
                    <span class="material-icons-round text-orange-500 text-lg">emoji_events</span>
                    <div>
                        <h4 class="text-sm font-bold text-slate-800">Top 3 Operator</h4>
                        <p class="text-[10px] text-slate-400 font-normal leading-none">{{ $monthLabel }}</p>
                    </div>
                </div>
                <div class="space-y-3">

                    @foreach($topOperators as $index => $op)
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full {{ $index == 0 ? 'bg-amber-400' : 'bg-slate-400' }} flex items-center justify-center text-white font-bold text-xs shadow-sm">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-slate-700 block">
                                        {{ $operatorNames[$op->operator_code] ?? $op->operator_code }}
                                    </span>
                                    <span class="text-[10px] text-slate-500">{{ $op->operator_code }}</span>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-emerald-600">{{ number_format($op->kpi_percent, 1) }}%</span>
                        </div>
                    @endforeach

                </div>

                @if($lowOperators->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-slate-100 space-y-2">
                        <div class="flex justify-between items-end mb-1">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Perlu Perhatian</p>
                            <span class="text-[9px] text-slate-300">{{ $monthLabel }}</span>
                        </div>
                        @foreach($lowOperators as $low)
                            <div class="flex justify-between text-xs font-medium">
                                <span class="text-slate-600">{{ $operatorNames[$low->operator_code] ?? $low->operator_code }}</span>
                                <span class="text-red-500 font-bold">{{ number_format($low->kpi_percent, 1) }}%</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Data from Controller
            const weeklyData = @json($weeklyProduction);

            // Weekly Charts Labels & Data
            const wLabels = weeklyData.map(d => d.kpi_date); // Maybe format date needed
            const wActual = weeklyData.map(d => d.total_actual);
            const wTarget = weeklyData.map(d => d.total_target);

            // Weekly Production Chart
            const ctxWeekly = document.getElementById('weeklyProductionChart').getContext('2d');
            new Chart(ctxWeekly, {
                type: 'bar',
                data: {
                    labels: wLabels,
                    datasets: [
                        {
                            label: 'Aktual',
                            data: wActual,
                            backgroundColor: '#1e40af',
                            borderRadius: 4,
                            barPercentage: 0.7
                        },
                        {
                            label: 'Target',
                            data: wTarget,
                            backgroundColor: '#e2e8f0',
                            borderRadius: 4,
                            barPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { display: false },
                            ticks: { font: { size: 10, family: 'Inter' } },
                            border: { display: false }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10, family: 'Inter' } },
                            border: { display: false }
                        }
                    }
                }
            });

            // Defect Analysis Chart (Dynamic)
            const rejectData = @json($rejectAnalysis);
            const rLabels = rejectData.map(r => r.reject_reason);
            const rQty = rejectData.map(r => r.total_qty);
            // Fallback colors
            const colors = ['#ef4444', '#f97316', '#cbd5e1', '#64748b', '#94a3b8'];

            const ctxDefect = document.getElementById('defectAnalysisChart').getContext('2d');
            new Chart(ctxDefect, {
                type: 'doughnut',
                data: {
                    labels: rLabels.length ? rLabels : ['Tidak ada Data'],
                    datasets: [{
                        data: rQty.length ? rQty : [1],
                        backgroundColor: rQty.length ? colors.slice(0, rQty.length) : ['#f3f4f6'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    cutout: '75%',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });

            // Line Production Chart (Dynamic)
            // Data from Controller: lineChartData, allLines
            const lineData = @json($lineChartData);
            const allLines = @json($allLines); // ['Line 1', 'Line 2', ...]

            // Dynamic Colors Helper
            const lineColors = [
                '#2563eb', // Blue
                '#db2777', // Pink
                '#16a34a', // Green
                '#d97706', // Amber
                '#9333ea', // Purple
                '#0891b2', // Cyan
                '#dc2626', // Red
            ];

            // Create Datasets
            const lineDatasets = allLines.map((lineName, index) => {
                // For each date in wLabels, get value or 0
                const dataPoints = wLabels.map(date => {
                    // Check if date exists in lineData
                    if (lineData[date] && lineData[date][lineName]) {
                        return lineData[date][lineName];
                    }
                    return 0;
                });

                const color = lineColors[index % lineColors.length];

                return {
                    label: lineName,
                    data: dataPoints,
                    borderColor: color,
                    backgroundColor: color,
                    tension: 0,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5
                };
            });

            const ctxLine = document.getElementById('lineProductionChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: wLabels, // Same Date Labels as Weekly Chart
                    datasets: lineDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 6,
                                font: { size: 10, family: 'Inter' }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            grid: { color: '#f1f5f9' },
                            ticks: { font: { size: 10, family: 'Inter' } },
                            border: { display: false },
                            beginAtZero: true
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10, family: 'Inter' } },
                            border: { display: false }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        });
    </script>
@endpush