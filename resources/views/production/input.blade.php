@extends('layouts.app')

@section('title', 'Input Hasil Produksi')

@section('content')
    <div x-data="productionForm()" class="max-w-3xl mx-auto pb-24">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Input Hasil Produksi</h1>
            <p class="text-sm text-slate-500">Departemen Bubut • KPI Tracking</p>
        </div>

        {{-- Form Section --}}
        <form action="{{ route('production.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Section 1: Basic Info --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <span class="material-icons-round text-blue-500">calendar_today</span>
                    <h2 class="font-bold text-lg text-slate-700">Waktu & Shift</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Tanggal --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</label>
                        <input type="date" name="production_date" value="{{ date('Y-m-d') }}"
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                    </div>

                    {{-- Shift --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Shift</label>
                        <select name="shift" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                            <option value="1">Shift 1 (07:00-15:00)</option>
                            <option value="2">Shift 2 (15:00-23:00)</option>
                            <option value="3">Shift 3 (23:00-07:00)</option>
                            <option value="non_shift">Non Shift</option>
                        </select>
                    </div>

                    {{-- Jam Kerja --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Waktu Mulai</label>
                        <input type="time" name="time_start" x-model="timeStart" @change="calculateTarget" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Waktu Selesai</label>
                        <input type="time" name="time_end" x-model="timeEnd" @change="calculateTarget" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                    </div>
                </div>
            </div>

            {{-- Section 2: Resources (Autocomplete) --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <span class="material-icons-round text-blue-500">group_work</span>
                    <h2 class="font-bold text-lg text-slate-700">Sumber Daya</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Operator Autocomplete --}}
                    <div class="space-y-1.5 relative" @click.outside="showOperatorSuggestions = false">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Operator</label>
                        <div class="relative">
                            <input type="text" x-model="operatorSearch" @input.debounce.300ms="searchOperators"
                                placeholder="Ketik Nama/Kode Operator..."
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 pl-10">
                            <span
                                class="material-icons-round absolute left-3 top-3 text-slate-400 text-lg">person_search</span>

                            {{-- Hidden Input for Real Value --}}
                            <input type="hidden" name="operator_code" x-model="selectedOperatorCode" required>
                        </div>

                        {{-- Suggestions Dropdown --}}
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
                        {{-- Selected Indication --}}
                        <div x-show="selectedOperatorName"
                            class="text-xs text-emerald-600 font-bold flex items-center gap-1 mt-1">
                            <span class="material-icons-round text-sm">check_circle</span>
                            <span x-text="selectedOperatorName"></span>
                        </div>
                    </div>

                    {{-- Mesin (Standard Select for now, list is small) --}}
                    {{-- Mesin (Autocomplete) --}}
                    <div class="space-y-1.5 relative" @click.outside="showMachineSuggestions = false">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mesin</label>
                        <div class="relative">
                            <input type="text" x-model="machineSearch" @input.debounce.300ms="searchMachines"
                                placeholder="Cari Mesin..."
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 pl-10"
                                autocomplete="off">
                            <span
                                class="material-icons-round absolute left-3 top-3 text-slate-400 text-lg">precision_manufacturing</span>
                            <input type="hidden" name="machine_code" x-model="selectedMachineCode" required>
                        </div>

                        {{-- Suggestions --}}
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

            {{-- Section 3: Item & Production --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <span class="material-icons-round text-blue-500">inventory_2</span>
                    <h2 class="font-bold text-lg text-slate-700">Item & Hasil</h2>
                </div>

                <div class="space-y-5">
                    {{-- Heat Number Autocomplete --}}
                    <div class="space-y-1.5 relative" @click.outside="showHeatNumberSuggestions = false">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cari Heat Number</label>
                        <div class="relative">
                            <input type="text" x-model="heatNumberSearch" @input.debounce.300ms="searchHeatNumbers"
                                placeholder="Cth: A210012502..."
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 pl-10"
                                autocomplete="off">
                            <span class="material-icons-round absolute left-3 top-3 text-slate-400 text-lg">qr_code</span>
                            <input type="hidden" name="heat_number" x-model="selectedHeatNumber">
                            <input type="hidden" name="item_code" x-model="selectedItemCode" required>
                        </div>

                        {{-- Suggestions --}}
                        <div x-show="showHeatNumberSuggestions && heatNumberList.length > 0"
                            class="absolute z-10 w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto"
                            style="display: none;">
                            <template x-for="hn in heatNumberList" :key="hn.heat_number">
                                <div @click="selectHeatNumber(hn)"
                                    class="p-3 hover:bg-blue-50 cursor-pointer border-b border-slate-50 last:border-none">
                                    <p class="text-sm font-bold text-slate-700" x-text="hn.heat_number"></p>
                                    <p class="text-xs text-slate-400" x-text="hn.item_name"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Item Info (Readonly) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Barang</label>
                            <input type="text" :value="selectedItemName" readonly
                                class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500 cursor-not-allowed"
                                placeholder="-">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cycle Time
                                (Sec)</label>
                            <input type="text" :value="cycleTime" readonly
                                class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500 cursor-not-allowed"
                                placeholder="0">
                        </div>
                    </div>

                    {{-- Calculation Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Target (Auto) --}}
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Target (Auto)</label>
                            <input type="number" readonly x-model="targetQty"
                                class="w-full bg-slate-100 border-transparent rounded-xl text-center font-bold text-slate-600 text-lg p-3 cursor-not-allowed">
                            <p class="text-[10px] text-center text-slate-400">Berdasarkan Cycle Time</p>
                        </div>

                        {{-- Hasil (Manual) --}}
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-blue-600 uppercase tracking-wider">Hasil (OK)</label>
                            <input type="number" name="actual_qty" x-model="actualQty" @input="calculateAchievement"
                                required min="0"
                                class="w-full bg-white border-blue-300 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 text-center font-bold text-blue-700 text-lg p-3"
                                placeholder="0">
                        </div>

                        {{-- Achievement (Auto) --}}
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Capaian</label>
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
                <button type="submit"
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
                cycleTime: 0,

                // Heat Number
                heatNumberSearch: '',
                selectedHeatNumber: '',
                heatNumberList: [],
                showHeatNumberSuggestions: false,

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
                    this.showHeatNumberSuggestions = false;

                    // We might need to fetch item cycle time from mirror if not included in Heat Number selection
                    this.fetchItemDetails(hn.item_code);
                },

                async fetchItemDetails(itemCode) {
                    // Quick way to get cycle time for the selected item
                    const res = await fetch(`{{ route('api.search.items') }}?q=${encodeURIComponent(itemCode)}&exact=1`);
                    const data = await res.json();
                    if (data.length > 0) {
                        this.cycleTime = data[0].cycle_time_sec;
                    }
                    this.calculateTarget();
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
                    if (!this.timeStart || !this.timeEnd || !this.cycleTime || this.cycleTime <= 0) {
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
                    this.targetQty = Math.floor(diffSeconds / this.cycleTime);
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
                }
            }
        }
    </script>
@endsection