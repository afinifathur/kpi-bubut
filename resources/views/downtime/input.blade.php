@extends('layouts.app')

@section('title', 'Laporan Harian Mesin')

@section('content')
    <div x-data="dailyReportForm()" class="max-w-3xl mx-auto pb-24">

        {{-- Header --}}
        <div class="mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Laporan Harian Mesin</h1>
                <p class="text-sm text-slate-500">Departemen Bubut • Maintenance & Ops</p>
            </div>
            <div class="text-right">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Status</span>
                <div class="flex items-center gap-1 text-emerald-500 font-bold">
                    <span class="material-icons-round text-sm">verified</span>
                    <span>Ready</span>
                </div>
            </div>
        </div>

        {{-- Form Section --}}
        <form action="{{ route('downtime.store') }}" method="POST" class="space-y-4">
            @csrf

            {{-- CARD 1: INFORMASI UMUM --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <span class="material-icons-round text-blue-500">settings_suggest</span>
                    <h2 class="font-bold text-lg text-slate-700">Informasi Umum</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Tanggal --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal Laporan</label>
                        <input type="date" name="downtime_date" value="{{ date('Y-m-d') }}"
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm p-3">
                    </div>

                    {{-- Mesin --}}
                    <div class="space-y-1.5 relative" @click.outside="showMachineSuggestions = false">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pilih Mesin</label>
                        <div class="relative">
                            <input type="text" x-model="machineSearch" @input.debounce.300ms="searchMachines"
                                placeholder="Cari Mesin..."
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm p-3 pl-10"
                                autocomplete="off">
                            <span class="material-icons-round absolute left-3 top-3 text-slate-400">precision_manufacturing</span>
                            <input type="hidden" name="machine_code" x-model="selectedMachineCode" required>
                        </div>

                        {{-- Suggestions --}}
                        <div x-show="showMachineSuggestions && machineList.length > 0"
                            class="absolute z-50 w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-60 overflow-y-auto"
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
                </div>

                {{-- Tipe Laporan --}}
                <div class="mt-6">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-3">Tipe Inputan</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label :class="entryType === 'downtime' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-200 bg-slate-50 text-slate-500'"
                            class="relative flex flex-col items-center p-4 border-2 rounded-2xl cursor-pointer transition-all hover:bg-white group">
                            <input type="radio" name="entry_type" value="downtime" x-model="entryType" class="sr-only">
                            <span class="material-icons-round mb-1" :class="entryType === 'downtime' ? 'text-red-500' : 'text-slate-400'">timer_off</span>
                            <span class="font-bold text-sm">Downtime</span>
                            <p class="text-[10px] opacity-70">Masalah Mesin Stop</p>
                        </label>

                        <label :class="entryType === 'check' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500'"
                            class="relative flex flex-col items-center p-4 border-2 rounded-2xl cursor-pointer transition-all hover:bg-white group">
                            <input type="radio" name="entry_type" value="check" x-model="entryType" class="sr-only">
                            <span class="material-icons-round mb-1" :class="entryType === 'check' ? 'text-emerald-500' : 'text-slate-400'">fact_check</span>
                            <span class="font-bold text-sm">Cek Harian</span>
                            <p class="text-[10px] opacity-70">Pengecekan Standar</p>
                        </label>
                    </div>
                </div>
            </div>

            {{-- CARD 2: DETAIL DINAMIS --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
                
                {{-- DOWNTIME UI --}}
                <div x-show="entryType === 'downtime'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" class="space-y-5">
                    <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                        <span class="material-icons-round text-red-500">warning</span>
                        <h2 class="font-bold text-lg text-slate-700">Detail Downtime</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mulai Stop (Tanggal & Jam)</label>
                            <input type="datetime-local" name="start_time" x-model="startTime" @change="calculateDuration"
                                :required="entryType === 'downtime'" :disabled="entryType !== 'downtime'"
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Selesai Stop (Tanggal & Jam)</label>
                            <input type="datetime-local" name="end_time" x-model="endTime" @change="calculateDuration"
                                :required="entryType === 'downtime'" :disabled="entryType !== 'downtime'"
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3">
                        </div>
                    </div>

                    <div class="bg-red-50 p-4 rounded-xl border border-red-100 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-red-700">
                            <span class="material-icons-round">schedule</span>
                            <span class="text-sm font-bold uppercase">Total Durasi</span>
                        </div>
                        <div class="text-2xl font-black text-red-600">
                            <span x-text="formattedDuration">0j 0m</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Penyebab / Alasan</label>
                        <select name="reason" :required="entryType === 'downtime'" :disabled="entryType !== 'downtime'"
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3">
                            <option value="">-- Pilih Alasan --</option>
                            <option value="Setting Mesin">Setting Mesin</option>
                            <option value="Perbaikan Tool">Perbaikan Tool</option>
                            <option value="Tunggu Material">Tunggu Material</option>
                            <option value="Perbaikan Mekanik">Perbaikan Mekanik</option>
                            <option value="Perbaikan Elektrik">Perbaikan Elektrik</option>
                            <option value="Tunggu Program">Tunggu Program</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>

                {{-- DAILY CHECK UI --}}
                <div x-show="entryType === 'check'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" class="space-y-6" style="display: none;">
                    <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                        <span class="material-icons-round text-emerald-500">check_circle</span>
                        <h2 class="font-bold text-lg text-slate-700">Checklist Pengecekan Harian</h2>
                    </div>

                    {{-- Checklist Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                        <template x-for="(q, index) in questions" :key="index">
                            <div class="flex items-center justify-between group">
                                <span class="text-sm text-slate-600 group-hover:text-slate-900 transition-colors" x-text="(index+1) + '. ' + q.label"></span>
                                <div class="flex bg-slate-100 p-1 rounded-lg gap-1">
                                    <label class="cursor-pointer">
                                        <input type="radio" :name="q.name" value="Ya" class="sr-only peer" 
                                            :required="entryType === 'check'" :disabled="entryType !== 'check'">
                                        <div class="px-3 py-1 rounded-md text-[10px] font-bold uppercase transition-all peer-checked:bg-white peer-checked:text-emerald-600 peer-checked:shadow-sm text-slate-400">Ya</div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" :name="q.name" value="Tidak" class="sr-only peer"
                                            :disabled="entryType !== 'check'">
                                        <div class="px-3 py-1 rounded-md text-[10px] font-bold uppercase transition-all peer-checked:bg-white peer-checked:text-red-600 peer-checked:shadow-sm text-slate-400">Tidak</div>
                                    </label>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Technical Data --}}
                    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Size & Mode --}}
                            <div class="space-y-4">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ukuran / Size</label>
                                    <select name="size_category" x-model="selectedSize" @change="updateStandards"
                                        :required="entryType === 'check'" :disabled="entryType !== 'check'"
                                        class="w-full bg-white border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm p-3 font-bold">
                                        <option value="">-- Pilih Ukuran --</option>
                                        <template x-for="size in Object.keys(standards)" :key="size">
                                            <option :value="size" x-text="size"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mode Pahat</label>
                                    <div class="flex bg-white p-1 rounded-xl border border-slate-200">
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" name="rpm_feeding_mode" value="kasar" x-model="mode" @change="updateStandards" 
                                                :required="entryType === 'check'" :disabled="entryType !== 'check'" class="sr-only peer">
                                            <div class="text-center py-2 rounded-lg text-sm font-bold peer-checked:bg-slate-800 peer-checked:text-white text-slate-400 transition-all">Kasar</div>
                                        </label>
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" name="rpm_feeding_mode" value="finish" x-model="mode" @change="updateStandards" 
                                                :disabled="entryType !== 'check'" class="sr-only peer">
                                            <div class="text-center py-2 rounded-lg text-sm font-bold peer-checked:bg-slate-800 peer-checked:text-white text-slate-400 transition-all">Finish</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- RPM & Feeding Inputs --}}
                            <div class="space-y-4">
                                <div class="space-y-1.5">
                                    <div class="flex justify-between items-center">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kecepatan RPM</label>
                                        <span class="text-[10px] font-bold text-slate-400" x-text="'Std: ' + (currentStd.rpm || '-')"></span>
                                    </div>
                                    <input type="number" name="rpm_value" x-model="rpmInput" placeholder="Contoh: 300"
                                        :class="rpmStatus" :required="entryType === 'check'" :disabled="entryType !== 'check'"
                                        class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm p-3 font-black">
                                </div>
                                <div class="space-y-1.5">
                                    <div class="flex justify-between items-center">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kecepatan Feeding</label>
                                        <span class="text-[10px] font-bold text-slate-400" x-text="'Std: ' + (currentStd.feeding || '-')"></span>
                                    </div>
                                    <input type="number" step="0.01" name="feeding_value" x-model="feedingInput" placeholder="Contoh: 0.17"
                                        :class="feedingStatus" :required="entryType === 'check'" :disabled="entryType !== 'check'"
                                        class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm p-3 font-black">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider font-bold">Catatan / Keterangan Tambahan</label>
                    <textarea name="note" rows="2"
                        class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm p-3"
                        placeholder="Detail tambahan..."></textarea>
                </div>
            </div>

            {{-- Submit Button --}}
            <button type="submit"
                :class="entryType === 'downtime' ? 'bg-red-600 shadow-red-500/20' : 'bg-emerald-600 shadow-emerald-500/20'"
                class="w-full text-white font-bold py-4 rounded-2xl shadow-lg flex items-center justify-center gap-2 hover:opacity-90 active:scale-95 transition-all">
                <span class="material-icons-round">save</span>
                Simpan Laporan Harian
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
        function dailyReportForm() {
            return {
                entryType: 'downtime',
                machineSearch: '',
                selectedMachineCode: '',
                machineList: [],
                showMachineSuggestions: false,

                // Downtime
                startTime: '',
                endTime: '',
                durationMinutes: 0,

                // Daily Check
                selectedSize: '',
                mode: 'kasar',
                rpmInput: '',
                feedingInput: '',
                currentStd: { rpm: null, feeding: null },

                questions: [
                    { label: 'Apakah cekam dapat dioperasikan normal?', name: 'check_cekam' },
                    { label: 'Apakah Air ozzon dapat keluar dengan Normal?', name: 'check_air_ozo' },
                    { label: 'Apakah Eretan dapat dioperasikan normal?', name: 'check_eretan' },
                    { label: 'Apakah sudah terpasang 3 pisau pahat?', name: 'check_pisau' },
                    { label: 'Apakah kebersihan mesin terjaga?', name: 'check_kebersihan' },
                    { label: 'Apakah oli hidrolis normal?', name: 'check_oli' }
                ],

                standards: {
                    '1/2" - 3/4"': {
                        'kasar': { rpm: 380, feeding: 0.17 },
                        'finish': { rpm: 450, feeding: 0.2 }
                    },
                    '1"': {
                        'kasar': { rpm: 350, feeding: 0.17 },
                        'finish': { rpm: 450, feeding: 0.2 }
                    },
                    '1-1/4" - 2"': {
                        'kasar': { rpm: 300, feeding: 0.17 },
                        'finish': { rpm: 380, feeding: 0.2 }
                    },
                    '2" - 2-1/2"': {
                        'kasar': { rpm: 300, feeding: 0.17 },
                        'finish': { rpm: 350, feeding: 0.2 }
                    },
                    '3" - 4"': {
                        'kasar': { rpm: 250, feeding: 0.17 },
                        'finish': { rpm: 320, feeding: 0.2 }
                    },
                    '5" - 6"': {
                        'kasar': { rpm: 220, feeding: 0.17 },
                        'finish': { rpm: 260, feeding: 0.2 }
                    },
                    '8"': {
                        'kasar': { rpm: 200, feeding: 0.16 },
                        'finish': { rpm: 240, feeding: 0.2 }
                    }
                },

                init() {
                    // Initial setup if needed
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
                    if (!this.startTime || !this.endTime) return;
                    const start = new Date(this.startTime);
                    const end = new Date(this.endTime);
                    const diffMs = end - start;
                    this.durationMinutes = diffMs > 0 ? Math.round(diffMs / 60000) : 0;
                },

                get formattedDuration() {
                    const h = Math.floor(this.durationMinutes / 60);
                    const m = this.durationMinutes % 60;
                    return `${h}j ${m}m`;
                },

                updateStandards() {
                    if (this.selectedSize && this.standards[this.selectedSize]) {
                        this.currentStd = this.standards[this.selectedSize][this.mode];
                    } else {
                        this.currentStd = { rpm: null, feeding: null };
                    }
                },

                calcStatus(input, std) {
                    if (!input || !std) return 'bg-white';
                    const val = parseFloat(input);
                    const diff = Math.abs(val - std) / std;
                    return diff > 0.2 ? 'bg-red-50 border-red-300 text-red-600' : 'bg-emerald-50 border-emerald-300 text-emerald-600';
                },

                get rpmStatus() {
                    return this.calcStatus(this.rpmInput, this.currentStd.rpm);
                },

                get feedingStatus() {
                    return this.calcStatus(this.feedingInput, this.currentStd.feeding);
                }
            }
        }
    </script>
@endsection
tion