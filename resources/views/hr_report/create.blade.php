@extends('layouts.app')

@section('title', 'Laporan HR Baru')

@section('content')

    <div x-data="hrReportForm()" class="w-full pb-6" style="width: 100%; max-width: none;">
        <!-- Header: More Compact -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('hr_report.index') }}" 
                   class="w-8 h-8 flex items-center justify-center bg-white rounded-lg shadow-sm border border-gray-100 text-gray-400 hover:text-blue-600 transition-all">
                    <span class="material-icons-round text-sm">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-xl font-black text-gray-800 tracking-tight leading-none">Buat Laporan HR Baru</h1>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Dokumentasi Issue & Anomali Sistem</p>
                </div>
            </div>
        </div>

        <form action="{{ route('hr_report.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="space-y-3">
                
                <!-- CARD 1: METADATA (Very Compact) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-icons-round text-blue-500 text-lg">info</span>
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Informasi Laporan</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Preview Number <span class="text-blue-500">(final dibuat saat simpan)</span></label>
                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-2.5">
                                    <p class="text-[11px] font-mono font-bold text-blue-700">{{ $reportNumber }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Tanggal Terbit</label>
                                <div class="bg-gray-50 border border-gray-100 rounded-xl p-2.5 flex items-center gap-2">
                                    <span class="material-icons-round text-gray-400 text-sm">calendar_today</span>
                                    <p class="text-[11px] font-bold text-gray-700">{{ \Carbon\Carbon::parse($today)->isoFormat('dddd, D MMM YYYY') }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5 text-blue-500">Status Awal</label>
                                <div class="w-full bg-blue-50 border border-blue-100 rounded-xl px-4 h-10 flex items-center">
                                    <span class="text-[11px] font-black text-blue-600 uppercase tracking-widest">🔵 OPEN</span>
                                </div>
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
                                <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Kategori Masalah <span class="text-red-500">*</span> <span class="text-gray-400 text-[9px] font-normal ml-1">(wajib diisi)</span></label>
                                <select name="category" required x-model="category"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all h-10 px-3">
                                    <option value="" disabled selected>Pilih Kategori</option>
                                    <option value="Cycle Time">Cycle Time</option>
                                    <option value="KPI Operator">KPI Operator</option>
                                    <option value="Downtime Mesin">Downtime Mesin</option>
                                    <option value="Quality / Scrap">Quality / Scrap</option>
                                    <option value="Disiplin Operator">Disiplin Operator</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="w-64 shrink-0 relative" @click.outside="showOperatorSuggestions = false">
                                <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Nama Operator <span class="text-red-500">*</span> <span class="text-gray-400 text-[9px] font-normal ml-1">(wajib diisi)</span></label>
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
                                <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Judul Laporan <span class="text-red-500">*</span> <span class="text-gray-400 text-[9px] font-normal ml-1">(wajib diisi)</span></label>
                                <input type="text" name="title" required x-model="title" placeholder="Misal: Penurunan KPI Signifikan Shift 1"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all h-10 px-3">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Deskripsi Masalah <span class="text-red-500">*</span> <span class="text-gray-400 text-[9px] font-normal ml-1">(wajib diisi)</span></label>
                            <textarea name="description" required rows="3" placeholder="Jelaskan detail anomali yang Anda temukan..."
                                class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-blue-400 transition-all p-3"></textarea>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Link Bukti Data (Traceable URL) <span class="text-red-500">*</span> <span class="text-gray-400 text-[9px] font-normal ml-1">(wajib diisi)</span></label>
                            <div class="relative">
                                <input type="url" name="data_link" required placeholder="http://10.88.8.97/kpi-bubut/public/daily-report/operator/show/..."
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 pl-10 focus:border-blue-400 transition-all h-10 px-3">
                                <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">link</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: ANALYSIS & ACTION (Most Compact) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-icons-round text-green-500 text-lg">psychology</span>
                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Analisis & Tindakan</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1.5 uppercase tracking-tighter">Penyebab Masalah (Root Cause) <span class="text-gray-400 text-[9px] font-normal lowercase ml-1">(wajib sebelum Submit)</span></label>
                                <textarea name="root_cause" rows="12" placeholder="Faktor penyebab utama..."
                                    class="w-full h-[320px] min-h-[320px] resize-y bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-orange-400 transition-all p-3"
                                    style="height: 320px !important; min-height: 320px !important;">{{ old('root_cause') }}</textarea>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-green-600 mb-1.5 uppercase tracking-tighter">Tindakan Perbaikan (Corrective Action) <span class="text-gray-400 text-[9px] font-normal lowercase ml-1">(wajib sebelum Submit)</span></label>
                                <textarea name="corrective_action" rows="12" placeholder="Langkah perbaikan..."
                                    class="w-full h-[320px] min-h-[320px] resize-y bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-green-400 transition-all p-3"
                                    style="height: 320px !important; min-height: 320px !important;">{{ old('corrective_action') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Target Penyelesaian <span class="text-gray-400 text-[9px] font-normal ml-1">(wajib sebelum Submit)</span></label>
                            <input type="date" name="target_completion_date" value="{{ old('target_completion_date') }}"
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
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Hasil Monitoring <span class="text-gray-400 text-[9px] font-normal ml-1">(wajib sebelum Submit)</span></label>
                            <textarea name="monitoring_result" placeholder="Tuliskan hasil pengecekan atau monitoring di lapangan..."
                                class="w-full min-h-[320px] h-[320px] resize-y bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-purple-400 transition-all p-3"
                                style="height: 320px !important; min-height: 320px !important;">{{ old('monitoring_result') }}</textarea>
                        </div>

                        <div x-data="{ 
                            files: [],
                            addFiles(e) {
                                const newFiles = Array.from(e.target.files);
                                this.files = [...this.files, ...newFiles];
                            },
                            removeFile(index) {
                                this.files.splice(index, 1);
                                // Note: This doesn't actually remove from the input element's files list
                                // because that's read-only. We'll handle this by syncing if needed,
                                // but for a simple multi-upload, usually we just append.
                                // However, to truly support 'remove', we need a workaround or a different approach.
                                // For now, let's keep it simple: the UI shows what's selected.
                                // If they remove from UI, we'll need to reconstruct the DataTransfer.
                                const dt = new DataTransfer();
                                this.files.forEach(file => dt.items.add(file));
                                $refs.fileInput.files = dt.files;
                            },
                            formatSize(bytes) {
                                if (bytes === 0) return '0 Bytes';
                                const k = 1024;
                                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                                const i = Math.floor(Math.log(bytes) / Math.log(k));
                                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                            }
                        }">
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Lampiran Bukti (Multiple) <span class="text-gray-400 text-[9px] font-normal ml-1">(wajib sebelum Submit)</span></label>
                            
                            <!-- Custom Upload Button -->
                            <div class="relative group">
                                <input type="file" name="evidence_files[]" multiple accept=".jpg,.jpeg,.png,.pdf"
                                    x-ref="fileInput"
                                    @change="addFiles"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                
                                <div class="w-full bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl p-6 flex flex-col items-center justify-center gap-2 group-hover:border-blue-400 group-hover:bg-blue-50/50 transition-all">
                                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform">
                                        <span class="material-icons-round text-2xl">cloud_upload</span>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-[11px] font-bold text-gray-700">Klik atau Tarik File ke Sini</p>
                                        <p class="text-[9px] text-gray-400 mt-0.5">Maksimal 5MB per file (JPG, PNG, PDF)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Files List -->
                            <template x-if="files.length > 0">
                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <template x-for="(file, index) in files" :key="index">
                                        <div class="flex items-center justify-between p-2.5 bg-white border border-gray-100 rounded-xl shadow-sm">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center shrink-0">
                                                    <span class="material-icons-round text-sm text-gray-400" x-text="file.type.includes('pdf') ? 'picture_as_pdf' : 'image'"></span>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-bold text-gray-700 truncate" x-text="file.name"></p>
                                                    <p class="text-[9px] text-gray-400" x-text="formatSize(file.size)"></p>
                                                </div>
                                            </div>
                                            <button type="button" @click="removeFile(index)" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all">
                                                <span class="material-icons-round text-sm">close</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <p class="text-[9px] text-gray-400 mt-2 italic">* Wajib dilampirkan sebelum mengajukan approval (Status Closed terjadi saat Approve).</p>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1.5">Catatan Tambahan (Opsional)</label>
                            <textarea name="additional_notes" placeholder="Catatan atau informasi tambahan lainnya..."
                                class="w-full min-h-[180px] h-[180px] resize-y bg-gray-50 border-gray-200 rounded-xl text-xs font-medium text-gray-700 focus:border-gray-400 transition-all p-3"
                                style="height: 180px !important; min-height: 180px !important;">{{ old('additional_notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS: Fixed at bottom or tight -->
                <div class="flex items-center justify-between pt-4">
                    <button type="reset" 
                            class="px-5 py-2 rounded-xl text-xs font-bold text-gray-400 hover:text-gray-600 transition-all">
                        Reset Form
                    </button>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('hr_report.index') }}" 
                           class="px-6 py-2 rounded-xl text-xs font-bold text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 transition-all">
                            Batal
                        </a>
                        <button type="submit" 
                                class="px-14 py-4 bg-blue-600 text-white rounded-2xl text-sm font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-3">
                             <span class="material-icons-round text-lg">save</span>
                             Simpan Laporan Baru
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
            category: '',
            title: '',
            operatorSearch: '',
            selectedOperatorName: '',
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
    /* Compact scrollbar for textareas */
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
