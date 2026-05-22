@extends('layouts.app')

@section('title', 'Laporan Analisis OEE')

@section('content')

    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Analisis OEE (Overall Equipment Effectiveness)</h1>
            <p class="text-xs text-gray-500">
                Laporan Ringkasan Harian: 
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

    {{-- HEADER CONTEXT BLOCK --}}
    <div class="mb-4 bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-[11px] text-gray-600 font-mono space-y-0.5 w-fit shadow-sm">
        <div>Departemen : {{ $departmentCode }}</div>
        <div>Scope      : {{ $selectedMachine ? 'Mesin ' . $selectedMachine : 'Semua Mesin' }}</div>
        <div>Rows       : {{ $rowCount }} hari ditemukan</div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r shadow-sm flex items-center text-xs">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r shadow-sm flex items-center text-xs">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- LIGHTWEIGHT KPI SUMMARY STRIP --}}
    @if(!empty($rows))
        @php
            $avgOee = $summary['oee'];
            if ($avgOee < 0.60) {
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
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[9px] font-bold text-blue-500 uppercase tracking-wider block mb-0.5">Avg OEE</span>
                    <span class="text-xl font-black text-blue-900">{{ number_format($avgOee * 100, 2) }}%</span>
                </div>
                <span class="mt-1 inline-block px-1.5 py-0.5 text-[9px] font-extrabold rounded-md w-fit {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </div>
            <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[9px] font-bold text-amber-500 uppercase tracking-wider block mb-0.5">Total Downtime</span>
                    <span class="text-xl font-black text-amber-900">{{ number_format($summary['downtime_hours'], 2) }} Jam</span>
                </div>
                <span class="mt-1 text-[9px] text-amber-600 font-medium">Batas OEE Limit</span>
            </div>
            <div class="bg-red-50 border border-red-100 rounded-xl p-3 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[9px] font-bold text-red-500 uppercase tracking-wider block mb-0.5">Total Reject</span>
                    <span class="text-xl font-black text-red-900">{{ number_format($summary['reject_qty']) }} Pcs</span>
                </div>
                <span class="mt-1 text-[9px] text-red-600 font-medium">Kualitas Output</span>
            </div>
            <div class="bg-green-50 border border-green-100 rounded-xl p-3 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[9px] font-bold text-green-500 uppercase tracking-wider block mb-0.5">Total Output</span>
                    <span class="text-xl font-black text-green-900">{{ number_format($summary['actual_qty']) }} Pcs</span>
                </div>
                <span class="mt-1 text-[9px] text-green-600 font-medium">Volume Produksi</span>
            </div>
        </div>
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
                            <th class="px-3 py-2">Tanggal</th>
                            <th class="px-3 py-2 text-right">Total Runtime Mesin (Jam)</th>
                            <th class="px-3 py-2 text-right">Downtime (Jam)</th>
                            <th class="px-3 py-2 text-right text-gray-400 font-normal">Target Qty</th>
                            <th class="px-3 py-2 text-right text-gray-400 font-normal">Aktual Qty</th>
                            <th class="px-3 py-2 text-right">Reject Qty</th>
                            <th class="px-3 py-2 text-center">Availability (%)</th>
                            <th class="px-3 py-2 text-center">Performance (%)</th>
                            <th class="px-3 py-2 text-center">Quality (%)</th>
                            <th class="px-3 py-2 text-center font-bold">OEE (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium">
                        @foreach ($rows as $row)
                            <tr class="odd:bg-white even:bg-gray-50 hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-3 py-2 text-gray-600 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-gray-600">
                                    {{ number_format($row['work_hours'], 2) }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-amber-700">
                                    {{ number_format($row['downtime_hours'], 2) }}
                                </td>
                                <td class="px-3 py-2 text-right text-gray-400 font-normal">
                                    {{ number_format($row['target_qty']) }}
                                </td>
                                <td class="px-3 py-2 text-right text-gray-400 font-normal">
                                    {{ number_format($row['actual_qty']) }}
                                </td>
                                <td class="px-3 py-2 text-right text-red-600 font-bold">
                                    {{ number_format($row['reject_qty']) }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700">
                                        {{ number_format($row['availability'] * 100, 2) }}%
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700">
                                        {{ number_format($row['performance'] * 100, 2) }}%
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700">
                                        {{ number_format($row['quality'] * 100, 2) }}%
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @php
                                        $oeeVal = $row['oee'];
                                        if ($oeeVal < 0.60) {
                                            $oeeClass = 'bg-red-100 text-red-700';
                                        } elseif ($oeeVal <= 0.85) {
                                            $oeeClass = 'bg-amber-100 text-amber-700';
                                        } else {
                                            $oeeClass = 'bg-green-100 text-green-700';
                                        }
                                    @endphp
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded text-[11px] font-bold {{ $oeeClass }}">
                                        {{ number_format($oeeVal * 100, 2) }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot class="bg-gray-100 border-t-2 border-gray-300 font-bold text-gray-800">
                        <tr>
                            <td class="px-3 py-3">TOTAL / RINGKASAN PERIODE</td>
                            <td class="px-3 py-3 text-right font-mono text-gray-700">
                                {{ number_format($summary['work_hours'], 2) }}
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-amber-700">
                                {{ number_format($summary['downtime_hours'], 2) }}
                            </td>
                            <td class="px-3 py-3 text-right text-gray-400 font-normal">
                                {{ number_format($summary['target_qty']) }}
                            </td>
                            <td class="px-3 py-3 text-right text-gray-400 font-normal">
                                {{ number_format($summary['actual_qty']) }}
                            </td>
                            <td class="px-3 py-3 text-right text-red-600 font-extrabold">
                                {{ number_format($summary['reject_qty']) }}
                            </td>
                            <td class="px-3 py-3 text-center bg-gray-50">
                                {{ number_format($summary['availability'] * 100, 2) }}%
                            </td>
                            <td class="px-3 py-3 text-center bg-gray-50">
                                {{ number_format($summary['performance'] * 100, 2) }}%
                            </td>
                            <td class="px-3 py-3 text-center bg-gray-50">
                                {{ number_format($summary['quality'] * 100, 2) }}%
                            </td>
                            <td class="px-3 py-3 text-center bg-blue-50 text-blue-900 font-extrabold whitespace-nowrap">
                                {{ number_format($avgOee * 100, 2) }}%
                                <span class="block text-[9px] font-extrabold uppercase mt-0.5 {{ $statusClass }} px-1.5 py-0.5 rounded">
                                    {{ $statusLabel }}
                                </span>
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
