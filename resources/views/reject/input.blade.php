@extends('layouts.app')

@section('title', 'Input Kerusakan')

@section('content')
    <div x-data="rejectForm()" class="max-w-2xl mx-auto pb-24">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Input Kerusakan (Reject)</h1>
            <p class="text-sm text-slate-500">Departemen Bubut • Quality Control</p>
        </div>

        {{-- Alert --}}
        <div class="bg-orange-50 border border-orange-200 p-4 rounded-2xl flex items-start gap-3 mb-6">
            <span class="material-icons-round text-orange-600">warning</span>
            <div>
                <p class="text-sm font-medium text-orange-800">
                    Segera laporkan kerusakan untuk menghindari akumulasi reject.
                </p>
                <div class="mt-1 flex items-center gap-2">
                    <span class="text-xs text-orange-700">Target reject rate:</span>
                    <span class="px-2 py-0.5 bg-orange-200 text-[10px] font-bold rounded-full text-orange-900">
                        < 5%</span>
                </div>
            </div>
        </div>

        {{-- Form Section --}}
        <form action="{{ route('reject.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <span class="material-icons-round text-orange-500">edit_note</span>
                    <h2 class="font-bold text-lg text-slate-700">Laporan Kerusakan Baru</h2>
                </div>

                {{-- Grid 1: Date & Time --}}
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</label>
                        <input type="date" name="reject_date" value="{{ date('Y-m-d') }}"
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 text-sm p-3">
                    </div>
                    {{-- Waktu Kejadian can be implicit or explicit, sticking to basic form --}}
                </div>

                {{-- Grid 2: Operator & Machine --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    {{-- Operator Autocomplete --}}
                    <div class="space-y-1.5 relative" @click.outside="showOperatorSuggestions = false">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Operator</label>
                        <div class="relative">
                            <input type="text" x-model="operatorSearch" @input.debounce.300ms="searchOperators"
                                placeholder="Cari Operator..."
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 text-sm p-3 pl-10">
                            <span class="material-icons-round absolute left-3 top-3 text-slate-400">person_search</span>
                            <input type="hidden" name="operator_code" x-model="selectedOperatorCode" required>
                        </div>

                        {{-- Suggestions --}}
                        <div x-show="showOperatorSuggestions && operatorList.length > 0"
                            class="absolute z-10 w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto"
                            style="display: none;">
                            <template x-for="op in operatorList" :key="op.code">
                                <div @click="selectOperator(op)"
                                    class="p-3 hover:bg-orange-50 cursor-pointer border-b border-slate-50">
                                    <p class="text-sm font-bold text-slate-700" x-text="op.name"></p>
                                    <p class="text-xs text-slate-400" x-text="op.code"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Machine Select --}}
                    {{-- Machine Autocomplete --}}
                    <div class="space-y-1.5 relative" @click.outside="showMachineSuggestions = false">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mesin</label>
                        <div class="relative">
                            <input type="text" x-model="machineSearch" @input.debounce.300ms="searchMachines"
                                placeholder="Cari Mesin..."
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 text-sm p-3 pl-10"
                                autocomplete="off">
                            <span
                                class="material-icons-round absolute left-3 top-3 text-slate-400">precision_manufacturing</span>
                            <input type="hidden" name="machine_code" x-model="selectedMachineCode" required>
                        </div>

                        {{-- Suggestions --}}
                        <div x-show="showMachineSuggestions && machineList.length > 0"
                            class="absolute z-10 w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto"
                            style="display: none;">
                            <template x-for="machine in machineList" :key="machine.code">
                                <div @click="selectMachine(machine)"
                                    class="p-3 hover:bg-orange-50 cursor-pointer border-b border-slate-50">
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

                {{-- Heat Number Autocomplete --}}
                <div class="space-y-1.5 mb-4 relative" @click.outside="showHeatNumberSuggestions = false">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cari Heat Number</label>
                    <div class="relative">
                        <input type="text" x-model="heatNumberSearch" @input.debounce.300ms="searchHeatNumbers"
                            placeholder="Cth: A210012502..."
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 text-sm p-3 pl-10">
                        <span class="material-icons-round absolute left-3 top-3 text-slate-400">qr_code</span>
                        <input type="hidden" name="heat_number" x-model="selectedHeatNumber">
                        <input type="hidden" name="item_code" x-model="selectedItemCode" required>
                    </div>

                    {{-- Suggestions --}}
                    <div x-show="showHeatNumberSuggestions && heatNumberList.length > 0"
                        class="absolute z-10 w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto"
                        style="display: none;">
                        <template x-for="hn in heatNumberList" :key="hn.heat_number">
                            <div @click="selectHeatNumber(hn)"
                                class="p-3 hover:bg-orange-50 cursor-pointer border-b border-slate-50">
                                <p class="text-sm font-bold text-slate-700" x-text="hn.heat_number"></p>
                                <p class="text-xs text-slate-400" x-text="hn.item_name"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="space-y-1.5 mb-4">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Barang</label>
                    <input type="text" :value="selectedItemName" readonly
                        class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500 cursor-not-allowed"
                        placeholder="-">
                </div>

                {{-- Reject Reason --}}
                <div class="space-y-1.5 mb-4">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jenis Kerusakan /
                        Reason</label>
                    <select name="reject_reason" required
                        class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 text-sm p-3">
                        <option value="" disabled selected>Pilih Jenis Kerusakan</option>
                        <option value="Ukuran Tidak Sesuai">Ukuran Tidak Sesuai</option>
                        <option value="Permukaan Kasar">Permukaan Kasar</option>
                        <option value="Retak/Pecah">Retak/Pecah</option>
                        <option value="Material Cacat">Material Cacat</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                {{-- Quantity & Rate --}}
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Jml Reject</label>
                        <input type="number" name="reject_qty" x-model="rejectQty" @input="calculateRate" required min="1"
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 text-sm p-3 text-center font-bold">
                    </div>
                    {{-- Optional Total Production for Rate Calc --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Total Prod</label>
                        <input type="number" x-model="totalQty" @input="calculateRate"
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 text-sm p-3 text-center"
                            placeholder="Opsional">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase">Reject Rate</label>
                        <div
                            class="w-full bg-orange-50 border border-orange-200 rounded-xl text-sm p-3 text-center font-bold text-orange-600">
                            <span x-text="rejectRate + '%'">0%</span>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Catatan</label>
                    <input type="text" name="note"
                        class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 text-sm p-3"
                        placeholder="Keterangan tambahan...">
                </div>

            </div>

            {{-- Submit Button --}}
            <button type="submit"
                class="w-full bg-orange-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-orange-500/20 flex items-center justify-center gap-2 hover:bg-orange-700 active:scale-95 transition-transform">
                <span class="material-icons-round">save</span>
                Simpan Laporan Kerusakan
            </button>

            {{-- Messages --}}
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

    <script>
        function rejectForm() {
            return {
                operatorSearch: '',
                selectedOperatorCode: '',
                operatorList: [],
                operatorList: [],
                showOperatorSuggestions: false,

                machineSearch: '',
                selectedMachineCode: '',
                machineList: [],
                showMachineSuggestions: false,

                // Heat Number
                heatNumberSearch: '',
                selectedHeatNumber: '',
                selectedItemName: '',
                heatNumberList: [],
                showHeatNumberSuggestions: false,

                selectedItemCode: '',

                rejectQty: '',
                totalQty: '',
                rejectRate: 0,

                async searchOperators() {
                    if (this.operatorSearch.length < 1) return;
                    const res = await fetch(`{{ route('api.search.operators') }}?q=${this.operatorSearch}`);
                    this.operatorList = await res.json();
                    this.showOperatorSuggestions = true;
                },
                selectOperator(op) {
                    this.selectedOperatorCode = op.code;
                    this.operatorSearch = op.name;
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
                },

                calculateRate() {
                    const rej = parseInt(this.rejectQty) || 0;
                    const tot = parseInt(this.totalQty) || 0;
                    if (tot > 0) {
                        this.rejectRate = ((rej / tot) * 100).toFixed(1);
                    } else {
                        this.rejectRate = 0;
                    }
                }
            }
        }
    </script>
@endsection