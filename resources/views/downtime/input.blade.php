@extends('layouts.app')

@section('title', 'Input Downtime')

@section('content')
    <div x-data="downtimeForm()" class="max-w-2xl mx-auto pb-24">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Input Downtime Mesin</h1>
            <p class="text-sm text-slate-500">Departemen Bubut • Maintenance & Ops</p>
        </div>

        {{-- Form Section --}}
        <form action="{{ route('downtime.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <span class="material-icons-round text-red-500">timer_off</span>
                    <h2 class="font-bold text-lg text-slate-700">Laporan Mesin Stop</h2>
                </div>

                {{-- Info Dasar --}}
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</label>
                        <input type="date" name="downtime_date" value="{{ date('Y-m-d') }}"
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Shift</label>
                        <select name="shift" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3">
                            <option value="1">Shift 1 (07:00-15:00)</option>
                            <option value="2">Shift 2 (15:00-23:00)</option>
                            <option value="3">Shift 3 (23:00-07:00)</option>
                            <option value="non_shift">Non Shift</option>
                        </select>
                    </div>
                </div>

                {{-- Mesin & Operator --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="space-y-1.5 relative" @click.outside="showMachineSuggestions = false">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mesin</label>
                        <div class="relative">
                            <input type="text" x-model="machineSearch" @input.debounce.300ms="searchMachines"
                                placeholder="Cari Mesin..."
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3 pl-10"
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
                                    class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-50">
                                    <p class="text-sm font-bold text-slate-700" x-text="machine.name"></p>
                                    <div class="flex gap-2 text-xs text-slate-400">
                                        <span x-text="machine.code"></span>
                                        <span x-show="machine.line_code" x-text="'• Line: ' + machine.line_code"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="space-y-1.5 relative" @click.outside="showOperatorSuggestions = false">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Operator</label>
                        <div class="relative">
                            <input type="text" x-model="operatorSearch" @input.debounce.300ms="searchOperators"
                                placeholder="Cari Operator..."
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3 pl-10">
                            <span class="material-icons-round absolute left-3 top-3 text-slate-400">person_search</span>
                            <input type="hidden" name="operator_code" x-model="selectedOperatorCode" required>
                        </div>

                        {{-- Suggestions --}}
                        <div x-show="showOperatorSuggestions && operatorList.length > 0"
                            class="absolute z-10 w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto"
                            style="display: none;">
                            <template x-for="op in operatorList" :key="op.code">
                                <div @click="selectOperator(op)"
                                    class="p-3 hover:bg-slate-50 cursor-pointer border-b border-slate-50">
                                    <p class="text-sm font-bold text-slate-700" x-text="op.name"></p>
                                    <p class="text-xs text-slate-400" x-text="op.code"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Time Inputs & Duration --}}
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mulai Stop</label>
                        <input type="time" name="time_start" x-model="timeStart" @change="calculateDuration" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Selesai Stop</label>
                        <input type="time" name="time_end" x-model="timeEnd" @change="calculateDuration" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Durasi (Menit)</label>
                        <div
                            class="w-full bg-red-50 border border-red-100 rounded-xl text-sm p-3 text-center font-bold text-red-600">
                            <span x-text="durationMinutes">0</span> Min
                        </div>
                    </div>
                </div>

                {{-- Reason & Note --}}
                <div class="space-y-1.5 mb-4">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Penyebab / Alasan</label>
                    <input type="text" name="reason" required
                        class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3"
                        placeholder="Contoh: Setting Mesin, Perbaikan Tool, Tunggu Material, dll.">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Keterangan (Opsional)</label>
                    <textarea name="note" rows="2"
                        class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3"
                        placeholder="Detail tambahan..."></textarea>
                </div>

            </div>

            {{-- Submit Button --}}
            <button type="submit"
                class="w-full bg-red-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-red-500/20 flex items-center justify-center gap-2 hover:bg-red-700 active:scale-95 transition-transform">
                <span class="material-icons-round">save</span>
                Simpan Data Downtime
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
        function downtimeForm() {
            return {
                operatorSearch: '',
                selectedOperatorCode: '',
                operatorList: [],
                showOperatorSuggestions: false,

                machineSearch: '',
                selectedMachineCode: '',
                machineList: [],
                showMachineSuggestions: false,

                timeStart: '',
                timeEnd: '',
                durationMinutes: 0,

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

                calculateDuration() {
                    if (!this.timeStart || !this.timeEnd) return;

                    const start = this.parseTime(this.timeStart);
                    const end = this.parseTime(this.timeEnd);

                    let diff = end - start;
                    // If diff is negative, assume midnight crossing? (Maybe keep simple for now)

                    this.durationMinutes = diff > 0 ? diff : 0;
                },

                parseTime(t) {
                    const [h, m] = t.split(':');
                    return parseInt(h) * 60 + parseInt(m);
                }
            }
        }
    </script>
@endsection