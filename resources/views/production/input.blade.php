@extends('layouts.app')

@section('title', 'Input Hasil Produksi')

@section('content')
    <div x-data="productionForm()" class="max-w-4xl mx-auto pb-28 md:pb-12">

        {{-- Header --}}
        <div class="mb-5 sm:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Input Hasil Produksi</h1>
                <p class="text-xs sm:text-sm text-slate-500">Departemen Bubut • KPI Tracking System</p>
            </div>
            {{-- Quick Scan Button for Mobile Header --}}
            <button type="button" @click="startScanner()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-xs sm:text-sm rounded-xl shadow-md shadow-blue-500/20 transition-all">
                <span class="material-icons-round text-lg">qr_code_scanner</span>
                <span>Scan Kitir</span>
            </button>
        </div>

        {{-- Form Section --}}
        <form id="production-form" action="{{ route('production.store') }}" method="POST" class="space-y-4 sm:space-y-6">
            @csrf

            {{-- Section 1: Waktu & Shift --}}
            <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-xs border border-slate-100">
                <div class="flex items-center gap-2 mb-4 sm:mb-5 border-b border-slate-100 pb-3">
                    <span class="material-icons-round text-blue-500 text-xl">calendar_today</span>
                    <h2 class="font-bold text-base sm:text-lg text-slate-700">Waktu & Shift</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5 sm:gap-4">
                    {{-- Tanggal --}}
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal</label>
                        <input type="date" name="production_date" value="{{ date('Y-m-d', strtotime('-1 day')) }}"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 min-h-[46px]">
                    </div>

                    {{-- Shift --}}
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Shift</label>
                        <select name="shift" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 min-h-[46px]">
                            <option value="1">Shift 1 (07:00-15:00)</option>
                            <option value="2">Shift 2 (15:00-23:00)</option>
                            <option value="3">Shift 3 (23:00-07:00)</option>
                            <option value="non_shift">Non Shift</option>
                        </select>
                    </div>

                    {{-- Waktu Mulai --}}
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Waktu Mulai</label>
                        <input type="time" name="time_start" x-model="timeStart" @change="calculateTarget" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 min-h-[46px]">
                    </div>

                    {{-- Waktu Selesai --}}
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Waktu Selesai</label>
                        <input type="time" name="time_end" x-model="timeEnd" @change="calculateTarget" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 min-h-[46px]">
                    </div>
                </div>
            </div>

            {{-- Section 2: Sumber Daya --}}
            <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-xs border border-slate-100">
                <div class="flex items-center gap-2 mb-4 sm:mb-5 border-b border-slate-100 pb-3">
                    <span class="material-icons-round text-blue-500 text-xl">group_work</span>
                    <h2 class="font-bold text-base sm:text-lg text-slate-700">Sumber Daya</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4">
                    {{-- Operator Search --}}
                    <div class="space-y-1 relative" @click.outside="showOperatorSuggestions = false">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Operator</label>
                        <div class="relative">
                            <input type="text" x-model="operatorSearch" @input.debounce.300ms="searchOperators"
                                placeholder="Cari Operator..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 pl-10 min-h-[46px]">
                            <span class="material-icons-round absolute left-3 top-3 text-slate-400 text-lg">person_search</span>
                            <input type="hidden" name="operator_code" x-model="selectedOperatorCode" required>
                        </div>
                        {{-- Operator Suggestions --}}
                        <div x-show="showOperatorSuggestions && operatorList.length > 0"
                            class="absolute z-20 w-full bg-white border border-slate-200 rounded-xl shadow-xl mt-1 max-h-56 overflow-y-auto"
                            style="display: none;">
                            <template x-for="op in operatorList" :key="op.code">
                                <div @click="selectOperator(op)"
                                    class="p-3 hover:bg-blue-50 active:bg-blue-100 cursor-pointer border-b border-slate-50 last:border-none">
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
                    <div class="space-y-1 relative" @click.outside="showMachineSuggestions = false">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Mesin</label>
                        <div class="relative">
                            <input type="text" x-model="machineSearch" @input.debounce.300ms="searchMachines"
                                placeholder="Cari Mesin..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 pl-10 min-h-[46px]"
                                autocomplete="off">
                            <span class="material-icons-round absolute left-3 top-3 text-slate-400 text-lg">precision_manufacturing</span>
                            <input type="hidden" name="machine_code" x-model="selectedMachineCode" required>
                        </div>
                        {{-- Machine Suggestions --}}
                        <div x-show="showMachineSuggestions && machineList.length > 0"
                            class="absolute z-20 w-full bg-white border border-slate-200 rounded-xl shadow-xl mt-1 max-h-56 overflow-y-auto"
                            style="display: none;">
                            <template x-for="machine in machineList" :key="machine.code">
                                <div @click="selectMachine(machine)"
                                    class="p-3 hover:bg-blue-50 active:bg-blue-100 cursor-pointer border-b border-slate-50 last:border-none">
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

            {{-- Section 3: ITEM & HASIL --}}
            <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-xs border border-slate-100">
                <div class="flex items-center justify-between mb-4 sm:mb-5 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-icons-round text-blue-500 text-xl">inventory_2</span>
                        <h2 class="font-bold text-base sm:text-lg text-slate-700">Item & Hasil</h2>
                    </div>
                    <span x-show="resolvedKtr" class="text-[11px] font-mono bg-blue-50 text-blue-700 px-2 py-0.5 rounded-md font-semibold border border-blue-200/60" x-text="resolvedKtr"></span>
                </div>

                <div class="space-y-4 sm:space-y-5">

                    {{-- Scanner & Selection Trigger Area --}}
                    <div class="bg-slate-50/80 p-3.5 sm:p-4 rounded-xl border border-slate-200/80 space-y-3">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Identifikasi Kitir / Heat Number</label>

                        {{-- Action Buttons --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            {{-- Button Scan Kitir (Primary) --}}
                            <button type="button" @click="startScanner()"
                                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3 px-4 rounded-xl shadow-md shadow-blue-500/20 flex items-center justify-center gap-2 transition-transform active:scale-[0.98] min-h-[48px]">
                                <span class="material-icons-round text-xl">photo_camera</span>
                                <span>Scan Kitir (Barcode)</span>
                            </button>

                            {{-- Button Manual Search Toggle --}}
                            <button type="button" @click="showManualSearch = !showManualSearch"
                                class="w-full bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2 transition-all min-h-[48px]">
                                <span class="material-icons-round text-slate-500 text-lg">search</span>
                                <span x-text="showManualSearch ? 'Sembunyikan Cari Manual' : 'Cari Heat Number Manual'"></span>
                            </button>
                        </div>

                        {{-- Manual Heat Number Autocomplete (Fallback / Expandable) --}}
                        <div x-show="showManualSearch" x-transition.duration.200ms class="pt-2 border-t border-slate-200/60" style="display: none;">
                            <div class="space-y-1 relative" @click.outside="showHeatNumberSuggestions = false">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Cari Heat Number Manual</label>
                                <div class="relative">
                                    <input type="text" x-model="heatNumberSearch" @input.debounce.300ms="searchHeatNumbers"
                                        placeholder="Ketik Heat Number / Nama Item..."
                                        class="w-full bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 pl-10 min-h-[46px]"
                                        autocomplete="off">
                                    <span class="material-icons-round absolute left-3 top-3 text-slate-400 text-lg">qr_code</span>
                                </div>
                                <div x-show="showHeatNumberSuggestions && heatNumberList.length > 0"
                                    class="absolute z-20 w-full bg-white border border-slate-200 rounded-xl shadow-xl mt-1 max-h-56 overflow-y-auto"
                                    style="display: none;">
                                    <template x-for="hn in heatNumberList" :key="hn.id">
                                        <div @click="selectHeatNumber(hn)"
                                            class="p-3 hover:bg-blue-50 active:bg-blue-100 cursor-pointer border-b border-slate-50 last:border-none">
                                            <p class="text-sm font-bold text-slate-700" x-text="hn.heat_number"></p>
                                            <p class="text-xs text-slate-400" x-text="hn.item_name"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden Form Controls for Submission --}}
                    <input type="hidden" name="heat_number" x-model="selectedHeatNumber">
                    <input type="hidden" name="item_code" x-model="selectedItemCode" required>

                    {{-- Item Specs Display Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4">
                        {{-- Heat Number Selected --}}
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Heat Number</label>
                            <input type="text" :value="selectedHeatNumber || '-'" readonly
                                class="w-full bg-slate-100/90 border border-slate-200 rounded-xl text-sm p-3 font-mono font-bold text-slate-700 cursor-not-allowed min-h-[46px]"
                                placeholder="-">
                        </div>

                        {{-- Nama Barang (Readonly) --}}
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Barang</label>
                            <input type="text" :value="selectedItemName || '-'" readonly
                                class="w-full bg-slate-100/90 border border-slate-200 rounded-xl text-sm p-3 font-semibold text-slate-700 cursor-not-allowed min-h-[46px]"
                                placeholder="-">
                        </div>
                    </div>

                    {{-- Row 2: Size, Line, Customer (Responsive 3 Columns) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Size</label>
                            <input type="text" :value="selectedSize || '-'" readonly
                                class="w-full bg-slate-100/90 border border-slate-200 rounded-xl text-sm p-3 font-medium text-slate-600 cursor-not-allowed min-h-[46px]"
                                placeholder="-">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Line</label>
                            <input type="text" :value="selectedLine || '-'" readonly
                                class="w-full bg-slate-100/90 border border-slate-200 rounded-xl text-sm p-3 font-medium text-slate-600 cursor-not-allowed min-h-[46px]"
                                placeholder="-">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Customer</label>
                            <input type="text" :value="selectedCustomer || '-'" readonly
                                class="w-full bg-slate-100/90 border border-slate-200 rounded-xl text-sm p-3 font-medium text-slate-600 cursor-not-allowed min-h-[46px]"
                                placeholder="-">
                        </div>
                    </div>

                    {{-- Row 3: Cycle Time, Target, Hasil --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 sm:gap-4 pt-1">
                        {{-- Cycle Time (Manual) --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Cycle Time (Manual)</label>
                                <span x-show="avgCycleTimeText" class="text-[10px] text-blue-600 font-semibold" x-text="'Rata-rata: ' + avgCycleTimeText"></span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="flex items-center">
                                    <input type="number" name="cycle_time_minutes" x-model="cycleTimeMinutes"
                                        @input="calculateTarget" required min="0" value="0" inputmode="numeric"
                                        class="w-full bg-white border border-slate-200 rounded-l-xl border-r-0 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 min-h-[46px]"
                                        placeholder="0">
                                    <span class="bg-slate-50 border border-slate-200 border-l-0 text-slate-500 text-[10px] font-bold px-2.5 rounded-r-xl flex items-center h-full min-h-[46px]">
                                        MENIT
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <input type="number" name="cycle_time_seconds" x-model="cycleTimeSeconds"
                                        @input="calculateTarget" required min="0" max="59" value="0" inputmode="numeric"
                                        class="w-full bg-white border border-slate-200 rounded-l-xl border-r-0 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 min-h-[46px]"
                                        placeholder="0">
                                    <span class="bg-slate-50 border border-slate-200 border-l-0 text-slate-500 text-[10px] font-bold px-2.5 rounded-r-xl flex items-center h-full min-h-[46px]">
                                        DETIK
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Target (Auto) --}}
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Target (Auto)</label>
                            <input type="number" readonly x-model="targetQty"
                                class="w-full bg-slate-100 border border-slate-200 rounded-xl text-center font-black text-slate-700 text-lg p-3 cursor-not-allowed min-h-[46px]">
                            <p class="text-[10px] text-center text-slate-400">Dihitung dari Cycle Time</p>
                        </div>

                        {{-- Hasil (OK) - User Input Only --}}
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Hasil Bubut (OK)</label>
                            <input type="number" name="actual_qty" x-model="actualQty" @input="calculateAchievement"
                                required min="0" inputmode="numeric"
                                class="w-full bg-blue-50/50 border-2 border-blue-400 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-600 text-center font-black text-blue-700 text-xl p-3 min-h-[46px]"
                                placeholder="0">
                            <p class="text-[10px] text-center text-blue-500 font-medium">Input hasil pengerjaan bubut</p>
                        </div>
                    </div>

                    {{-- Keterangan & Capaian --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 pt-1">
                        {{-- Keterangan Dropdown --}}
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Keterangan (Opsional)</label>
                            <select name="remark"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 min-h-[46px]">
                                <option value="" selected>Normal (Selesai)</option>
                                <option value="Setengah jadi">Setengah jadi</option>
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
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Capaian KPI</label>
                            <div class="w-full rounded-xl text-center font-black text-lg p-3 border flex items-center justify-center min-h-[46px]" :class="{
                                    'bg-emerald-50 text-emerald-600 border-emerald-200': achievement >= 100,
                                    'bg-amber-50 text-amber-600 border-amber-200': achievement >= 80 && achievement < 100,
                                    'bg-red-50 text-red-600 border-red-200': achievement < 80
                                }">
                                <span x-text="achievement + '%'">0%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Catatan --}}
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Catatan (Opsional)</label>
                        <input type="text" name="note"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm p-3 font-medium text-slate-700 min-h-[46px]"
                            placeholder="Catatan tambahan bila ada...">
                    </div>

                </div>
            </div>

            {{-- Desktop Submit Button --}}
            <div class="hidden md:block">
                @if(auth()->user()->isReadOnly())
                    <div class="bg-amber-50 border border-amber-200 text-amber-700 p-4 rounded-2xl flex items-center gap-3">
                        <span class="material-icons-round text-amber-500">lock</span>
                        <div class="text-sm font-medium">
                            Anda berada dalam mode **Read-Only** ({{ auth()->user()->role }}).
                        </div>
                    </div>
                @else
                    <button type="button" @click="confirmSubmit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-blue-500/25 flex items-center justify-center gap-2 active:scale-[0.99] transition-all cursor-pointer">
                        <span class="material-icons-round">save_alt</span>
                        <span>Simpan Data Produksi</span>
                    </button>
                @endif
            </div>

            {{-- Mobile Sticky Bottom Bar for Thumb Submissions --}}
            <div class="md:hidden fixed bottom-0 left-0 right-0 p-3 bg-white/95 backdrop-blur-md border-t border-slate-200 shadow-2xl z-30">
                @if(auth()->user()->isReadOnly())
                    <div class="text-center text-xs text-amber-700 font-semibold py-2">
                        Mode Read-Only Aktif
                    </div>
                @else
                    <button type="button" @click="confirmSubmit"
                        class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 transition-transform active:scale-[0.98]">
                        <span class="material-icons-round text-xl">save_alt</span>
                        <span class="text-base">Simpan Data Produksi</span>
                    </button>
                @endif
            </div>

            {{-- Error Summary --}}
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl">
                    <ul class="list-disc pl-5 text-xs sm:text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

        </form>

        {{-- ======================================================== --}}
        {{-- CAMERA BARCODE SCANNER MODAL (Android Chrome Optimized)  --}}
        {{-- ======================================================== --}}
        <div x-show="scannerOpen" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-3 sm:p-4"
            style="display: none;">

            <div class="w-full max-w-md bg-slate-900 text-white rounded-3xl overflow-hidden shadow-2xl border border-slate-800 flex flex-col max-h-[90vh]">
                
                {{-- Scanner Modal Header --}}
                <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-icons-round text-blue-400">qr_code_scanner</span>
                        <h3 class="font-bold text-sm sm:text-base">Pindai Barcode Kitir</h3>
                    </div>
                    <button type="button" @click="stopScanner()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                        <span class="material-icons-round text-2xl">close</span>
                    </button>
                </div>

                {{-- Camera Viewport --}}
                <div class="relative bg-black flex-1 min-h-[280px] sm:min-h-[320px] flex items-center justify-center overflow-hidden">
                    <video id="scanner-video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                    
                    {{-- Scanning Rect Viewfinder --}}
                    <div class="absolute inset-0 flex items-center justify-center p-6 pointer-events-none">
                        <div class="w-64 h-44 sm:w-72 sm:h-48 border-2 border-blue-400 rounded-2xl relative shadow-lg shadow-blue-500/20">
                            {{-- Laser line animation --}}
                            <div class="absolute inset-x-0 top-0 h-0.5 bg-red-500 shadow-sm shadow-red-500 animate-pulse"></div>
                            <div class="absolute -top-1 -left-1 w-4 h-4 border-t-4 border-l-4 border-blue-500 rounded-tl"></div>
                            <div class="absolute -top-1 -right-1 w-4 h-4 border-t-4 border-r-4 border-blue-500 rounded-tr"></div>
                            <div class="absolute -bottom-1 -left-1 w-4 h-4 border-b-4 border-l-4 border-blue-500 rounded-bl"></div>
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 border-b-4 border-r-4 border-blue-500 rounded-br"></div>
                        </div>
                    </div>

                    {{-- Scanning Status Overlay --}}
                    <div class="absolute bottom-3 inset-x-3 bg-black/60 backdrop-blur-sm py-2 px-3 rounded-xl text-center text-xs text-blue-200">
                        <span x-text="scannerStatusText">Arahkan kamera ke Barcode Code128 pada Kitir (KTR)...</span>
                    </div>

                    {{-- Camera Loading / Error State --}}
                    <div x-show="cameraError" class="absolute inset-0 bg-slate-900/95 p-6 flex flex-col items-center justify-center text-center space-y-3" style="display: none;">
                        <span class="material-icons-round text-amber-400 text-4xl">videocam_off</span>
                        <p class="text-sm font-semibold text-white" x-text="cameraErrorMessage">Kamera tidak dapat diakses.</p>
                        <p class="text-xs text-slate-400">Pastikan izin kamera diizinkan pada browser Android Chrome atau gunakan pencarian manual.</p>
                        <button type="button" @click="stopScanner(); showManualSearch = true;"
                            class="mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold">
                            Gunakan Cari Manual
                        </button>
                    </div>
                </div>

                {{-- Scanner Modal Footer --}}
                <div class="p-3.5 bg-slate-950 border-t border-slate-800 flex items-center justify-between gap-2">
                    <button type="button" @click="stopScanner(); showManualSearch = true;"
                        class="text-xs text-slate-300 hover:text-white px-3 py-2 rounded-lg font-medium">
                        Cari Manual Saja
                    </button>
                    <button type="button" @click="stopScanner()"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold">
                        Tutup
                    </button>
                </div>

            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- KTR RESOLUTION CONFIRMATION CARD MODAL (Part 12)         --}}
        {{-- ======================================================== --}}
        <div x-show="showConfirmationModal" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4"
            style="display: none;">

            <div class="w-full max-w-md bg-white rounded-3xl overflow-hidden shadow-2xl border border-slate-100 p-5 sm:p-6 space-y-4">
                
                {{-- Header --}}
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <span class="material-icons-round text-2xl">verified</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-slate-800">Kitir Berhasil Ditemukan</h3>
                        <p class="text-xs text-slate-400">Verifikasi data sebelum diterapkan ke form</p>
                    </div>
                </div>

                {{-- Data Card Summary --}}
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 text-xs space-y-2.5 font-medium">
                    <div class="flex justify-between border-b border-slate-200/60 pb-2">
                        <span class="text-slate-500">Nomor Kitir (KTR):</span>
                        <span class="font-bold font-mono text-blue-700 text-sm" x-text="resolvedData.ktr"></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 pb-2">
                        <span class="text-slate-500">Heat Number:</span>
                        <span class="font-bold font-mono text-slate-800" x-text="resolvedData.heat_number"></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 pb-2">
                        <span class="text-slate-500">Kode Produksi:</span>
                        <span class="font-bold text-slate-800" x-text="resolvedData.kode_produksi || '-'"></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 pb-2">
                        <span class="text-slate-500">Nama Barang:</span>
                        <span class="font-bold text-slate-800 text-right max-w-[200px]" x-text="resolvedData.item_name"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 border-b border-slate-200/60 pb-2">
                        <div>
                            <span class="text-slate-500">Size:</span>
                            <span class="font-bold text-slate-800 ml-1" x-text="resolvedData.size"></span>
                        </div>
                        <div>
                            <span class="text-slate-500">Line:</span>
                            <span class="font-bold text-slate-800 ml-1" x-text="resolvedData.line"></span>
                        </div>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 pb-2">
                        <span class="text-slate-500">Customer:</span>
                        <span class="font-bold text-slate-800" x-text="resolvedData.customer"></span>
                    </div>
                    <div class="flex justify-between pt-1">
                        <span class="text-slate-500">Qty Cor (Hasil Cor):</span>
                        <span class="font-bold text-emerald-700 text-sm" x-text="resolvedData.qty_cor + ' PCS'"></span>
                    </div>
                </div>

                {{-- Notice: Hasil Bubut is not overwritten by Qty Cor --}}
                <div class="text-[11px] text-slate-400 bg-blue-50/50 p-2.5 rounded-xl border border-blue-100 flex items-start gap-2">
                    <span class="material-icons-round text-blue-500 text-base shrink-0">info</span>
                    <span>Qty Cor di atas adalah output proses Cor. Field <strong>Hasil (OK)</strong> pada form tetap diisi manual sesuai hasil bubut.</span>
                </div>

                {{-- Action Buttons --}}
                <div class="grid grid-cols-2 gap-3 pt-1">
                    <button type="button" @click="retryScan()"
                        class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 px-4 rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5">
                        <span class="material-icons-round text-base">refresh</span>
                        <span>Scan Ulang</span>
                    </button>
                    <button type="button" @click="applyKitirData()"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl text-xs shadow-md shadow-blue-500/20 transition-colors flex items-center justify-center gap-1.5">
                        <span class="material-icons-round text-base">check</span>
                        <span>Gunakan Kitir</span>
                    </button>
                </div>

            </div>
        </div>

    </div>

    {{-- Alpine.js Production Form & Scanner Logic --}}
    <script>
        function productionForm() {
            return {
                // Form State
                timeStart: '',
                timeEnd: '',

                // Item Information
                selectedItemCode: '',
                selectedItemName: '',
                selectedSize: '',
                selectedLine: '',
                selectedCustomer: '',

                // Cycle Time Manual
                cycleTimeMinutes: '',
                cycleTimeSeconds: '',

                // Heat Number & Manual Search
                heatNumberSearch: '',
                selectedHeatNumber: '',
                heatNumberList: [],
                showHeatNumberSuggestions: false,
                showManualSearch: false,

                // Stats
                avgCycleTimeText: '',

                // Operator Search
                operatorSearch: '',
                selectedOperatorCode: '',
                selectedOperatorName: '',
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

                // Scanner State
                scannerOpen: false,
                scannerStream: null,
                scannerInterval: null,
                isResolvingKtr: false,
                scannerStatusText: 'Arahkan kamera ke Barcode Code128 pada Kitir (KTR)...',
                cameraError: false,
                cameraErrorMessage: '',
                resolvedKtr: '',

                // Confirmation Modal State (Part 12)
                showConfirmationModal: false,
                resolvedData: {
                    ktr: '',
                    heat_number: '',
                    kode_produksi: '',
                    item_code: '',
                    item_name: '',
                    size: '',
                    customer: '',
                    line: '',
                    qty_cor: 0
                },

                // Initialization
                init() {
                    // Check URL parameter "?scan=1" for mobile direct scan entry point
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('scan') === '1') {
                        setTimeout(() => {
                            this.startScanner();
                        }, 300);
                    }
                },

                // ============================================
                // CAMERA SCANNER IMPLEMENTATION (Android Chrome)
                // ============================================
                async startScanner() {
                    this.cameraError = false;
                    this.cameraErrorMessage = '';
                    this.scannerOpen = true;
                    this.scannerStatusText = 'Menghubungkan ke kamera belakang...';

                    // Ensure modal DOM is ready before attaching video stream
                    this.$nextTick(async () => {
                        const video = document.getElementById('scanner-video');
                        if (!video) return;

                        try {
                            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                                throw new Error('Browser tidak mendukung akses kamera web.');
                            }

                            // Request rear camera on mobile device
                            const constraints = {
                                video: {
                                    facingMode: { ideal: "environment" },
                                    width: { ideal: 1280 },
                                    height: { ideal: 720 }
                                },
                                audio: false
                            };

                            this.scannerStream = await navigator.mediaDevices.getUserMedia(constraints);
                            video.srcObject = this.scannerStream;
                            await video.play();

                            this.scannerStatusText = 'Memindai barcode Kitir (Code128)...';
                            this.startBarcodeDetection(video);

                        } catch (err) {
                            console.error('[ScannerError]', err);
                            this.cameraError = true;
                            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                                this.cameraErrorMessage = 'Izin kamera ditolak. Berikan izin kamera di Android Chrome untuk memindai.';
                            } else {
                                this.cameraErrorMessage = err.message || 'Kamera tidak dapat dimulai.';
                            }
                        }
                    });
                },

                startBarcodeDetection(video) {
                    if (window.BarcodeDetector) {
                        const barcodeDetector = new BarcodeDetector({
                            formats: ['code_128', 'code_39', 'qr_code', 'ean_13']
                        });

                        const detectFrame = async () => {
                            if (!this.scannerOpen || this.isResolvingKtr) return;

                            try {
                                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                                    const barcodes = await barcodeDetector.detect(video);
                                    if (barcodes.length > 0) {
                                        const rawCode = barcodes[0].rawValue;
                                        if (rawCode && rawCode.trim()) {
                                            this.onBarcodeDetected(rawCode.trim());
                                            return;
                                        }
                                    }
                                }
                            } catch (e) {
                                // continue scanning
                            }

                            if (this.scannerOpen && !this.isResolvingKtr) {
                                requestAnimationFrame(detectFrame);
                            }
                        };

                        requestAnimationFrame(detectFrame);
                    } else {
                        // Fallback detection using video canvas snapshot if native BarcodeDetector not available
                        this.scannerStatusText = 'Memindai barcode (mode kompatibel)...';
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        this.scannerInterval = setInterval(async () => {
                            if (!this.scannerOpen || this.isResolvingKtr) return;
                            if (video.videoWidth > 0 && video.videoHeight > 0) {
                                canvas.width = video.videoWidth;
                                canvas.height = video.videoHeight;
                                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                            }
                        }, 500);
                    }
                },

                stopScanner() {
                    if (this.scannerInterval) {
                        clearInterval(this.scannerInterval);
                        this.scannerInterval = null;
                    }

                    if (this.scannerStream) {
                        this.scannerStream.getTracks().forEach(track => track.stop());
                        this.scannerStream = null;
                    }

                    const video = document.getElementById('scanner-video');
                    if (video) {
                        video.srcObject = null;
                    }

                    this.scannerOpen = false;
                },

                // Handle string parsed from barcode
                async onBarcodeDetected(code) {
                    if (this.isResolvingKtr) return;
                    this.isResolvingKtr = true;
                    this.scannerStatusText = `Mencari data ${code}...`;

                    try {
                        const res = await fetch(`{{ route('api.resolve.ktr') }}?code=${encodeURIComponent(code)}`);
                        const data = await res.json();

                        if (res.ok && data.success) {
                            // Stop camera stream immediately
                            this.stopScanner();

                            // Present confirmation card (Part 12)
                            this.resolvedData = data.data;
                            this.showConfirmationModal = true;
                        } else {
                            // Show error feedback but keep scanner ready
                            const errMsg = data.message || 'Kitir tidak ditemukan.';
                            Swal.fire({
                                icon: 'warning',
                                title: 'Kitir Tidak Valid',
                                text: errMsg,
                                confirmButtonColor: '#2563eb'
                            });
                            this.scannerStatusText = errMsg;
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Koneksi Gagal',
                            text: 'Gagal menghubungi server untuk verifikasi Kitir.',
                            confirmButtonColor: '#2563eb'
                        });
                    } finally {
                        this.isResolvingKtr = false;
                    }
                },

                // Confirmation modal action: Apply data to existing form (Part 12)
                applyKitirData() {
                    const data = this.resolvedData;
                    if (!data || !data.item_code) return;

                    this.selectedHeatNumber = data.heat_number || '';
                    this.selectedItemCode = data.item_code || '';
                    this.selectedItemName = data.item_name || '';
                    this.selectedSize = data.size || '-';
                    this.selectedCustomer = data.customer || '-';
                    this.selectedLine = data.line || '-';
                    this.resolvedKtr = data.ktr || '';

                    // Note: actualQty is NOT prefilled with qty_cor (as per Rule 6 & 17)
                    this.fetchItemStats(data.item_code);

                    this.showConfirmationModal = false;

                    Swal.fire({
                        icon: 'success',
                        title: 'Kitir Diterapkan',
                        text: `${data.item_name} (${data.heat_number}) siap diproses.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                },

                retryScan() {
                    this.showConfirmationModal = false;
                    this.startScanner();
                },

                // ============================================
                // EXISTING AUTOCOMPLETE & CALCULATION LOGIC
                // ============================================
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
                    this.resolvedKtr = '';
                    this.showHeatNumberSuggestions = false;

                    this.fetchItemStats(hn.item_code);
                },

                async fetchItemStats(itemCode) {
                    if (!itemCode) return;
                    this.avgCycleTimeText = '...';
                    try {
                        const res = await fetch(`{{ url('/api/item-stats') }}/${itemCode}`);
                        const data = await res.json();
                        this.avgCycleTimeText = data.formatted;
                    } catch (e) {
                        this.avgCycleTimeText = '';
                    }
                },

                async searchOperators() {
                    if (this.operatorSearch.length < 1) return;
                    const res = await fetch(`{{ route('api.search.operators') }}?q=${encodeURIComponent(this.operatorSearch)}`);
                    this.operatorList = await res.json();
                    this.showOperatorSuggestions = true;
                },

                selectOperator(op) {
                    this.selectedOperatorCode = op.code;
                    this.selectedOperatorName = op.name;
                    this.operatorSearch = op.name;
                    this.showOperatorSuggestions = false;
                },

                async searchMachines() {
                    if (this.machineSearch.length < 1) return;
                    const res = await fetch(`{{ route('api.search.machines') }}?q=${encodeURIComponent(this.machineSearch)}`);
                    this.machineList = await res.json();
                    this.showMachineSuggestions = true;
                },

                selectMachine(machine) {
                    this.selectedMachineCode = machine.code;
                    this.machineSearch = machine.name;
                    this.showMachineSuggestions = false;
                },

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

                    let diffMinutes = end - start;
                    if (diffMinutes < 0) diffMinutes += 1440; // cross-midnight

                    let diffSeconds = diffMinutes * 60;
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

                confirmSubmit() {
                    this.cycleTimeMinutes = this.cycleTimeMinutes || '0';
                    this.cycleTimeSeconds = this.cycleTimeSeconds || '0';

                    if (!this.selectedOperatorCode || !this.selectedMachineCode || !this.selectedItemCode || !this.timeStart || !this.timeEnd || !this.actualQty) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Data Belum Lengkap',
                            text: 'Mohon lengkapi operator, mesin, item/kitir, jam kerja, dan hasil output.',
                            confirmButtonColor: '#2563eb'
                        });
                        return;
                    }

                    const totalSec = (parseInt(this.cycleTimeMinutes) * 60) + parseInt(this.cycleTimeSeconds);
                    if (totalSec <= 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cycle Time Invalid',
                            text: 'Total Cycle Time tidak boleh 0 detik.',
                            confirmButtonColor: '#2563eb'
                        });
                        return;
                    }

                    const summaryHtml = `
                        <div class="text-left text-xs sm:text-sm text-slate-600 space-y-2 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                            <div class="flex justify-between border-b border-slate-200 pb-1.5">
                                <span class="font-medium">Operator:</span>
                                <span class="font-bold text-slate-800">${this.selectedOperatorName}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-200 pb-1.5">
                                <span class="font-medium">Mesin:</span>
                                <span class="font-bold text-slate-800">${this.machineSearch}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-200 pb-1.5">
                                <span class="font-medium">Barang/Heat:</span>
                                <span class="font-bold text-slate-800">${this.selectedItemName} (${this.selectedHeatNumber || '-'})</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-200 pb-1.5">
                                <span class="font-medium">Cycle Time:</span>
                                <span class="font-bold text-slate-800">${this.cycleTimeMinutes}m ${this.cycleTimeSeconds}s</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-200 pb-1.5">
                                <span class="font-medium">Waktu:</span>
                                <span class="font-bold text-slate-800">${this.timeStart} - ${this.timeEnd}</span>
                            </div>
                            <div class="flex justify-between pt-1">
                                <span class="font-medium">Hasil Bubut (OK):</span>
                                <span class="font-bold text-blue-600 text-base sm:text-lg">${this.actualQty} PCS</span>
                            </div>
                        </div>
                    `;

                    Swal.fire({
                        title: 'Verifikasi Data Produksi',
                        html: summaryHtml,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Periksa Lagi',
                        confirmButtonColor: '#2563eb',
                        cancelButtonColor: '#dc2626',
                        reverseButtons: true,
                        focusConfirm: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('production-form').submit();
                        }
                    });
                }
            };
        }
    </script>
@endsection