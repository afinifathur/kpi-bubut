@extends('layouts.app')

@section('title', 'Edit Laporan HR')

@section('content')

    <div x-data="hrReportForm()" class="w-full pb-6" style="width: 100%; max-width: none;">
        <!-- Header: Consistent with Create -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('hr_report.show', $report->id) }}" 
                   class="w-8 h-8 flex items-center justify-center bg-white rounded-lg shadow-sm border border-gray-100 text-gray-400 hover:text-blue-600 transition-all">
                    <span class="material-icons-round text-sm">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-xl font-black text-gray-800 tracking-tight leading-none">Edit Laporan HR</h1>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Perbarui Informasi Issue <b>{{ $report->report_number }}</b></p>
                </div>
            </div>
        </div>

        <form action="{{ route('hr_report.update', $report->id) }}" method="POST" enctype="multipart/form-data" id="editReportForm" onsubmit="return handleEditSubmit(event, this)">
            @csrf
            @method('PUT')

            <script>
                function handleEditSubmit(event, form) {
                    const status = form.querySelector('select[name="status"]').value;
                    
                    if (status === 'Closed') {
                        event.preventDefault();
                        Swal.fire({
                            title: 'Closed laporan ini?',
                            text: "Laporan yang sudah close tidak bisa diedit kembali!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#10b981',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Ya, lanjutkan',
                            cancelButtonText: 'Tidak',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                        return false;
                    }
                    return true;
                }
            </script>
            
            <div class="space-y-3">
                
                <!-- CARD 1: METADATA (Consistent & Compact) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-icons-round text-blue-500 text-lg">info</span>
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Informasi Laporan</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">ID Laporan</label>
                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-2.5">
                                    <p class="text-[11px] font-mono font-bold text-blue-700">{{ $report->report_number }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Tanggal Terbit</label>
                                <div class="bg-gray-50 border border-gray-100 rounded-xl p-2.5 flex items-center gap-2">
                                    <span class="material-icons-round text-gray-400 text-sm">calendar_today</span>
                                    <p class="text-[11px] font-bold text-gray-700">{{ \Carbon\Carbon::parse($report->report_date)->isoFormat('dddd, D MMM YYYY') }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5 text-blue-500">Update Status</label>
                                <select name="status" required
                                    class="w-full bg-blue-50 border-blue-100 rounded-xl text-[11px] font-black text-blue-600 focus:ring-0 h-10 px-3 cursor-pointer">
                                    @foreach(['Open' => '🔴 OPEN', 'Investigating' => '🟠 INVESTIGATING', 'Action Plan' => '🔵 ACTION PLAN', 'Monitoring' => '🟣 MONITORING', 'Closed' => '🟢 CLOSED'] as $val => $label)
                                        <option value="{{ $val }}" {{ $report->status == $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: ISSUE DETAILS -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-icons-round text-orange-400 text-lg">report_problem</span>
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Detail Masalah</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="flex gap-4 w-full items-start">
                            <div class="w-64 shrink-0">
                                <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Kategori Masalah <span class="text-red-500">*</span></label>
                                <select name="category" required x-model="category"
                                     class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all h-10 px-3">
                                     @foreach(['Cycle Time', 'KPI Operator', 'Downtime Mesin', 'Quality / Scrap', 'Disiplin Operator', 'Maintenance', 'Lainnya'] as $cat)
                                         <option value="{{ $cat }}" {{ $report->category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                     @endforeach
                                 </select>
                            </div>
                            <div class="w-64 shrink-0 relative" @click.outside="showOperatorSuggestions = false">
                                <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Nama Operator</label>
                                <div class="relative">
                                    <input type="text" x-model="operatorSearch" @input.debounce.300ms="searchOperators"
                                        @focus="searchOperators"
                                        placeholder="Cari Operator..." autocomplete="off"
                                        class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all h-10 px-3 pl-8">
                                    <span class="material-icons-round absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">person_search</span>
                                    <input type="hidden" name="operator_name" x-model="selectedOperatorName">
                                </div>
                                <!-- Suggestions -->
                                <div x-show="showOperatorSuggestions && operatorList.length > 0"
                                    class="absolute z-50 w-full bg-white border border-gray-200 rounded-xl shadow-xl mt-1 max-h-48 overflow-y-auto overflow-x-hidden"
                                    style="display: none;">
                                    <template x-for="op in operatorList" :key="op.code">
                                        <div @click="selectOperator(op)"
                                            class="p-2.5 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-none transition-colors">
                                            <p class="text-[11px] font-bold text-gray-700" x-text="op.name"></p>
                                            <p class="text-[9px] text-gray-400" x-text="op.code"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Judul Laporan <span class="text-red-500">*</span></label>
                                <input type="text" name="title" x-model="title" required placeholder="Misal: Penurunan KPI Signifikan Shift 1"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all h-10 px-3">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Deskripsi Masalah <span class="text-red-500">*</span></label>
                            <textarea name="description" required rows="3" placeholder="Jelaskan detail anomali yang Anda temukan..."
                                class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all p-3">{{ $report->description }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Link Bukti Data (Traceable URL)</label>
                            <div class="relative">
                                <input type="url" name="data_link" value="{{ $report->data_link }}" placeholder="http://10.88.8.97/kpi-bubut/public/daily-report/operator/show/..."
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 pl-10 focus:border-blue-400 transition-all h-10 px-3">
                                <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">link</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: ANALYSIS & ACTION -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-icons-round text-green-500 text-lg">psychology</span>
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Analisis & Tindakan</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1.5 uppercase tracking-tighter">Penyebab Masalah (Root Cause)</label>
                                <textarea name="root_cause" rows="12" placeholder="Faktor penyebab utama..."
                                    class="w-full h-[320px] min-h-[320px] resize-y bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-orange-400 transition-all p-3"
                                    style="height: 320px !important; min-height: 320px !important;">{{ old('root_cause', $report->root_cause) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-green-600 mb-1.5 uppercase tracking-tighter">Tindakan Perbaikan (Corrective Action)</label>
                                <textarea name="corrective_action" rows="12" placeholder="Langkah perbaikan..."
                                    class="w-full h-[320px] min-h-[320px] resize-y bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-green-400 transition-all p-3"
                                    style="height: 320px !important; min-height: 320px !important;">{{ old('corrective_action', $report->corrective_action) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Target Penyelesaian <span class="text-red-500">*</span></label>
                            <input type="date" name="target_completion_date" required value="{{ old('target_completion_date', $report->target_completion_date ? $report->target_completion_date->format('Y-m-d') : '') }}"
                                class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all h-10 px-3">
                        </div>
                    </div>
                </div>

                <!-- CARD 4: MONITORING & EVIDENCE -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-icons-round text-purple-500 text-lg">visibility</span>
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Investigasi & Monitoring</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Hasil Monitoring <span class="text-gray-400 text-[9px] font-normal ml-1">(wajib diisi)</span></label>
                            <textarea name="monitoring_result" placeholder="Tuliskan hasil pengecekan atau monitoring di lapangan..."
                                class="w-full min-h-[320px] h-[320px] resize-y bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-purple-400 transition-all p-3"
                                style="height: 320px !important; min-height: 320px !important;">{{ old('monitoring_result', $report->monitoring_result) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Lampiran Bukti (Multiple) <span class="text-gray-400 text-[9px] font-normal">(JPG, PNG, PDF)</span></label>
                            
                            @if($report->evidence_files && count($report->evidence_files) > 0)
                                <div class="mb-3 grid grid-cols-2 md:grid-cols-4 gap-2">
                                    @foreach($report->evidence_files as $file)
                                        <div class="p-2 bg-gray-50 border border-gray-100 rounded-lg flex items-center justify-between">
                                            <span class="text-[10px] text-gray-600 truncate mr-2">{{ $file['name'] }}</span>
                                            <a href="{{ asset('storage/' . $file['path']) }}" target="_blank" class="text-blue-500 hover:text-blue-700">
                                                <span class="material-icons-round text-sm">open_in_new</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <input type="file" name="evidence_files[]" multiple accept=".jpg,.jpeg,.png,.pdf"
                                class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all p-2">
                            <p class="text-[9px] text-gray-400 mt-1 italic">* Wajib dilampirkan jika ingin menutup laporan (Status Closed).</p>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Catatan Tambahan (Opsional)</label>
                            <textarea name="additional_notes" placeholder="Catatan atau informasi tambahan lainnya..."
                                class="w-full min-h-[180px] h-[180px] resize-y bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-gray-400 transition-all p-3"
                                style="height: 180px !important; min-height: 180px !important;">{{ old('additional_notes', $report->additional_notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="flex items-center justify-between pt-4">
                    <div class="text-[9px] text-gray-400 italic">
                        Terakhir diupdate: {{ $report->updated_at->diffForHumans() }}
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('hr_report.show', $report->id) }}" 
                           class="px-6 py-2 rounded-xl text-xs font-bold text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 transition-all">
                            Batal
                        </a>
                        <button type="submit" 
                                class="px-14 py-4 bg-blue-600 text-white rounded-2xl text-sm font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-3">
                             <span class="material-icons-round text-lg">save</span>
                             Perbarui Laporan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
<script>
    function hrReportForm() {
        return {
            category: '{{ $report->category }}',
            title: '{{ $report->title }}',
            operatorSearch: '{{ $report->operator_name }}',
            selectedOperatorName: '{{ $report->operator_name }}',
            operatorList: [],
            showOperatorSuggestions: false,

            async searchOperators() {
                try {
                    const res = await fetch(`{{ route('api.search.operators') }}?q=${encodeURIComponent(this.operatorSearch)}`);
                    this.operatorList = await res.json();
                    this.showOperatorSuggestions = true;
                } catch (e) {
                    console.error('Error searching operators:', e);
                }
            },

            selectOperator(op) {
                this.selectedOperatorName = op.name;
                this.operatorSearch = op.name;
                this.showOperatorSuggestions = false;

                // Autofill Title if Category is KPI Operator
                if (this.category === 'KPI Operator' && (this.title === '' || this.title.startsWith('KPI an '))) {
                    this.title = 'KPI an ' + op.name;
                }
            }
        }
    }
</script>
@endpush

@push('styles')
<style>
    /* Compact scrollbar */
    textarea {
        scrollbar-width: thin;
        scrollbar-color: rgba(0,0,0,0.1) transparent;
    }
    textarea::-webkit-scrollbar {
        width: 4px;
    }
    textarea::-webkit-scrollbar-thumb {
        background-color: rgba(0,0,0,0.1);
        border-radius: 10px;
    }
</style>
@endpush
