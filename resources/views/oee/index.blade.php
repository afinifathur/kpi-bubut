@extends('layouts.app')

@section('title', 'Laporan Analisis OEE')

@section('content')

    <div class="mb-2.5 flex flex-col lg:flex-row lg:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-bold text-gray-800 leading-tight">Analisis OEE (Overall Equipment Effectiveness)</h1>
            <p class="text-[11px] text-gray-500">
                Laporan Ringkasan Harian: 
                <span class="font-semibold text-gray-600">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</span> s/d <span class="font-semibold text-gray-600">{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</span>
            </p>
        </div>
        {{-- HEADER CONTEXT BLOCK --}}
        <div class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1 text-[11px] text-gray-600 font-mono shadow-sm h-fit">
            <span><strong>Departemen:</strong> {{ $departmentCode }}</span>
            <span class="text-gray-300">|</span>
            <span><strong>Scope:</strong> {{ $selectedMachine ? 'Mesin ' . $selectedMachine : 'Semua Mesin' }}</span>
            <span class="text-gray-300">|</span>
            <span><strong>Rows:</strong> {{ $rowCount }} Hari</span>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-2.5 p-2 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r shadow-sm flex items-center text-[11px]">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-2.5 p-2 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r shadow-sm flex items-center text-[11px]">
            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- LIGHTWEIGHT KPI SUMMARY STRIP --}}
    @if(!empty($rows))
        @php
            $avgOee = $summary['oee'];
            if ($avgOee === null) {
                $statusLabel = 'N/A';
                $statusClass = 'bg-gray-100 text-gray-500 border border-gray-200';
            } elseif ($avgOee < 0.60) {
                $statusLabel = 'Critical';
                $statusClass = 'bg-red-100 text-red-700 border border-red-200';
            } elseif ($avgOee <= 0.85) {
                $statusLabel = 'Fair';
                $statusClass = 'bg-amber-100 text-amber-700 border border-amber-200';
            } else {
                $statusLabel = 'Excellent';
                $statusClass = 'bg-green-100 text-green-700 border border-green-200';
            }
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 mb-2.5">
            {{-- Card 1 (Avg OEE - Blue) --}}
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-2.5 shadow-sm flex items-center justify-between h-[70px]">
                <div>
                    <span class="text-[9px] font-bold text-blue-500 uppercase tracking-wider block leading-none mb-1">Avg OEE</span>
                    <span class="text-lg font-black text-blue-900 leading-none">
                        @if($avgOee !== null)
                            {{ number_format($avgOee * 100, 2) }}%
                        @else
                            -
                        @endif
                    </span>
                </div>
                <span class="px-1.5 py-0.5 text-[9px] font-extrabold rounded {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </div>

            {{-- Card 2 (Total Capacity - Purple/Indigo) --}}
            <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-2.5 shadow-sm flex flex-col justify-center h-[70px]">
                <span class="text-[9px] font-bold text-indigo-500 uppercase tracking-wider block leading-none mb-1">Total Kapasitas</span>
                <span class="text-lg font-black text-indigo-900 leading-none">{{ number_format($summary['planned_capacity'], 2) }} Jam</span>
            </div>

            {{-- Card 3 (Total Downtime - Orange/Amber) --}}
            <div class="bg-amber-50 border border-amber-100 rounded-lg p-2.5 shadow-sm flex flex-col justify-center h-[70px]">
                <span class="text-[9px] font-bold text-amber-500 uppercase tracking-wider block leading-none mb-1">Total Downtime</span>
                <span class="text-lg font-black text-amber-900 leading-none">{{ number_format($summary['downtime_hours'], 2) }} Jam</span>
            </div>

            {{-- Card 4 (Total Reject - Red) --}}
            <div class="bg-red-50 border border-red-100 rounded-lg p-2.5 shadow-sm flex flex-col justify-center h-[70px]">
                <span class="text-[9px] font-bold text-red-500 uppercase tracking-wider block leading-none mb-1">Total Reject</span>
                <span class="text-lg font-black text-red-900 leading-none">{{ number_format($summary['reject_qty']) }} Pcs</span>
            </div>

            {{-- Card 5 (Total Output - Green) --}}
            <div class="bg-green-50 border border-green-100 rounded-lg p-2.5 shadow-sm flex flex-col justify-center h-[70px]">
                <span class="text-[9px] font-bold text-green-500 uppercase tracking-wider block leading-none mb-1">Total Output</span>
                <span class="text-lg font-black text-green-900 leading-none">{{ number_format($summary['actual_qty']) }} Pcs</span>
            </div>
        </div>

        {{-- ================================================================
             DOWNTIME ANALYSIS CARD — INFORMATIONAL ONLY
             Explains why Downtime OEE ≠ sum of Top Downtime Reasons.
             No calculation is changed here.
             ================================================================ --}}
        @php
            $oeeDowntime    = $summary['downtime_hours'];
            $actualDowntime = $rawDowntimeHours;
            $orphanDowntime = max(0.0, $actualDowntime - $oeeDowntime);
            $hasGap         = $orphanDowntime > 0.005; // float-safe epsilon
        @endphp
        <div class="mb-2.5 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            {{-- Header strip --}}
            <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 border-b border-gray-100">
                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-[10px] font-bold text-gray-600 uppercase tracking-wider">Analisis Downtime</span>
                <span class="ml-auto text-[9px] text-gray-400 font-medium italic">Informasi cakupan — tidak mempengaruhi perhitungan OEE</span>
            </div>

            {{-- Three value columns --}}
            <div class="grid grid-cols-3 divide-x divide-gray-100 px-0">

                {{-- 1. Downtime OEE --}}
                <div class="flex flex-col justify-center px-4 py-2.5">
                    <span class="text-[9px] font-bold text-amber-600 uppercase tracking-wider leading-none mb-1">
                        Downtime OEE
                    </span>
                    <span class="text-base font-black text-amber-900 leading-none tabular-nums">
                        {{ number_format($oeeDowntime, 2) }} Jam
                    </span>
                    <span class="text-[9px] text-gray-400 mt-1 leading-snug">
                        Digunakan dalam perhitungan Availability
                    </span>
                </div>

                {{-- 2. Downtime Aktual --}}
                <div class="flex flex-col justify-center px-4 py-2.5">
                    <span class="text-[9px] font-bold text-slate-600 uppercase tracking-wider leading-none mb-1">
                        Downtime Aktual
                    </span>
                    <span class="text-base font-black text-slate-800 leading-none tabular-nums">
                        {{ number_format($actualDowntime, 2) }} Jam
                    </span>
                    <span class="text-[9px] text-gray-400 mt-1 leading-snug">
                        Total seluruh catatan downtime pada periode ini
                    </span>
                </div>

                {{-- 3. Di Luar Produksi --}}
                <div class="flex flex-col justify-center px-4 py-2.5 {{ $hasGap ? 'bg-orange-50' : 'bg-green-50' }}">
                    <span class="text-[9px] font-bold {{ $hasGap ? 'text-orange-600' : 'text-green-600' }} uppercase tracking-wider leading-none mb-1">
                        Di Luar Produksi
                    </span>
                    <span class="text-base font-black {{ $hasGap ? 'text-orange-800' : 'text-green-700' }} leading-none tabular-nums">
                        {{ number_format($orphanDowntime, 2) }} Jam
                    </span>
                    <span class="text-[9px] {{ $hasGap ? 'text-orange-400' : 'text-green-400' }} mt-1 leading-snug">
                        @if($hasGap)
                            Downtime tanpa catatan produksi pada hari &amp; mesin yang sama
                        @else
                            Semua downtime tercakup dalam lingkup OEE
                        @endif
                    </span>
                </div>

            </div>

            {{-- Contextual footnote — only shown when there is a gap --}}
            @if($hasGap)
                <div class="flex items-start gap-2 px-3 py-1.5 bg-orange-50 border-t border-orange-100">
                    <svg class="w-3 h-3 text-orange-400 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                              clip-rule="evenodd"/>
                    </svg>
                    <p class="text-[9px] text-orange-600 leading-relaxed">
                        <strong>Catatan:</strong>
                        Selisih <strong>{{ number_format($orphanDowntime, 2) }} Jam</strong> merupakan downtime yang tercatat
                        namun tidak memiliki entri produksi pada hari &amp; mesin yang sama.
                        Downtime ini sah dan terdokumentasi, tetapi berada di luar cakupan perhitungan Availability OEE.
                        Nilai OEE, Availability, Performance, dan Quality tidak terpengaruh.
                    </p>
                </div>
            @endif
        </div>
    @endif

    {{-- COLLAPSIBLE VALIDATION PANEL FOR MR/DIREKTUR/ADMIN --}}
    @if(!empty($rows) && in_array(strtolower(auth()->user()->role), ['mr', 'direktur', 'admin']))
        <details class="mb-2.5 bg-gradient-to-r from-slate-800 to-slate-900 border border-slate-700 rounded-lg shadow-sm text-white group overflow-hidden">
            <summary class="flex items-center justify-between p-2 cursor-pointer select-none font-semibold text-xs tracking-wider uppercase">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 bg-yellow-500/20 text-yellow-500 rounded text-[9px] font-bold">
                        VALIDATION PANEL
                    </span>
                    <span class="text-slate-400 font-medium normal-case text-[10px]">(Click to Expand/Collapse)</span>
                </div>
                <div class="flex items-center gap-1 text-[10px] text-slate-400 font-mono">
                    <span class="group-open:hidden">▼ Show</span>
                    <span class="hidden group-open:inline">▲ Hide</span>
                </div>
            </summary>
            
            <div class="p-3 pt-0 border-t border-slate-700/60">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 mt-2.5">
                    <div class="bg-slate-700/30 border border-slate-700/50 rounded p-2">
                        <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Total Planned Capacity</span>
                        <span class="text-sm font-mono font-bold text-white">{{ number_format($summary['planned_capacity'], 2) }} Jam</span>
                    </div>
                    <div class="bg-slate-700/30 border border-slate-700/50 rounded p-2">
                        <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Total Runtime</span>
                        <span class="text-sm font-mono font-bold text-white">{{ number_format($summary['runtime'], 2) }} Jam</span>
                    </div>
                    <div class="bg-slate-700/30 border border-slate-700/50 rounded p-2">
                        <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Availability V2</span>
                        <span class="text-sm font-mono font-bold text-white">
                            {{ $summary['availability'] !== null ? number_format($summary['availability'] * 100, 2) . '%' : '-' }}
                        </span>
                    </div>
                </div>

                {{-- ANOMALY DETECTION AREA --}}
                @php
                    $anomalies = [];
                    foreach($rows as $row) {
                        $rowRuntime = max(0.0, $row['work_hours'] - $row['downtime_hours']);
                        $rowCapacity = $row['planned_capacity'];
                        $formattedDate = \Carbon\Carbon::parse($row['date'])->format('d/m/Y');

                        if ($rowRuntime > $rowCapacity) {
                            $anomalies[] = [
                                'type' => 'CASE A',
                                'message' => "Tanggal {$formattedDate}: Runtime ({$rowRuntime} Jam) melebihi Planned Capacity ({$rowCapacity} Jam)."
                            ];
                        }
                        if ($row['availability'] > 1.0) {
                            $anomalies[] = [
                                'type' => 'CASE B',
                                'message' => "Tanggal {$formattedDate}: Availability (" . number_format($row['availability'] * 100, 2) . "%) melebihi 100%."
                            ];
                        }
                        if ($rowCapacity == 0 && ($row['actual_qty'] > 0 || $row['work_hours'] > 0)) {
                            $anomalies[] = [
                                'type' => 'CASE C',
                                'message' => "Tanggal {$formattedDate}: Planned Capacity = 0, tetapi ada catatan produksi (Work Hours: {$row['work_hours']} Jam, Qty: {$row['actual_qty']} Pcs)."
                            ];
                        }
                    }
                @endphp

                <div class="mt-2.5 border-t border-slate-700/60 pt-2">
                    <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-1">Hasil Deteksi Anomali</span>
                    @if(count($anomalies) > 0)
                        <div class="space-y-1">
                            @foreach($anomalies as $anomaly)
                                <div class="flex items-start gap-1.5 text-xs text-red-400">
                                    <span class="inline-block px-1 py-0.5 bg-red-500/20 text-red-400 font-bold text-[8px] rounded font-mono uppercase mt-0.5">
                                        {{ $anomaly['type'] }}
                                    </span>
                                    <span class="text-[11px]">{{ $anomaly['message'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center gap-1.5 text-[11px] text-emerald-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Tidak terdeteksi anomali pada periode ini (Sesuai parameter validasi).</span>
                        </div>
                    @endif
                </div>
            </div>
        </details>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
        {{-- Toolbar / Report Filters --}}
        <div class="p-3 border-b border-gray-100 bg-gray-50 flex flex-col md:flex-row gap-3 items-end justify-between">
            
            {{-- FILTER FORM --}}
            <form method="GET" id="filterForm" class="flex flex-col md:flex-row md:items-end gap-2 w-full md:w-auto">
                {{-- Start Date --}}
                <div>
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date', $startDate) }}"
                           class="block w-full shadow-sm text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1 px-2">
                </div>

                {{-- End Date --}}
                <div>
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date', $endDate) }}"
                           class="block w-full shadow-sm text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1 px-2">
                </div>

                {{-- Machine Dropdown --}}
                <div>
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">Mesin</label>
                    <select name="machine_code" id="machine_code" class="block w-40 shadow-sm text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1 px-2">
                        <option value="all" {{ request('machine_code', $selectedMachine) == null ? 'selected' : '' }}>Semua Mesin</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->code }}" {{ request('machine_code', $selectedMachine) == $machine->code ? 'selected' : '' }}>
                                {{ $machine->code }} - {{ $machine->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold uppercase tracking-wide rounded transition-colors shadow-sm h-fit">
                        Filter
                    </button>
                    
                    @if(!empty($rows))
                        <a href="{{ route('oee.export.excel', request()->all()) }}" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-[10px] font-bold uppercase tracking-wide rounded transition-colors shadow-sm h-fit decoration-none inline-block">
                            Excel
                        </a>
                        <a href="{{ route('oee.export.pdf', request()->all()) }}" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold uppercase tracking-wide rounded transition-colors shadow-sm h-fit decoration-none inline-block">
                            PDF
                        </a>
                    @endif

                    {{-- Governance Notice --}}
                    <span class="text-[10px] text-gray-400 font-medium ml-1">
                        Maksimal generate laporan OEE adalah 45 hari per proses.
                    </span>
                </div>
            </form>
        </div>

        {{-- Table --}}
        @if(!empty($rows))
            <div class="w-full overflow-x-auto">
                <table class="min-w-full text-left text-[13px]">
                    <thead class="text-[11px] text-gray-500 uppercase bg-gray-50 border-b border-gray-100 font-semibold tracking-wider">
                        <tr>
                            <th class="px-2 py-1.5">Tanggal</th>
                            <th class="px-2 py-1.5 text-right">Planned Capacity (Jam)</th>
                            <th class="px-2 py-1.5 text-right">Work Hours (Jam)</th>
                            <th class="px-2 py-1.5 text-right">Downtime (Jam)</th>
                            <th class="px-2 py-1.5 text-right text-gray-400 font-normal">Target Qty</th>
                            <th class="px-2 py-1.5 text-right text-gray-400 font-normal">Aktual Qty</th>
                            <th class="px-2 py-1.5 text-right">Reject Qty</th>
                            <th class="px-2 py-1.5 text-center">Availability (%)</th>
                            <th class="px-2 py-1.5 text-center">Performance (%)</th>
                            <th class="px-2 py-1.5 text-center">Quality (%)</th>
                            <th class="px-2 py-1.5 text-center font-bold">OEE (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium">
                        @foreach ($rows as $row)
                            @php
                                $isHoliday = $row['planned_capacity'] == 0;
                                $rowClass = $isHoliday 
                                    ? 'bg-gray-100 text-gray-400 hover:bg-gray-200' 
                                    : 'odd:bg-white even:bg-gray-50 hover:bg-blue-50 text-gray-600';
                            @endphp
                            <tr class="{{ $rowClass }} transition-colors duration-150">
                                <td class="px-2 py-1.5 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}
                                </td>
                                <td class="px-2 py-1.5 text-right font-mono">
                                    {{ number_format($row['planned_capacity'], 2) }}
                                </td>
                                <td class="px-2 py-1.5 text-right font-mono">
                                    {{ number_format($row['work_hours'], 2) }}
                                </td>
                                <td class="px-2 py-1.5 text-right font-mono">
                                    {{ number_format($row['downtime_hours'], 2) }}
                                </td>
                                <td class="px-2 py-1.5 text-right text-gray-400 font-normal">
                                    {{ number_format($row['target_qty']) }}
                                </td>
                                <td class="px-2 py-1.5 text-right text-gray-400 font-normal">
                                    {{ number_format($row['actual_qty']) }}
                                </td>
                                <td class="px-2 py-1.5 text-right text-red-600 font-bold">
                                    {{ number_format($row['reject_qty']) }}
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700">
                                        {{ $row['availability'] !== null ? number_format($row['availability'] * 100, 2) . '%' : '-' }}
                                    </span>
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700">
                                        {{ $row['performance'] !== null ? number_format($row['performance'] * 100, 2) . '%' : '-' }}
                                    </span>
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700">
                                        {{ $row['quality'] !== null ? number_format($row['quality'] * 100, 2) . '%' : '-' }}
                                    </span>
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    @php
                                        $oeeVal = $row['oee'];
                                        if ($oeeVal === null) {
                                            $oeeClass = 'bg-gray-100 text-gray-500';
                                            $oeeText = '-';
                                        } else {
                                            if ($oeeVal < 0.60) {
                                                $oeeClass = 'bg-red-100 text-red-700';
                                            } elseif ($oeeVal <= 0.85) {
                                                $oeeClass = 'bg-amber-100 text-amber-700';
                                            } else {
                                                $oeeClass = 'bg-green-100 text-green-700';
                                            }
                                            $oeeText = number_format($oeeVal * 100, 2) . '%';
                                        }
                                    @endphp
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded text-[11px] font-bold {{ $oeeClass }}">
                                        {{ $oeeText }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
 
                    <tfoot class="bg-gray-100 border-t-2 border-gray-300 font-bold text-gray-800">
                        <tr>
                            <td class="px-2 py-1.5">TOTAL / RINGKASAN PERIODE</td>
                            <td class="px-2 py-1.5 text-right font-mono text-gray-700">
                                {{ number_format($summary['planned_capacity'], 2) }}
                            </td>
                            <td class="px-2 py-1.5 text-right font-mono text-gray-700">
                                {{ number_format($summary['work_hours'], 2) }}
                            </td>
                            <td class="px-2 py-1.5 text-right font-mono text-amber-700">
                                {{ number_format($summary['downtime_hours'], 2) }}
                            </td>
                            <td class="px-2 py-1.5 text-right text-gray-400 font-normal">
                                {{ number_format($summary['target_qty']) }}
                            </td>
                            <td class="px-2 py-1.5 text-right text-gray-400 font-normal">
                                {{ number_format($summary['actual_qty']) }}
                            </td>
                            <td class="px-2 py-1.5 text-right text-red-600 font-extrabold">
                                {{ number_format($summary['reject_qty']) }}
                            </td>
                            <td class="px-2 py-1.5 text-center bg-gray-50">
                                {{ $summary['availability'] !== null ? number_format($summary['availability'] * 100, 2) . '%' : '-' }}
                            </td>
                            <td class="px-2 py-1.5 text-center bg-gray-50">
                                {{ $summary['performance'] !== null ? number_format($summary['performance'] * 100, 2) . '%' : '-' }}
                            </td>
                            <td class="px-2 py-1.5 text-center bg-gray-50">
                                {{ $summary['quality'] !== null ? number_format($summary['quality'] * 100, 2) . '%' : '-' }}
                            </td>
                            <td class="px-2 py-1.5 text-center bg-blue-50 text-blue-900 font-extrabold whitespace-nowrap">
                                @if($summary['oee'] !== null)
                                    {{ number_format($summary['oee'] * 100, 2) }}%
                                    <span class="block text-[9px] font-extrabold uppercase mt-0.5 {{ $statusClass }} px-1.5 py-0.5 rounded">
                                        {{ $statusLabel }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            {{-- TASK 6: EMPTY STATE --}}
            <div class="p-8 text-center text-gray-500">
                <div class="flex flex-col items-center justify-center">
                    <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <p class="font-semibold text-gray-700 text-sm">Tidak ada data OEE pada periode ini.</p>
                    <p class="text-xs text-gray-400 mt-1">Silakan sesuaikan filter tanggal atau mesin di atas.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- ACTIONABLE INSIGHTS SECTION --}}
    @if(!empty($rows))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Top Downtime reasons --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800 flex items-center text-xs">
                        <span class="material-icons-round text-base mr-1.5 text-amber-500">warning</span>
                        Top Penyebab Downtime
                    </h3>
                </div>
                <div class="p-3">
                    <div class="w-full overflow-x-auto">
                        <table class="min-w-full text-left text-[13px]">
                            <thead class="text-[11px] text-gray-500 uppercase bg-gray-50 border-b border-gray-100 font-semibold tracking-wider">
                                <tr>
                                    <th class="px-3 py-1.5">Alasan Downtime</th>
                                    <th class="px-3 py-1.5 text-right">Total Durasi</th>
                                    <th class="px-3 py-1.5 text-right">Frekuensi</th>
                                    <th class="px-3 py-1.5 text-right">Kontribusi (%)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-medium">
                                @forelse($topDowntimeReasons as $reason)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-700">{{ $reason['reason'] }}</td>
                                        <td class="px-3 py-2 text-right font-mono text-gray-600 font-bold">{{ number_format($reason['hours'], 2) }} Jam</td>
                                        <td class="px-3 py-2 text-right text-gray-600 font-normal">{{ $reason['count'] }} kejadian</td>
                                        <td class="px-3 py-2 text-right font-mono text-amber-700 font-semibold">{{ number_format($reason['contribution'], 2) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-3 text-center text-gray-400 italic bg-gray-50">Tidak ada catatan downtime.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Top Reject reasons --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800 flex items-center text-xs">
                        <span class="material-icons-round text-base mr-1.5 text-red-500">delete_outline</span>
                        Top Penyebab Reject
                    </h3>
                </div>
                <div class="p-3">
                    <div class="w-full overflow-x-auto">
                        <table class="min-w-full text-left text-[13px]">
                            <thead class="text-[11px] text-gray-500 uppercase bg-gray-50 border-b border-gray-100 font-semibold tracking-wider">
                                <tr>
                                    <th class="px-3 py-1.5">Alasan Reject</th>
                                    <th class="px-3 py-1.5 text-right">Total Qty</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-medium">
                                @forelse($topRejectReasons as $reason)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-700">{{ $reason['reason'] }}</td>
                                        <td class="px-3 py-2 text-right font-mono text-red-600 font-bold">{{ number_format($reason['qty']) }} Pcs</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-3 py-3 text-center text-gray-400 italic bg-gray-50">Tidak ada catatan reject.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const filterForm = document.getElementById('filterForm');

        function checkDateSpan() {
            const start = new Date(startDateInput.value);
            const end = new Date(endDateInput.value);

            if (isNaN(start.getTime()) || isNaN(end.getTime())) {
                return false;
            }

            if (end < start) {
                return false;
            }

            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; 

            if (diffDays > 45) {
                alert('Batas Waktu: Rentang tanggal maksimal OEE yang diperbolehkan adalah 45 hari (inklusif).');
                return false;
            }
            return true;
        }

        [startDateInput, endDateInput].forEach(input => {
            input.addEventListener('change', checkDateSpan);
        });

        filterForm.addEventListener('submit', function(e) {
            const start = new Date(startDateInput.value);
            const end = new Date(endDateInput.value);

            if (isNaN(start.getTime()) || isNaN(end.getTime())) {
                e.preventDefault();
                alert('Silakan isi tanggal mulai dan tanggal selesai dengan benar.');
                return;
            }

            if (end < start) {
                e.preventDefault();
                alert('Tanggal selesai tidak boleh mendahului tanggal mulai.');
                return;
            }

            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; 
            if (diffDays > 45) {
                e.preventDefault();
                alert('Rentang tanggal maksimal OEE yang diperbolehkan adalah 45 hari (inklusif).');
                return;
            }
        });
    });
</script>
@endpush
