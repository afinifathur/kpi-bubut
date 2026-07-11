@extends('layouts.app')

@section('title', 'Edit Data Produksi')

@section('content')
    @php
        $cycleMinutes = intdiv($log->cycle_time_used_sec, 60);
        $cycleSeconds = $log->cycle_time_used_sec % 60;
    @endphp

    <div x-data="editForm()" class="max-w-4xl mx-auto pb-24">

        {{-- Header --}}
        <div class="mb-8 flex items-center gap-3">
            <a href="{{ route('daily_report.operator.show', $log->production_date) }}"
                class="text-gray-400 hover:text-gray-600 transition-colors">
                <span class="material-icons-round">arrow_back</span>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Edit Data Produksi</h1>
                <p class="text-sm text-slate-500">
                    {{ \Carbon\Carbon::parse($log->production_date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                </p>
            </div>
        </div>

        {{-- Form --}}
        <form id="edit-form" action="{{ route('daily_report.operator.update', $log->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Section 1: Info Terkunci --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <span class="material-icons-round text-slate-400">lock</span>
                    <h2 class="font-bold text-lg text-slate-700">Data Terkunci</h2>
                    <span class="text-xs text-slate-400 ml-auto">Tidak dapat diubah</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Operator</label>
                        <div
                            class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500">
                            {{ $log->operator->name ?? $log->operator_code }}
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Mesin</label>
                        <div
                            class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500">
                            {{ $log->machine_code }}
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Item</label>
                        <div class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500 truncate"
                            title="{{ $log->item->name ?? $log->item_code }}">
                            {{ $log->item->name ?? $log->item_code }}
                        </div>
                    </div>
                </div>

                @if($log->heat_number)
                    <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Heat
                                Number</label>
                            <div
                                class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500">
                                {{ $log->heat_number }}
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Size</label>
                            <div
                                class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500">
                                {{ $log->size ?? '-' }}
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Customer</label>
                            <div
                                class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500">
                                {{ $log->customer ?? '-' }}
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Line</label>
                            <div
                                class="w-full bg-slate-100 border-transparent rounded-xl text-sm p-3 font-medium text-slate-500">
                                {{ $log->line ?? '-' }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Section 2: Data yang Bisa Diedit --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-blue-100">
                <div class="flex items-center gap-2 mb-6 border-b border-blue-50 pb-4">
                    <span class="material-icons-round text-blue-500">edit</span>
                    <h2 class="font-bold text-lg text-slate-700">Data yang Dapat Diedit</h2>
                </div>

                {{-- Row 1: Tanggal, Shift, Waktu Mulai, Waktu Selesai --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal</label>
                        <input type="date" name="production_date" value="{{ $log->production_date }}" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Shift</label>
                        <select name="shift"
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                            <option value="1" {{ $log->shift == '1' ? 'selected' : '' }}>Shift 1 (07:00-15:00)</option>
                            <option value="2" {{ $log->shift == '2' ? 'selected' : '' }}>Shift 2 (15:00-23:00)</option>
                            <option value="3" {{ $log->shift == '3' ? 'selected' : '' }}>Shift 3 (23:00-07:00)</option>
                            <option value="non_shift" {{ $log->shift == 'non_shift' ? 'selected' : '' }}>Non Shift</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Waktu Mulai</label>
                        <input type="time" name="time_start" x-model="timeStart" @change="calculateTarget"
                            value="{{ \Carbon\Carbon::parse($log->time_start)->format('H:i') }}" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Waktu Selesai</label>
                        <input type="time" name="time_end" x-model="timeEnd" @change="calculateTarget"
                            value="{{ \Carbon\Carbon::parse($log->time_end)->format('H:i') }}" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                    </div>
                </div>

                {{-- Row 2: Cycle Time, Target (Auto), Hasil --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Cycle Time
                            (Manual)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex items-center">
                                <input type="number" name="cycle_time_minutes" x-model="cycleTimeMinutes"
                                    @input="calculateTarget" required min="0" value="{{ $cycleMinutes }}"
                                    class="w-full bg-white border-slate-200 rounded-l-xl border-r-0 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700"
                                    placeholder="0">
                                <span
                                    class="bg-slate-50 border border-slate-200 border-l-0 text-slate-500 text-xs font-bold px-3 py-3 rounded-r-xl flex items-center h-full">
                                    MENIT
                                </span>
                            </div>
                            <div class="flex items-center">
                                <input type="number" name="cycle_time_seconds" x-model="cycleTimeSeconds"
                                    @input="calculateTarget" required min="0" max="59" value="{{ $cycleSeconds }}"
                                    class="w-full bg-white border-slate-200 rounded-l-xl border-r-0 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700"
                                    placeholder="0">
                                <span
                                    class="bg-slate-50 border border-slate-200 border-l-0 text-slate-500 text-xs font-bold px-3 py-3 rounded-r-xl flex items-center h-full">
                                    DETIK
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Target (Auto)</label>
                        <input type="number" readonly x-model="targetQty"
                            class="w-full bg-slate-100 border-transparent rounded-xl text-center font-bold text-slate-600 text-lg p-3 cursor-not-allowed">
                        <p class="text-[10px] text-center text-slate-400">Berdasarkan Cycle Time</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Hasil (OK)</label>
                        <input type="number" name="actual_qty" x-model="actualQty" @input="calculateAchievement" required
                            min="0" value="{{ $log->actual_qty }}"
                            class="w-full bg-white border-blue-300 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 text-center font-bold text-blue-700 text-lg p-3"
                            placeholder="0">
                    </div>
                </div>

                {{-- Row 3: Keterangan & Capaian --}}
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Keterangan
                            (Opsional)</label>
                        <select name="remark"
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700">
                            <option value="" {{ empty($log->remark) ? 'selected' : '' }}>Normal (Selesai)</option>
                            <option value="Setengah jadi" {{ $log->remark == 'Setengah jadi' ? 'selected' : '' }}>Setengah
                                jadi</option>
                            <option value="K1-1 sisi" {{ $log->remark == 'K1-1 sisi' ? 'selected' : '' }}>K1-1 sisi</option>
                            <option value="K1-2 sisi" {{ $log->remark == 'K1-2 sisi' ? 'selected' : '' }}>K1-2 sisi</option>
                            <option value="K1- Finish ID" {{ $log->remark == 'K1- Finish ID' ? 'selected' : '' }}>K1- Finish
                                ID</option>
                            <option value="Finish 1 sisi" {{ $log->remark == 'Finish 1 sisi' ? 'selected' : '' }}>Finish 1
                                sisi</option>
                            <option value="FINISH KASARAN" {{ $log->remark == 'FINISH KASARAN' ? 'selected' : '' }}>FINISH
                                KASARAN</option>
                            <option value="K1,K2" {{ $log->remark == 'K1,K2' ? 'selected' : '' }}>K1,K2</option>
                            <option value="KOD,FOD,K1,K2,FID" {{ $log->remark == 'KOD,FOD,K1,K2,FID' ? 'selected' : '' }}>
                                KOD,FOD,K1,K2,FID</option>
                            <option value="K1,K2,FOD" {{ $log->remark == 'K1,K2,FOD' ? 'selected' : '' }}>K1,K2,FOD</option>
                            <option value="KID,FID" {{ $log->remark == 'KID,FID' ? 'selected' : '' }}>KID,FID</option>
                        </select>
                    </div>
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

                {{-- Row 4: Catatan --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Catatan (Opsional)</label>
                    <input type="text" name="note" value="{{ $log->note }}"
                        class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700"
                        placeholder="Keterangan tambahan...">
                </div>
            </div>

            {{-- Error Messages --}}
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl">
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Buttons --}}
            <div class="grid grid-cols-2 gap-3 mt-2">
                <a href="{{ route('daily_report.operator.show', $log->production_date) }}" style="background:#ef4444"
                    class="py-4 text-white font-bold rounded-2xl flex items-center justify-center gap-2 hover:opacity-90 active:scale-[0.98] transition-all shadow-lg">
                    <span class="material-icons-round text-[20px]">close</span>
                    Batal
                </a>
                <button type="button" @click="confirmUpdate" style="background:#2563eb"
                    class="py-4 text-white font-bold rounded-2xl flex items-center justify-center gap-2 hover:opacity-90 active:scale-[0.98] transition-all shadow-lg">
                    <span class="material-icons-round text-[20px]">save</span>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <script>
        function editForm() {
            return {
                timeStart: '{{ \Carbon\Carbon::parse($log->time_start)->format("H:i") }}',
                timeEnd: '{{ \Carbon\Carbon::parse($log->time_end)->format("H:i") }}',
                cycleTimeMinutes: {{ $cycleMinutes }},
                cycleTimeSeconds: {{ $cycleSeconds }},
                targetQty: {{ $log->target_qty }},
                actualQty: {{ $log->actual_qty }},
                achievement: {{ $log->achievement_percent }},

                calculateTarget() {
                    const mins = parseInt(this.cycleTimeMinutes) || 0;
                    const secs = parseInt(this.cycleTimeSeconds) || 0;
                    const totalCycleTimeSec = (mins * 60) + secs;

                    if (!this.timeStart || !this.timeEnd || totalCycleTimeSec <= 0) {
                        this.targetQty = 0;
                        return;
                    }

                    const start = this.parseTime(this.timeStart);
                    const end = this.parseTime(this.timeEnd);

                    let diffSeconds = (end - start) * 60;
                    if (diffSeconds < 0) diffSeconds += 24 * 60 * 60; // Handle cross-midnight

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
                    return parseInt(h) * 60 + parseInt(m);
                },

                confirmUpdate() {
                    Swal.fire({
                        title: 'Simpan Perubahan?',
                        text: 'Data produksi akan diperbarui dan KPI akan dihitung ulang.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#2563eb',
                        cancelButtonColor: '#dc2626',
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('edit-form').submit();
                        }
                    });
                }
            }
        }
    </script>
@endsection