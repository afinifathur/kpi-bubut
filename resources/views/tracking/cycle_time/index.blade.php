@extends('layouts.app')

@section('title', 'Cycle Time Report')

@section('content')

    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Cycle Time Report</h1>
            <p class="text-gray-500">
                Pencarian berdasarkan Barang: 
                <span class="font-semibold text-gray-700">
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                </span>
                s/d 
                <span class="font-semibold text-gray-700">
                    {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </span>
            </p>
        </div>

        @if($itemCode)
        <div class="flex gap-2 mt-4 md:mt-0">
            {{-- Export PDF --}}
            <a href="{{ route('tracking.cycle_time.pdf', ['start_date' => $startDate, 'end_date' => $endDate, 'item_code' => $itemCode]) }}" 
               target="_blank" 
               class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                PDF
            </a>
            
            {{-- Export Excel --}}
            <a href="{{ route('tracking.cycle_time.excel', ['start_date' => $startDate, 'end_date' => $endDate, 'item_code' => $itemCode]) }}" 
               target="_blank"
               class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Excel
            </a>
        </div>
        @endif
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

    {{-- Toolbar / Report Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6" x-data="itemSearchForm()">
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex flex-col items-start gap-4 rounded-t-xl">
            
            {{-- FILTER FORM --}}
            <form method="GET" id="filterForm" class="flex flex-col md:flex-row md:items-end gap-3 w-full">
                {{-- Start Date --}}
                <div class="w-full md:w-auto">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date', $startDate) }}"
                           class="block w-full shadow-sm text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1.5">
                </div>

                {{-- End Date --}}
                <div class="w-full md:w-auto">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date', $endDate) }}"
                           class="block w-full shadow-sm text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1.5">
                </div>

                {{-- Item Autocomplete --}}
                <div class="w-full md:w-1/3 relative" @click.outside="showItemSuggestions = false">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Cari Barang</label>
                    <div class="relative">
                        <input type="text" x-model="itemSearch" @input.debounce.300ms="searchItems"
                            placeholder="Ketik Kode/Nama Barang..."
                            class="block w-full shadow-sm text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1.5 pl-8"
                            autocomplete="off">
                        <span class="material-icons-round absolute left-2 top-2 text-gray-400 text-sm">search</span>
                        <input type="hidden" name="item_code" x-model="selectedItemCode" required>
                    </div>
                    
                    {{-- Item Suggestions Box --}}
                    <div x-show="showItemSuggestions && itemList.length > 0"
                        class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto"
                        style="display: none;">
                        <template x-for="item in itemList" :key="item.code">
                            <div @click="selectItem(item)"
                                class="p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-none">
                                <p class="text-sm font-bold text-gray-700" x-text="item.name"></p>
                                <p class="text-[10px] text-gray-400 font-mono" x-text="item.code"></p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex flex-col sm:flex-row pb-[2px] items-end gap-2 w-full md:w-auto mt-2 md:mt-0">
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button type="submit" class="w-full sm:w-auto px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wide rounded-md transition-colors shadow-sm h-[32px] inline-flex items-center justify-center">
                            Filter
                        </button>
                        @if($itemCode)
                            <a href="{{ route('tracking.cycle_time.index') }}" class="w-full sm:w-auto px-4 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-bold uppercase tracking-wide rounded-md transition-colors shadow-sm h-[32px] inline-flex items-center justify-center">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($itemCode)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Card: Nama Produk --}}
        <div class="bg-blue-50 p-4 rounded-xl shadow-sm border border-blue-100 flex items-center gap-4">
            <div class="bg-blue-100 p-2 rounded-lg text-blue-500 shrink-0">
                <span class="material-icons-round text-xl">inventory_2</span>
            </div>
            <div>
                <p class="text-[10px] text-blue-500 font-bold uppercase tracking-wide">Nama Produk</p>
                <h3 class="text-sm font-bold text-blue-800 leading-snug" title="{{ $selectedItem->name ?? 'Unknown item' }}">
                    {{ $selectedItem->name ?? 'Unknown item' }}
                </h3>
                <p class="text-[10px] text-blue-600 font-mono mt-0.5">{{ $itemCode }}</p>
            </div>
        </div>

        {{-- Card: Total Data --}}
        <div class="bg-indigo-50 p-4 rounded-xl shadow-sm border border-indigo-100 flex items-center gap-4">
            <div class="bg-indigo-100 p-2 rounded-lg text-indigo-500 shrink-0">
                <span class="material-icons-round text-xl">list_alt</span>
            </div>
            <div>
                <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-wide">Total Riwayat Input</p>
                <div class="flex items-baseline gap-1">
                    <h3 class="text-xl font-bold text-indigo-800">{{ number_format($totalData) }}</h3>
                    <span class="text-xs text-indigo-600 font-medium">Data</span>
                </div>
            </div>
        </div>

        {{-- Card: Rata-rata Cycle Time --}}
        <div class="bg-emerald-50 p-4 rounded-xl shadow-sm border border-emerald-100 flex items-center gap-4">
            <div class="bg-emerald-100 p-2 rounded-lg text-emerald-500 shrink-0">
                <span class="material-icons-round text-xl">timer</span>
            </div>
            <div>
                <p class="text-[10px] text-emerald-500 font-bold uppercase tracking-wide">Rata-rata Global</p>
                <div class="flex items-baseline gap-1">
                    @php
                        $avgSec = round($averageCycleTimeSec);
                        $avgMin = floor($avgSec / 60);
                        $avgRemSec = $avgSec % 60;
                    @endphp
                    <h3 class="text-xl font-bold text-emerald-800">{{ $avgMin }}<span class="text-sm text-emerald-600 font-medium">m</span> {{ $avgRemSec }}<span class="text-sm text-emerald-600 font-medium">s</span></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100 font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Tanggal Input</th>
                        <th class="px-6 py-3">Operator</th>
                        <th class="px-6 py-3">Mesin</th>
                        <th class="px-6 py-3 text-right">Hasil (PCS)</th>
                        <th class="px-6 py-3 text-right border-l border-gray-100">Cycle Time</th>
                        <th class="px-6 py-3 text-center">Status V.S Rata2</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        @php
                            $rowMins = floor($row->cycle_time_used_sec / 60);
                            $rowSecs = $row->cycle_time_used_sec % 60;
                            
                            // Anomaly check: e.g. > +25% or < -25% from average
                            $diffPercent = 0;
                            $isAnomalyHigh = false;
                            $isAnomalyLow = false;
                            if ($averageCycleTimeSec > 0) {
                                $diffPercent = (($row->cycle_time_used_sec - $averageCycleTimeSec) / $averageCycleTimeSec) * 100;
                                if ($diffPercent > 20) $isAnomalyHigh = true;
                                if ($diffPercent < -20) $isAnomalyLow = true;
                            }
                        @endphp
                        <tr class="odd:bg-white even:bg-gray-50 hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-6 py-4 font-medium text-gray-600 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($row->production_date)->format('d/m/Y') }}
                                <div class="text-xs text-gray-400">Shift {{ $row->shift }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-800">{{ $row->operator->name ?? $row->operator_code }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ $row->operator_code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-800">{{ $row->machine->name ?? $row->machine_code }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ $row->machine_code }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-800">
                                {{ $row->actual_qty }}
                            </td>
                            <td class="px-6 py-4 text-right border-l border-gray-100">
                                <span class="font-mono font-medium @if($isAnomalyHigh) text-red-600 @elseif($isAnomalyLow) text-amber-600 @else text-gray-700 @endif">
                                    {{ $rowMins }}m {{ $rowSecs }}s
                                </span>
                                <div class="text-[10px] text-gray-400 mt-1">
                                    {{ number_format($row->work_hours, 2) }} Jam
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($isAnomalyHigh)
                                    <span class="inline-flex items-center gap-1 justify-center px-2 py-1 rounded bg-red-100 text-red-700 text-[10px] font-bold uppercase tracking-wider">
                                        <span class="material-icons-round text-[12px]">trending_up</span>
                                        +{{ number_format($diffPercent, 1) }}% Tinggi
                                    </span>
                                @elseif($isAnomalyLow)
                                    <span class="inline-flex items-center gap-1 justify-center px-2 py-1 rounded bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wider">
                                        <span class="material-icons-round text-[12px]">trending_down</span>
                                        {{ number_format($diffPercent, 1) }}% Rendah
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center px-2 py-1 rounded bg-gray-100 text-gray-500 text-[10px] font-bold uppercase tracking-wider">
                                        Normal
                                    </span>
                                @endif

                                @if($row->remark)
                                    <div class="mt-2 text-[10px] text-gray-500 italic bg-gray-50 p-1.5 rounded border border-gray-100 text-left">
                                        <span class="font-bold text-gray-700">Ket:</span> {{ $row->remark }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 italic bg-gray-50">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                    <p>Data riwayat tidak ditemukan untuk barang dan rentang tanggal ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
        {{-- Empty State - No Item Selected --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center flex flex-col items-center justify-center">
            <span class="material-icons-round text-gray-300 text-6xl mb-4">search_off</span>
            <h3 class="text-xl font-bold text-gray-700 mb-2">Pilih Barang untuk Melacak History</h3>
            <p class="text-gray-500 max-w-sm">
                Silakan ketik nama barang atau kode barang pada kolom pencarian di atas untuk melihat rata-rata Cycle Time dan daftar input dari operator.
            </p>
        </div>
    @endif

@endsection

@push('scripts')
<script>
    function itemSearchForm() {
        return {
            itemSearch: '{!! addslashes($selectedItem->name ?? "") !!}',
            selectedItemCode: '{{ $itemCode ?? "" }}',
            itemList: [],
            showItemSuggestions: false,

            async searchItems() {
                if (this.itemSearch.length < 2) {
                    this.itemList = [];
                    return;
                }
                try {
                    const res = await fetch(`{{ route('api.search.items') }}?q=${encodeURIComponent(this.itemSearch)}`);
                    this.itemList = await res.json();
                    this.showItemSuggestions = true;
                } catch (e) {
                    console.error("Failed to fetch items", e);
                }
            },

            selectItem(item) {
                this.selectedItemCode = item.code;
                this.itemSearch = item.code + ' - ' + item.name;
                this.showItemSuggestions = false;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.getElementById('filterForm');
        
        filterForm.addEventListener('submit', function(e) {
            const startDateInput = document.getElementById('start_date').value;
            const endDateInput = document.getElementById('end_date').value;
            const itemCodeInput = document.querySelector('input[name="item_code"]').value;

            if (!itemCodeInput) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Barang',
                    text: 'Anda harus memilih barang terlebih dahulu dari daftar pencarian.',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }

            const start = new Date(startDateInput);
            const end = new Date(endDateInput);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            if (diffDays > 180) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Rentang Waktu Maksimal',
                    text: 'Rentang tanggal maksimal 180 hari.',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
        });
    });
</script>
@endpush
