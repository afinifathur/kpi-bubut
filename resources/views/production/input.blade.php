@extends('layouts.app')

@section('title', 'Input Hasil Produksi')

@section('content')
    <div x-data="productionForm()" class="max-w-4xl mx-auto pb-24">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Input Hasil Produksi</h1>
            <p class="text-sm text-slate-500">Departemen Bubut • KPI Tracking</p>
        </div>

        {{-- Form Section --}}
        <form id="production-form" action="{{ route('production.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Section 1: Waktu & Shift ( 1 Row, 4 Columns ) --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <span class="material-icons-round text-blue-500">calendar_today</span>
                    <h2 class="font-bold text-lg text-slate-700">Waktu & Shift</h2>
                </div>

                <div class="grid grid-cols-4 gap-4">
                    {{-- Tanggal --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal</label>
                        <input type="date" name="production_date" value="{{ date('Y-m-d', strtotime('-1 day')) }}"
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                    </div>

                    {{-- Shift --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Shift</label>
                        <select name="shift" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                            <option value="1">Shift 1 (07:00-15:00)</option>
                            <option value="2">Shift 2 (15:00-23:00)</option>
                            <option value="3">Shift 3 (23:00-07:00)</option>
                            <option value="non_shift">Non Shift</option>
                        </select>
                    </div>

                    {{-- Waktu Mulai --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Waktu Mulai</label>
                        <input type="time" name="time_start" x-model="timeStart" @change="calculateTarget" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                    </div>

                    {{-- Waktu Selesai --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Waktu Selesai</label>
                        <input type="time" name="time_end" x-model="timeEnd" @change="calculateTarget" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                    </div>
                </div>
            </div>

            {{-- Section 2: Sumber Daya ( 1 Row, 2 Columns ) --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <span class="material-icons-round text-blue-500">group_work</span>
                    <h2 class="font-bold text-lg text-slate-700">Sumber Daya</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Operator Search --}}
                    <div class="space-y-1.5 relative" @click.outside="showOperatorSuggestions = false">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Operator</label>
                        <div class="relative">
                            <input type="text" x-model="operatorSearch" @input.debounce.300ms="searchOperators"
                                placeholder="Cari Operator..."
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 pl-10">
                            <span
                                class="material-icons-round absolute left-3 top-3 text-slate-400 text-lg">person_search</span>
                            <input type="hidden" name="operator_code" x-model="selectedOperatorCode" required>
                        </div>
                        {{-- Operator Suggestions --}}
                        <div x-show="showOperatorSuggestions && operatorList.length > 0"
                            class="absolute z-10 w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto"
                            style="display: none;">
                            <template x-for="op in operatorList" :key="op.code">
                                <div @click="selectOperator(op)"
                                    class="p-3 hover:bg-blue-50 cursor-pointer border-b border-slate-50 last:border-none">
                                    <p class="text-sm font-bold text-slate-700" x-text="op.name"></p>
                                    <p class="text-xs text-slate-400" x-text="op.code"></p>
                                </div>
                            </template>
                        </div>
                        <div x-show="selectedOperatorName"
                            class="text-xs text-emerald-600 font-bold flex items-center gap-1 mt-1">
                            <span class="material-icons-round text-sm">check_circle</span>
                            <span x-text="selectedOperatorName"></span>
                        </div>
                    </div>

                    {{-- Mesin Search --}}
                    <div class="space-y-1.5 relative" @click.outside="showMachineSuggestions = false">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Mesin</label>
                        <div class="relative">
                            <input type="text" x-model="machineSearch" @input.debounce.300ms="searchMachines"
                                placeholder="Cari Mesin..."
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 pl-10"
                                autocomplete="off">
                            <span
                                class="material-icons-round absolute left-3 top-3 text-slate-400 text-lg">precision_manufacturing</span>
                            <input type="hidden" name="machine_code" x-model="selectedMachineCode" required>
                        </div>
                        {{-- Machine Suggestions --}}
                        <div x-show="showMachineSuggestions && machineList.length > 0"
                            class="absolute z-10 w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto"
                            style="display: none;">
                            <template x-for="machine in machineList" :key="machine.code">
                                <div @click="selectMachine(machine)"
                                    class="p-3 hover:bg-blue-50 cursor-pointer border-b border-slate-50 last:border-none">
                                    <p class="text-sm font-bold text-slate-700" x-text="machine.name"></p>
                                    <div class="flex gap-2 text-xs text-slate-400">
                                        <span x-text="machine.code"></span>
                                        <span x-show="machine.line_code" x-text="'• Line: ' + machine.line_code"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 3: Item & Hasil --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <span class="material-icons-round text-blue-500">inventory_2</span>
                    <h2 class="font-bold text-lg text-slate-700">Item & Hasil</h2>
                </div>

                <div class="space-y-6">
                    {{-- Row 1: Search HN & Nama Barang (2 Columns) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Heat Number Search --}}
                        <div class="space-y-1.5 relative" @click.outside="showHeatNumberSuggestions = false">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Cari Heat
                                Number</label>
                            <div class="relative">
                                <input type="text" x-model="heatNumberSearch" @input.debounce.300ms="searchHeatNumbers"
                                    placeholder="Scan/Ketik Heat Number..."
                                    class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 pl-10"
                                    autocomplete="off">
                                <span
                                    class="material-icons-round absolute left-3 top-3 text-slate-400 text-lg">qr_code</span>
                                <input type="hidden" name="heat_number" x-model="selectedHeatNumber">
                                <input type="hidden" name="item_code" x-model="selectedItemCode" required>
                            </div>
                            <div x-show="showHeatNumberSuggestions && heatNumberList.length > 0"
                                class="absolute z-10 w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto"
                                style="display: none;">
                                <template x-for="hn in heatNumberList" :key="hn.id">
                                    <div @click="selectHeatNumber(hn)"
                                        class="p-3 hover:bg-blue-50 cursor-pointer border-b border-slate-50 last:border-none">
                                        <p class="text-sm font-bold text-slate-700" x-text="hn.heat_number"></p>
                                        <p class="text-xs text-slate-400" x-text="hn.item_name"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Nama Barang (Readonly) --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Barang</label>
                            <input type="text" :value="selectedItemName" readonly
                                class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500 cursor-not-allowed"
                                placeholder="-">
                        </div>
                    </div>

                    {{-- Row 2: Size, Line, Customer (3 Columns) --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Size</label>
                            <input type="text" :value="selectedSize" readonly
                                class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500 cursor-not-allowed"
                                placeholder="-">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Line</label>
                            <input type="text" :value="selectedLine" readonly
                                class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500 cursor-not-allowed"
                                placeholder="-">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Customer</label>
                            <input type="text" :value="selectedCustomer" readonly
                                class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500 cursor-not-allowed"
                                placeholder="-">
                        </div>
                    </div>

                    {{-- Row 3: Cycle Time, Target, Hasil (3 Columns) --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Cycle Time (Manual) --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1 block">Cycle
                                Time (Manual)</label>
                            <div class="flex items-center gap-2 mb-1" x-show="avgCycleTimeText" x-transition>
                                <div
                                    class="bg-blue-50 border border-blue-100 text-blue-600 px-3 py-1.5 rounded-lg text-xs font-medium flex items-center gap-1.5 w-full">
                                    <span class="material-icons-round text-sm">history</span>
                                    <span>Rata-rata: <span x-text="avgCycleTimeText" class="font-bold"></span></span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="flex items-center">
                                    <input type="number" name="cycle_time_minutes" x-model="cycleTimeMinutes"
                                        @input="calculateTarget" required min="0" value="0"
                                        class="w-full bg-white border-slate-200 rounded-l-xl border-r-0 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700"
                                        placeholder="0">
                                    <span
                                        class="bg-slate-50 border border-slate-200 border-l-0 text-slate-500 text-xs font-bold px-3 py-3 rounded-r-xl flex items-center h-full">
                                        MENIT
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <input type="number" name="cycle_time_seconds" x-model="cycleTimeSeconds"
                                        @input="calculateTarget" required min="0" max="59" value="0"
                                        class="w-full bg-white border-slate-200 rounded-l-xl border-r-0 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700"
                                        placeholder="0">
                                    <span
                                        class="bg-slate-50 border border-slate-200 border-l-0 text-slate-500 text-xs font-bold px-3 py-3 rounded-r-xl flex items-center h-full">
                                        DETIK
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Target (Auto) --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Target
                                (Auto)</label>
                            <input type="number" readonly x-model="targetQty"
                                class="w-full bg-slate-100 border-transparent rounded-xl text-center font-bold text-slate-600 text-lg p-3 cursor-not-allowed">
                            <p class="text-[10px] text-center text-slate-400">Berdasarkan Cycle Time</p>
                        </div>

                        {{-- Hasil (Manual) --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Hasil (OK)</label>
                            <input type="number" name="actual_qty" x-model="actualQty" @input="calculateAchievement"
                                required min="0"
                                class="w-full bg-white border-blue-300 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 text-center font-bold text-blue-700 text-lg p-3"
                                placeholder="0">
                        </div>
                    </div>

                    {{-- Capaian Row --}}
                    {{-- Keterangan & Capaian --}}
                    <div class="grid grid-cols-2 gap-4 mt-2">
                        {{-- Keterangan Dropdown --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Keterangan
                                (Opsional)</label>
                            <select name="remark"
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                                <option value="" selected>Normal (Selesai)</option>
                                <option value="K1-1 sisi">K1-1 sisi</option>
                                <option value="K1-2 sisi">K1-2 sisi</option>
                                <option value="K1- Finish ID">K1- Finish ID</option>
                                <option value="Finish 1 sisi">Finish 1 sisi</option>
                                <option value="FINISH KASARAN">FINISH KASARAN</option>
                                <option value="K1,K2">K1,K2</option>
                                <option value="KOD,FOD,K1,K2,FID">KOD,FOD,K1,K2,FID</option>
                                <option value="K1,K2,FOD">K1,K2,FOD</option>
                                <option value="KID,FID">KID,FID</option>
                            </select>
                        </div>

                        {{-- Capaian --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Capaian</label>
                            <div class="w-full rounded-xl text-center font-bold text-lg p-3 border" :class="{
                                                                                            'bg-emerald-50 text-emerald-600 border-emerald-200': achievement >= 100,
                                                                                            'bg-amber-50 text-amber-600 border-amber-200': achievement >= 80 && achievement < 100,
                                                                                            'bg-red-50 text-red-600 border-red-200': achievement < 80
                                                                                        }">
                                <span x-text="achievement + '%'">0%</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Submit Button --}}
            @if(auth()->user()->isReadOnly())
                <div class="bg-amber-50 border border-amber-200 text-amber-700 p-4 rounded-2xl flex items-center gap-3">
                    <span class="material-icons-round text-amber-500">lock</span>
                    <div class="text-sm font-medium">
                        Anda berada dalam mode **Read-Only** ({{ auth()->user()->role }}).
                        Anda dapat melihat data tetapi tidak dapat melakukan penyimpanan atau perubahan.
                    </div>
                </div>
            @else
                <button type="button" @click="confirmSubmit"
                    class="w-full bg-blue-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2 hover:bg-blue-700 active:scale-95 transition-transform">
                    <span class="material-icons-round">save_alt</span>
                    Simpan Data Produksi
                </button>
            @endif

            {{-- Session Messages --}}
            @if(session('success'))
                <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-xl flex items-center gap-2">
                    <span class="material-icons-round">check_circle</span>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl">
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

        </form>
    </div>

    {{-- Alpine.js Logic --}}
    <script>
        function productionForm() {
            return {
                // State
                timeStart: '',
                timeEnd: '',

                // Item
                selectedItemCode: '',
                selectedItemName: '',
                selectedSize: '',
                selectedLine: '',
                selectedCustomer: '',

                // Cycle Time Manual
                cycleTimeMinutes: '',
                cycleTimeSeconds: '',

                // Heat Number
                heatNumberSearch: '',
                selectedHeatNumber: '',
                heatNumberList: [],
                // Heat Number
                heatNumberSearch: '',
                selectedHeatNumber: '',
                heatNumberList: [],
                showHeatNumberSuggestions: false,

                // Stats
                avgCycleTimeText: '',

                // Operator Search
                operatorSearch: '',
                selectedOperatorCode: '',
                selectedOperatorName: '',
                operatorList: [],
                operatorList: [],
                showOperatorSuggestions: false,

                // Machine Search
                machineSearch: '',
                selectedMachineCode: '',
                machineList: [],
                showMachineSuggestions: false,

                // Calculation
                targetQty: 0,
                actualQty: '',
                achievement: 0,

                // Actions
                async searchHeatNumbers() {
                    if (this.heatNumberSearch.length < 1) {
                        this.heatNumberList = [];
                        return;
                    }
                    const res = await fetch(`{{ route('api.search.heat_numbers') }}?q=${encodeURIComponent(this.heatNumberSearch)}`);
                    this.heatNumberList = await res.json();
                    this.showHeatNumberSuggestions = true;
                },

                selectHeatNumber(hn) {
                    this.selectedHeatNumber = hn.heat_number;
                    this.heatNumberSearch = hn.heat_number;
                    this.selectedItemCode = hn.item_code;
                    this.selectedItemName = hn.item_name;
                    this.selectedSize = hn.size || '-';
                    this.selectedCustomer = hn.customer || '-';
                    this.selectedLine = hn.line || '-';
                    this.selectedLine = hn.line || '-';
                    this.showHeatNumberSuggestions = false;

                    // Fetch Item Stats for Reference
                    this.fetchItemStats(hn.item_code);
                },

                async fetchItemStats(itemCode) {
                    this.avgCycleTimeText = 'Loading...';
                    try {
                        const res = await fetch(`{{ url('/api/item-stats') }}/${itemCode}`);
                        const data = await res.json();
                        this.avgCycleTimeText = data.formatted;

                        // Optional: Auto-fill if empty? For now, just reference.
                    } catch (e) {
                        this.avgCycleTimeText = '';
                    }
                },

                async searchOperators() {
                    if (this.operatorSearch.length < 1) return;
                    const res = await fetch(`{{ route('api.search.operators') }}?q=${this.operatorSearch}`);
                    this.operatorList = await res.json();
                    this.showOperatorSuggestions = true;
                },

                selectOperator(op) {
                    this.selectedOperatorCode = op.code;
                    this.selectedOperatorName = op.name;
                    this.operatorSearch = op.name; // Display Name nice
                    this.showOperatorSuggestions = false;
                },

                async searchMachines() {
                    if (this.machineSearch.length < 1) return;
                    const res = await fetch(`{{ route('api.search.machines') }}?q=${this.machineSearch}`);
                    this.machineList = await res.json();
                    this.showMachineSuggestions = true;
                },
                selectMachine(machine) {
                    this.selectedMachineCode = machine.code;
                    this.machineSearch = machine.name;
                    this.showMachineSuggestions = false;
                },

                calculateTarget() {
                    // Calculate Total Cycle Time in Seconds
                    const mins = parseInt(this.cycleTimeMinutes) || 0;
                    const secs = parseInt(this.cycleTimeSeconds) || 0;
                    const totalCycleTimeSec = (mins * 60) + secs;

                    if (!this.timeStart || !this.timeEnd || totalCycleTimeSec <= 0) {
                        this.targetQty = 0;
                        return;
                    }

                    // Simple Time Diff (Assuming same day for now)
                    const start = this.parseTime(this.timeStart);
                    const end = this.parseTime(this.timeEnd);

                    let diffSeconds = (end - start) * 60;

                    // Handle crossing midnight if needed later (usually not for simple shifts unless requested)
                    if (diffSeconds < 0) diffSeconds = 0; // Prevent negative

                    // Calculate Target
                    this.targetQty = Math.floor(diffSeconds / totalCycleTimeSec);
                    this.calculateAchievement();
                },

                calculateAchievement() {
                    if (!this.targetQty || this.targetQty <= 0) {
                        this.achievement = 0;
                        return;
                    }
                    const actual = parseInt(this.actualQty) || 0;
                    this.achievement = ((actual / this.targetQty) * 100).toFixed(1);
                },

                parseTime(t) {
                    if (!t) return 0;
                    const [h, m] = t.split(':');
                    return parseInt(h) * 60 + parseInt(m); // return minutes
                },

                // Confirmation Popup
                // Confirmation Popup
                confirmSubmit() {
                    // Default values to 0 if empty
                    this.cycleTimeMinutes = this.cycleTimeMinutes || '0';
                    this.cycleTimeSeconds = this.cycleTimeSeconds || '0';

                    // Basic Validation (Check required fields manually if needed, or rely on form validation after check)
                    // Since we are intercepting, HTML5 required won't trigger on button click automatically if type=button.
                    // So we check keys manually
                    if (!this.selectedOperatorCode || !this.selectedMachineCode || !this.selectedItemCode || !this.timeStart || !this.timeEnd || !this.actualQty) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Data Belum Lengkap',
                            text: 'Mohon lengkapi semua field yang berafiliasi dengan tanda bintang/required.',
                            confirmButtonColor: '#3b82f6'
                        });
                        return;
                    }

                    // Validate Cycle Time > 0
                    const totalSec = (parseInt(this.cycleTimeMinutes) * 60) + parseInt(this.cycleTimeSeconds);
                    if (totalSec <= 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cycle Time Invalid',
                            text: 'Total Cycle Time tidak boleh 0 detik.',
                            confirmButtonColor: '#3b82f6'
                        });
                        return;
                    }

                    const summaryHtml = `
                                        <div class="text-left text-sm text-slate-600 space-y-2 bg-slate-50 p-4 rounded-xl border border-slate-200">
                                            <div class="flex justify-between border-b border-slate-200 pb-2">
                                                <span class="font-medium">Operator:</span>
                                                <span class="font-bold text-slate-800">${this.selectedOperatorName}</span>
                                            </div>
                                            <div class="flex justify-between border-b border-slate-200 pb-2">
                                                <span class="font-medium">Mesin:</span>
                                                <span class="font-bold text-slate-800">${this.machineSearch}</span>
                                            </div>
                                            <div class="flex justify-between border-b border-slate-200 pb-2">
                                                <span class="font-medium">Barang/Heat:</span>
                                                <span class="font-bold text-slate-800">${this.selectedItemName} (${this.selectedHeatNumber || '-'})</span>
                                            </div>
                                            <div class="flex justify-between border-b border-slate-200 pb-2">
                                                <span class="font-medium">Cycle Time:</span>
                                                <span class="font-bold text-slate-800">${this.cycleTimeMinutes}m ${this.cycleTimeSeconds}s</span>
                                            </div>
                                            <div class="flex justify-between border-b border-slate-200 pb-2">
                                                <span class="font-medium">Waktu:</span>
                                                <span class="font-bold text-slate-800">${this.timeStart} - ${this.timeEnd}</span>
                                            </div>
                                            <div class="flex justify-between pt-1">
                                                <span class="font-medium">Hasil Output:</span>
                                                <span class="font-bold text-blue-600 text-lg">${this.actualQty} PCS</span>
                                            </div>
                                        </div>
                                    `;

                    Swal.fire({
                        title: 'Verifikasi Data',
                        html: summaryHtml,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Lanjutkan',
                        cancelButtonText: 'Periksa Lagi',
                        confirmButtonColor: '#2563eb', // Blue
                        cancelButtonColor: '#dc2626', // Red
                        reverseButtons: true,
                        focusConfirm: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Submit user form
                            document.getElementById('production-form').submit();
                        }
                    });
                }
            }
        }
    </script>

    {{-- SweetAlert2 bundled in app.js --}}
@endsection