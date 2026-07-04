@extends('layouts.app')

@section('title', 'Operator Leaderboard')

@section('content')

    @php
        // Helper function to get styling classes based on KPI value
        if (!function_exists('getKpiClass')) {
            function getKpiClass($kpi) {
                if ($kpi === null) {
                    return 'bg-slate-100 text-slate-400';
                }
                if ($kpi >= 95) {
                    return 'bg-emerald-700 text-white font-bold';
                }
                if ($kpi >= 90) {
                    return 'bg-emerald-500 text-white font-semibold';
                }
                if ($kpi >= 80) {
                    return 'bg-amber-400 text-amber-950 font-semibold';
                }
                if ($kpi >= 70) {
                    return 'bg-orange-500 text-white font-semibold';
                }
                return 'bg-rose-600 text-white font-bold';
            }
        }
    @endphp

    {{-- Page Header --}}
    <div class="mb-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Operator Leaderboard</h1>
            <p class="text-xs text-gray-500 mt-1">
                Pilot Deployment V1.0: Peringkat kinerja operator berdasarkan rata-rata KPI harian.
            </p>
        </div>
        <div class="flex items-center gap-2 bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg border border-blue-100 text-xs font-semibold">
            <span class="material-icons-round text-sm">emoji_events</span>
            <span>Bubut Department (404.1)</span>
        </div>
    </div>

    {{-- Flash & Validation Error Messages --}}
    @if(session('error'))
        <div class="mb-4 p-3 bg-rose-50 border-l-4 border-rose-500 text-rose-700 rounded-r shadow-sm flex items-center text-sm">
            <span class="material-icons-round text-lg mr-2">error_outline</span>
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-5">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <form method="GET" id="filterForm" class="flex flex-col md:flex-row md:items-end gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date', $startDate) }}"
                           class="block w-full shadow-sm text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1.5">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date', $endDate) }}"
                           class="block w-full shadow-sm text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1.5">
                </div>
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
                <div class="flex">
                    <button type="submit" class="px-5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wide rounded-md transition-colors shadow-sm h-fit inline-flex items-center gap-1.5">
                        <span class="material-icons-round text-sm">filter_alt</span>
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Grid / Table Container --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden p-3">
        
        {{-- Color Legend Helper --}}
        <div class="flex flex-wrap gap-3 items-center justify-between border-b border-slate-100 pb-3 mb-3 text-xs">
            <span class="text-slate-500 font-medium">Legend KPI:</span>
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] bg-emerald-700 text-white font-semibold">&ge;95%</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] bg-emerald-500 text-white font-semibold">90-94%</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] bg-amber-400 text-amber-950 font-semibold">80-89%</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] bg-orange-500 text-white font-semibold">70-79%</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] bg-rose-600 text-white font-semibold">&lt;70%</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-400 font-normal">No activity (-)</span>
            </div>
        </div>

        @if(count($leaderboardData) > 0)
            <div class="leaderboard-container w-full border border-slate-200 rounded-lg">
                <table class="w-full text-left text-xs table-sticky border-collapse">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="col-rank px-2 py-2 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Rank</th>
                            <th class="col-operator px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Operator</th>
                            <th class="col-kpi px-2 py-2 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Avg KPI</th>
                            <th class="col-days px-2 py-2 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Days</th>
                            
                            {{-- Generate Column Headers for date range --}}
                            @foreach($dates as $date)
                                <th class="matrix-cell px-0 py-2 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider hover:bg-slate-100 cursor-help" 
                                    title="{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y (l)') }}">
                                    {{ \Carbon\Carbon::parse($date)->format('d') }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($leaderboardData as $index => $row)
                            <tr class="hover:bg-slate-50 group">
                                {{-- 1. Rank --}}
                                <td class="col-rank px-2 py-2 text-center font-bold text-slate-700">
                                    @if($index === 0)
                                        <span class="text-base" title="Rank 1">🥇</span>
                                    @elseif($index === 1)
                                        <span class="text-base" title="Rank 2">🥈</span>
                                    @elseif($index === 2)
                                        <span class="text-base" title="Rank 3">🥉</span>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>

                                {{-- 2. Operator Details --}}
                                <td class="col-operator px-3 py-2 truncate" title="{{ $row['operator_code'] }} - {{ $row['operator_name'] }}">
                                    <span class="font-semibold text-slate-900 block truncate">{{ $row['operator_name'] }}</span>
                                    <span class="text-[9px] text-slate-400 block tracking-tight font-mono">{{ $row['operator_code'] }}</span>
                                </td>

                                {{-- 3. Average KPI --}}
                                <td class="col-kpi px-2 py-2 text-center font-bold text-slate-900 bg-slate-50/50">
                                    {{ number_format($row['average_kpi'], 1) }}%
                                </td>

                                {{-- 4. Working Days --}}
                                <td class="col-days px-2 py-2 text-center text-slate-600 bg-slate-50/50">
                                    {{ $row['working_days'] }}
                                </td>

                                {{-- 5. Daily Matrix Cells --}}
                                @foreach($dates as $date)
                                    @php
                                        $val = $row['matrix'][$date];
                                    @endphp
                                    <td class="matrix-cell {{ getKpiClass($val) }}" 
                                        title="{{ $row['operator_name'] }} | {{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }} | KPI: {{ $val !== null ? number_format($val, 1) . '%' : '-' }}">
                                        {{ $val !== null ? round($val) : '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3 flex justify-between items-center text-[10px] text-slate-400">
                <div class="flex items-center gap-1">
                    <span class="material-icons-round text-sm">groups</span>
                    <span>Total: <strong>{{ count($leaderboardData) }}</strong> Operator</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="material-icons-round text-sm">calendar_month</span>
                    <span>Rentang: <strong>{{ count($dates) }}</strong> Hari</span>
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-slate-400 bg-slate-50 rounded-lg border border-dashed border-slate-200">
                <span class="material-icons-round text-5xl mb-3 text-slate-300">workspace_premium</span>
                <p class="font-medium text-sm">Belum ada data untuk ditampilkan</p>
                <p class="text-xs mt-1">Silakan sesuaikan filter tanggal atau pilih operator lain.</p>
            </div>
        @endif

    </div>

@endsection

@push('styles')
    <style>
        /* Container to enable concurrent scrolling and viewport limits with page-level flex containment */
        .leaderboard-container {
            overflow: auto;
            max-height: calc(100vh - 250px);
            position: relative;
            width: 0;
            min-width: 100%;
        }

        /* Enforce sticky headers */
        .table-sticky th {
            position: sticky;
            top: 0;
            z-index: 30;
            background-color: #f8fafc;
            border-bottom: 2px solid #cbd5e1;
        }

        /* Sticky Columns Offset Map */
        .col-rank {
            position: sticky;
            left: 0px;
            z-index: 10;
            width: 50px;
            min-width: 50px;
            max-width: 50px;
        }

        .col-operator {
            position: sticky;
            left: 50px;
            z-index: 10;
            width: 160px;
            min-width: 160px;
            max-width: 160px;
        }

        .col-kpi {
            position: sticky;
            left: 210px;
            z-index: 10;
            width: 80px;
            min-width: 80px;
            max-width: 80px;
        }

        .col-days {
            position: sticky;
            left: 290px;
            z-index: 10;
            width: 70px;
            min-width: 70px;
            max-width: 70px;
            border-right: 3px double #cbd5e1 !important;
        }

        /* Sticky elements intersection higher priority */
        th.col-rank { z-index: 40; }
        th.col-operator { z-index: 40; }
        th.col-kpi { z-index: 40; }
        th.col-days { z-index: 40; }

        /* Guarantee background colors are non-transparent to hide scroll-under text */
        .table-sticky td.col-rank,
        .table-sticky td.col-operator,
        .table-sticky td.col-kpi,
        .table-sticky td.col-days {
            background-color: #ffffff;
        }

        /* Hover row background color override including sticky cells */
        .table-sticky tr:hover td.col-rank,
        .table-sticky tr:hover td.col-operator,
        .table-sticky tr:hover td.col-kpi,
        .table-sticky tr:hover td.col-days {
            background-color: #f8fafc !important;
        }

        /* High-density grid cell definition */
        .matrix-cell {
            width: 44px;
            min-width: 44px;
            max-width: 44px;
            text-align: center;
            font-size: 11px;
            padding: 6px 0;
            border-right: 1px solid #f1f5f9;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Select2 dropdown with search capability
            $('.select2-search').select2({
                width: '100%',
                placeholder: 'Pilih Operator',
                allowClear: false
            });

            // Handle date validations before form submission
            var filterForm = document.getElementById('filterForm');
            filterForm.addEventListener('submit', function (e) {
                var startVal = document.getElementById('start_date').value;
                var endVal = document.getElementById('end_date').value;
                
                if (!startVal || !endVal) return;

                var start = new Date(startVal);
                var end = new Date(endVal);
                
                if (end < start) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Tanggal Tidak Valid',
                        text: 'Tanggal akhir harus lebih besar atau sama dengan tanggal mulai.'
                    });
                    return;
                }

                var diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24));
                if (diffDays > 366) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Batas Rentang Tanggal',
                        text: 'Rentang tanggal maksimal yang diperbolehkan adalah 366 hari.'
                    });
                    return;
                }
            });
        });
    </script>
@endpush
