@extends('layouts.app')

@section('title', 'Edit Laporan Harian')

@section('content')
    <div x-data="editForm()" class="max-w-3xl mx-auto pb-24">

        {{-- Header --}}
        <div class="mb-8 flex items-center gap-3">
            <a href="{{ route('daily_report.downtime.show', $log->downtime_date) }}"
                class="text-gray-400 hover:text-gray-600 transition-colors">
                <span class="material-icons-round">arrow_back</span>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Edit {{ $log->entry_type === 'check' ? 'Pengecekan' : 'Downtime' }}</h1>
                <p class="text-sm text-slate-500">
                    {{ \Carbon\Carbon::parse($log->downtime_date)->locale('id')->isoFormat('dddd, D MMMM Y') }} • {{ $log->machine->name }}
                </p>
            </div>
        </div>

        {{-- Form Section --}}
        <form id="edit-form" action="{{ route('daily_report.downtime.update', $log->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- CARD: DETAIL --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 overflow-hidden relative">
                
                {{-- DOWNTIME UI --}}
                @if($log->entry_type === 'downtime')
                    <div class="space-y-5">
                        <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                            <span class="material-icons-round text-red-500">warning</span>
                            <h2 class="font-bold text-lg text-slate-700">Detail Downtime</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mulai Stop (Tanggal & Jam)</label>
                                <input type="datetime-local" name="start_time" x-model="startTime" @change="calculateDuration" required
                                    class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Selesai Stop (Tanggal & Jam)</label>
                                <input type="datetime-local" name="end_time" x-model="endTime" @change="calculateDuration" required
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
                            <select name="reason" required
                                class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-red-500 text-sm p-3">
                                <option value="Setting Mesin" {{ $log->reason === 'Setting Mesin' ? 'selected' : '' }}>Setting Mesin</option>
                                <option value="Perbaikan Tool" {{ $log->reason === 'Perbaikan Tool' ? 'selected' : '' }}>Perbaikan Tool</option>
                                <option value="Tunggu Material" {{ $log->reason === 'Tunggu Material' ? 'selected' : '' }}>Tunggu Material</option>
                                <option value="Perbaikan Mekanik" {{ $log->reason === 'Perbaikan Mekanik' ? 'selected' : '' }}>Perbaikan Mekanik</option>
                                <option value="Perbaikan Elektrik" {{ $log->reason === 'Perbaikan Elektrik' ? 'selected' : '' }}>Perbaikan Elektrik</option>
                                <option value="Tunggu Program" {{ $log->reason === 'Tunggu Program' ? 'selected' : '' }}>Tunggu Program</option>
                                <option value="Lainnya" {{ $log->reason === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>
                    </div>
                @endif

                {{-- DAILY CHECK UI --}}
                @if($log->entry_type === 'check')
                    <div class="space-y-6">
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
                                            <input type="radio" :name="q.name" value="Ya" x-model="checkValues[q.name]" class="sr-only peer" required>
                                            <div class="px-3 py-1 rounded-md text-[10px] font-bold uppercase transition-all peer-checked:bg-white peer-checked:text-emerald-600 peer-checked:shadow-sm text-slate-400">Ya</div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" :name="q.name" value="Tidak" x-model="checkValues[q.name]" class="sr-only peer">
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
                                        <select name="size_category" x-model="selectedSize" @change="updateStandards" required
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
                                                <input type="radio" name="rpm_feeding_mode" value="kasar" x-model="mode" @change="updateStandards" required class="sr-only peer">
                                                <div class="text-center py-2 rounded-lg text-sm font-bold peer-checked:bg-slate-800 peer-checked:text-white text-slate-400 transition-all">Kasar</div>
                                            </label>
                                            <label class="flex-1 cursor-pointer">
                                                <input type="radio" name="rpm_feeding_mode" value="finish" x-model="mode" @change="updateStandards" class="sr-only peer">
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
                                        <input type="number" name="rpm_value" x-model="rpmInput" required
                                            :class="rpmStatus"
                                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm p-3 font-black">
                                    </div>
                                    <div class="space-y-1.5">
                                        <div class="flex justify-between items-center">
                                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kecepatan Feeding</label>
                                            <span class="text-[10px] font-bold text-slate-400" x-text="'Std: ' + (currentStd.feeding || '-')"></span>
                                        </div>
                                        <input type="number" step="0.01" name="feeding_value" x-model="feedingInput" required
                                            :class="feedingStatus"
                                            class="w-full border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm p-3 font-black">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mt-8 space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider font-bold">Catatan / Keterangan Tambahan</label>
                    <textarea name="note" rows="2"
                        class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm p-3"
                        placeholder="Detail tambahan...">{{ $log->note }}</textarea>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="grid grid-cols-2 gap-3 mt-4">
                <a href="{{ route('daily_report.downtime.show', $log->downtime_date) }}"
                    class="w-full bg-slate-100 text-slate-600 font-bold py-4 rounded-2xl flex items-center justify-center gap-2 hover:bg-slate-200 transition-all">
                    Batal
                </a>
                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2 hover:opacity-90 active:scale-95 transition-all">
                    <span class="material-icons-round">save</span>
                    Simpan Perubahan
                </button>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mt-4">
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
        function editForm() {
            return {
                entryType: '{{ $log->entry_type }}',
                
                // Downtime
                startTime: '{{ $log->start_time ? \Carbon\Carbon::parse($log->start_time)->format("Y-m-d\TH:i") : "" }}',
                endTime: '{{ $log->end_time ? \Carbon\Carbon::parse($log->end_time)->format("Y-m-d\TH:i") : "" }}',
                durationMinutes: {{ $log->duration_minutes ?? 0 }},

                // Daily Check
                selectedSize: '{{ $log->size_category }}',
                mode: '{{ $log->rpm_feeding_mode }}',
                rpmInput: '{{ $log->rpm_value }}',
                feedingInput: '{{ $log->feeding_value }}',
                currentStd: { rpm: null, feeding: null },
                checkValues: {
                    check_cekam: '{{ $log->check_cekam }}',
                    check_air_ozo: '{{ $log->check_air_ozo }}',
                    check_eretan: '{{ $log->check_eretan }}',
                    check_pisau: '{{ $log->check_pisau }}',
                    check_kebersihan: '{{ $log->check_kebersihan }}',
                    check_oli: '{{ $log->check_oli }}'
                },

                questions: [
                    { label: 'Apakah cekam dapat dioperasikan normal?', name: 'check_cekam' },
                    { label: 'Apakah Air ozzon dapat keluar dengan Normal?', name: 'check_air_ozo' },
                    { label: 'Apakah Eretan dapat dioperasikan normal?', name: 'check_eretan' },
                    { label: 'Apakah sudah terpasang 3 pisau pahat?', name: 'check_pisau' },
                    { label: 'Apakah kebersihan mesin terjaga?', name: 'check_kebersihan' },
                    { label: 'Apakah oli hidrolis normal?', name: 'check_oli' }
                ],

                standards: {
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
                    }
                },

                init() {
                    this.updateStandards();
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
