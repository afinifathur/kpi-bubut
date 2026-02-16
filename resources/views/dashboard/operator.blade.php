@extends('layouts.app')

@section('title', 'Dashboard Operator')

@section('content')

    {{-- Header --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Operator</h1>
            <p class="text-gray-500">
                Trend KPI Operator:
                <span class="font-semibold text-gray-700">
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                </span>
                s/d
                <span class="font-semibold text-gray-700">
                    {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </span>
            </p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r shadow-sm flex items-center">
            <span class="material-icons-round text-lg mr-2">error_outline</span>
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <form method="GET" id="filterForm" class="flex flex-col md:flex-row md:items-end gap-3">
                {{-- Start Date --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date', $startDate) }}"
                           class="block w-full shadow-sm text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1.5">
                </div>

                {{-- End Date --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date', $endDate) }}"
                           class="block w-full shadow-sm text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1.5">
                </div>

                {{-- Operator Dropdown --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Operator</label>
                    <select name="operator_code" id="operator_code" class="select2-search block w-56 shadow-sm text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1.5">
                        <option value="all">Semua Operator</option>
                        @foreach($operatorNames as $code => $name)
                            <option value="{{ $code }}" {{ request('operator_code', $selectedOperator) == $code ? 'selected' : '' }}>
                                {{ $code }} - {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Generate Button --}}
                <div class="flex">
                    <button type="submit" class="px-5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wide rounded-md transition-colors shadow-sm h-fit inline-flex items-center gap-2">
                        <span class="material-icons-round text-sm">auto_graph</span>
                        Generate
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Chart Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h4 class="text-sm font-bold text-slate-800">Trend KPI Operator (%)</h4>
                <p class="text-[10px] text-slate-400 mt-0.5">
                    Klik legend di bawah chart untuk show/hide operator tertentu
                </p>
            </div>
            <span class="material-icons-round text-slate-400">show_chart</span>
        </div>

        @if(count($chartDatasets) > 0)
            <div id="chartWrapper" class="relative w-full" style="height: 480px;">
                <canvas id="operatorKpiChart"></canvas>
            </div>

            {{-- Summary Info --}}
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-xs text-slate-500">
                <div class="flex items-center gap-1.5">
                    <span class="material-icons-round text-sm text-blue-500">people</span>
                    <span><strong>{{ count($chartDatasets) }}</strong> operator ditampilkan</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="material-icons-round text-sm text-emerald-500">date_range</span>
                    <span><strong>{{ count($chartLabels) }}</strong> hari data</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="material-icons-round text-sm text-orange-500">horizontal_rule</span>
                    <span class="text-red-500 font-semibold">Garis merah putus-putus = Target KPI 90%</span>
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                <span class="material-icons-round text-5xl mb-3">analytics</span>
                <p class="font-medium">Belum ada data KPI</p>
                <p class="text-xs mt-1">Silakan pilih rentang tanggal lain atau pastikan data sudah di-generate.</p>
            </div>
        @endif
    </div>

@endsection

@push('scripts')
<script>
    // Initialize Select2
    $(document).ready(function() {
        $('.select2-search').select2({
            width: '100%',
            placeholder: 'Pilih Operator',
            allowClear: false
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        var startDateInput = document.getElementById('start_date');
        var endDateInput = document.getElementById('end_date');
        var filterForm = document.getElementById('filterForm');

        // Form validation
        filterForm.addEventListener('submit', function(e) {
            var start = new Date(startDateInput.value);
            var end = new Date(endDateInput.value);
            var diffTime = Math.abs(end - start);
            var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays > 31) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Batas Waktu', text: 'Rentang tanggal maksimal 31 hari.' });
                return;
            }
            if (end < start) {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: 'Tanggal Tidak Valid', text: 'Tanggal akhir harus lebih besar dari tanggal mulai.' });
                return;
            }
        });

        // ===== CHART =====
        try {
            var chartCanvas = document.getElementById('operatorKpiChart');
            if (!chartCanvas) { console.log('Canvas not found'); return; }
            if (typeof Chart === 'undefined') { throw new Error('Chart.js not loaded'); }

            var chartLabels = {!! json_encode($chartLabels) !!};
            var chartDatasets = {!! json_encode($chartDatasets) !!};
            var operatorNameMap = {!! json_encode($operatorNames) !!};

            console.log('Labels:', chartLabels.length, 'Datasets:', chartDatasets.length);

            // Map operator codes to names
            for (var i = 0; i < chartDatasets.length; i++) {
                var opName = operatorNameMap[chartDatasets[i].label];
                if (opName) {
                    chartDatasets[i].label = chartDatasets[i].label + ' - ' + opName;
                }
            }

            // Add 90% target reference line
            var targetData = [];
            for (var j = 0; j < chartLabels.length; j++) { targetData.push(90); }
            chartDatasets.push({
                label: 'Target KPI 90%',
                data: targetData,
                borderColor: 'rgba(239, 68, 68, 0.5)',
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [6, 4],
                pointRadius: 0,
                pointHoverRadius: 0,
                tension: 0,
                fill: false,
                order: 999
            });

            var ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: { labels: chartLabels, datasets: chartDatasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                padding: 12,
                                font: { size: 10, family: 'Inter' }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            filter: function(tooltipItem) {
                                return tooltipItem.dataset.label !== 'Target KPI 90%';
                            },
                            callbacks: {
                                label: function(context) {
                                    var lbl = context.dataset.label || '';
                                    if (context.parsed.y !== null) {
                                        lbl += ': ' + context.parsed.y.toFixed(1) + '%';
                                    }
                                    return lbl;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            suggestedMax: 150,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { size: 10, family: 'Inter' },
                                callback: function(value) { return value + '%'; }
                            },
                            border: { display: false }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 10, family: 'Inter' },
                                maxRotation: 45,
                                minRotation: 0
                            },
                            border: { display: false }
                        }
                    }
                }
            });
            console.log('Chart rendered OK');
        } catch(err) {
            console.error('Chart Error:', err);
            var wrapper = document.getElementById('chartWrapper');
            if (wrapper) {
                wrapper.innerHTML = '<div style="padding:20px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#991b1b;font-size:13px;">'
                    + '<strong>Chart Error:</strong> ' + err.message
                    + '<br><small>Tekan F12 untuk melihat detail error di Console.</small></div>';
            }
        }
    });
</script>
@endpush
