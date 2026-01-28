@extends('layouts.app')

@section('title', 'Tracking Downtime')

@section('content')

    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tracking Downtime</h1>
            <p class="text-gray-500">
                Laporan Range: 
                <span class="font-semibold text-gray-700">
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                </span>
                s/d 
                <span class="font-semibold text-gray-700">
                    {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </span>
            </p>
        </div>
        
        <div class="flex gap-2">
            {{-- PDF Export --}}
            <a href="{{ route('downtime.tracking.pdf', request()->query()) }}" target="_blank" class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                PDF
            </a>
            <a href="{{ url('/export/downtime/'.$endDate) }}" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Excel
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Summary Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Ringkasan Downtime per Mesin</h3>
            <span class="text-xs font-medium bg-gray-200 text-gray-600 px-2 py-1 rounded-full">{{ $summary->count() }} Mesin</span>
        </div>
        
        <div class="p-4 bg-gray-50 border-b border-gray-100">
             {{-- FILTER FORM --}}
            <form method="GET" id="filterForm" class="flex flex-col md:flex-row md:items-end gap-3 w-full md:w-auto">
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

                {{-- Machine Dropdown --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Mesin</label>
                    <select name="machine_code" id="machine_code" class="select2-search block w-48 shadow-sm text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1.5">
                        <option value="all">Semua Mesin</option>
                        @foreach($machineNames as $code => $name)
                            <option value="{{ $code }}" {{ request('machine_code', $selectedMachine) == $code ? 'selected' : '' }}>
                                {{ $code }} - {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="flex">
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wide rounded-md transition-colors shadow-sm h-fit">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100 font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Mesin</th>
                        <th class="px-6 py-3 text-right">Total Downtime</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($summary as $row)
                    <tr class="odd:bg-white even:bg-gray-50 hover:bg-blue-50 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800">{{ $machineNames[$row->machine_code] ?? $row->machine_code }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ $row->machine_code }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ $row->total_minutes }} Menit
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-6 py-8 text-center text-gray-500 italic">
                            Tidak ada downtime tercatat.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detail Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Detail Aktivitas Downtime</h3>
            <span class="text-xs font-medium bg-gray-200 text-gray-600 px-2 py-1 rounded-full">{{ $list->count() }} Item</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100 font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Mesin</th>
                        <th class="px-6 py-3">Operator</th>
                        <th class="px-6 py-3 text-right">Durasi</th>
                        <th class="px-6 py-3">Catatan Masalah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($list as $row)
                    <tr class="odd:bg-white even:bg-gray-50 hover:bg-blue-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                             {{ \Carbon\Carbon::parse($row->downtime_date)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $row->machine->name ?? $row->machine_code }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ $row->machine_code }}</div>
                        </td>
                        <td class="px-6 py-4">
                             <div class="font-medium text-gray-800">{{ $row->operator->name ?? $row->operator_code }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ $row->operator_code }}</div>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-red-600">
                            {{ $row->duration_minutes }}m
                        </td>
                        <td class="px-6 py-4 text-gray-600 italic">
                            {{ $row->note ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">
                            Tidak ada detail downtime.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Initialize Select2
    $(document).ready(function() {
        $('.select2-search').select2({
            width: '100%',
            placeholder: 'Pilih Mesin',
            allowClear: false
        });

        // Re-bind change event for Select2
        $('#machine_code').on('change', function() {
            validateForm();
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const machineSelect = document.getElementById('machine_code');
        const filterForm = document.getElementById('filterForm');

        function validateForm() {
            const start = new Date(startDateInput.value);
            const end = new Date(endDateInput.value);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 

            // 1. Max Duration 45 Days
            if (diffDays > 45) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Batas Waktu',
                    text: 'Data yang bisa digenerate maksimal 45 hari. Silakan perkecil rentang tanggal.',
                });
                return false;
            }

            // 2. Multi-day Machine Constraint
            if (startDateInput.value !== endDateInput.value) {
                const allOption = machineSelect.querySelector('option[value="all"]');
                if (allOption) {
                    allOption.disabled = true;
                    if (machineSelect.value === 'all') {
                        machineSelect.value = ''; // Reset selection
                    }
                }
            } else {
                const allOption = machineSelect.querySelector('option[value="all"]');
                if (allOption) allOption.disabled = false;
            }

            return true;
        }

        // Real-time checks
        [startDateInput, endDateInput, machineSelect].forEach(input => {
            input.addEventListener('change', validateForm);
        });

        // Initial check
        validateForm();

        // Form Submit Intercept
        filterForm.addEventListener('submit', function(e) {
            const start = new Date(startDateInput.value);
            const end = new Date(endDateInput.value);
            const isMultiDay = startDateInput.value !== endDateInput.value;

            // Check max days
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            if (diffDays > 45) {
                e.preventDefault();
                Swal.fire('Error', 'Rentang tanggal maksimal 45 hari.', 'error');
                return;
            }

            // Check Machine
            if (isMultiDay && (!machineSelect.value || machineSelect.value === 'all')) {
                e.preventDefault();
                Swal.fire('Pilih Mesin', 'Untuk rentang lebih dari 1 hari, Anda WAJIB memilih 1 mesin spesifik.', 'warning');
                return;
            }
        });
    });
</script>
@endpush
