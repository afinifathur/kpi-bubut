@extends('layouts.app')

@section('title', 'Planned Capacity Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/handsontable/handsontable.full.min.css') }}" />
    <style>
        .handsontable td {
            font-size: 11px !important;
            vertical-align: middle !important;
        }
        .handsontable th {
            font-size: 11px !important;
            font-weight: bold !important;
        }
        /* Custom highlight for Exception rows */
        .bg-green-50\/50 {
            background-color: rgba(236, 253, 245, 0.6) !important;
        }
        .text-green-900 {
            color: #065f46 !important;
        }
    </style>
@endpush

@section('content')

    <div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Kapasitas Kerja Terencana (Planned Capacity)</h1>
            <p class="text-xs text-gray-500">
                Pengecualian Kapasitas Shift Harian (Exception-Only Storage)
            </p>
        </div>
    </div>

    {{-- Panduan Pengisian Kapasitas (Temuan #4) --}}
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg mb-4 text-xs">
        <div class="font-bold text-blue-800 mb-1 flex items-center">
            <span class="material-icons-round text-sm mr-1">info</span>
            Panduan Pengisian Kapasitas Shift:
        </div>
        <ul class="list-disc pl-4 space-y-1 text-gray-700">
            <li><strong>Kosong / Angka 7.00:</strong> Kondisi kerja normal (7 Jam per shift). Sistem <strong>tidak akan menyimpan</strong> nilai ini di database (Exception-Only).</li>
            <li><strong>Angka 0.00:</strong> Mesin libur, shutdown, atau tidak beroperasi pada shift tersebut.</li>
            <li><strong>Angka &gt; 7.00 (misal: 8.00 s/d 12.00):</strong> Lembur operasional mesin (Overtime). Max: 24.00 jam.</li>
            <li><strong>Angka &lt; 7.00 (misal: 4.00):</strong> Pemangkasan kapasitas shift kerja.</li>
            <li>Baris dengan status <strong class="text-green-800">Exception</strong> (ditandai dengan warna hijau) menunjukkan kapasitas menyimpang dari normal dan akan disimpan di database.</li>
        </ul>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r shadow-sm flex items-center text-xs">
            <span class="material-icons-round text-sm mr-2">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r shadow-sm flex items-center text-xs">
            <span class="material-icons-round text-sm mr-2">error</span>
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
        {{-- Toolbar / Filters --}}
        <div class="p-3 border-b border-gray-100 bg-gray-50 flex flex-col md:flex-row gap-3 items-end justify-between">
            <form method="GET" id="filterForm" class="flex flex-col md:flex-row md:items-end gap-2 w-full md:w-auto">
                {{-- Bulan --}}
                <div>
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">Bulan</label>
                    <input type="month" name="month" id="month" value="{{ $month }}"
                           class="block w-full shadow-sm text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1 px-2">
                </div>

                {{-- Mesin --}}
                <div>
                    <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">Mesin</label>
                    <select name="machine_code" id="machine_code" class="block w-48 shadow-sm text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1 px-2">
                        <option value="GLOBAL" {{ strcasecmp($machineCode, 'GLOBAL') === 0 ? 'selected' : '' }}>🌎 Semua Mesin (GLOBAL)</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->code }}" {{ strcasecmp($machineCode, $machine->code) === 0 ? 'selected' : '' }}>
                                🖥️ {{ $machine->code }} - {{ $machine->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold uppercase tracking-wide rounded transition-colors shadow-sm h-fit">
                        Filter / Tampilkan
                    </button>
                </div>
            </form>

            <div class="flex items-center gap-2 mt-2 md:mt-0">
                @if(!auth()->user()->isReadOnly())
                    <button id="btnReset" class="px-3 py-1 bg-gray-600 hover:bg-gray-700 text-white text-[10px] font-bold uppercase tracking-wide rounded transition-colors shadow-sm h-fit">
                        Reset Ke Default (7-7-7)
                    </button>
                    <button id="btnSave" class="px-4 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold uppercase tracking-wide rounded transition-colors shadow-sm h-fit">
                        Simpan Perubahan
                    </button>
                @endif
            </div>
        </div>

        {{-- Summary Cards (PHASE 1.5.1) --}}
        <div class="p-4 pb-0 bg-gray-50/50">
            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                {{-- Card 1: Total Kapasitas --}}
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 shadow-sm flex flex-col justify-between min-h-[76px]">
                    <div>
                        <span class="text-[9px] font-bold text-blue-500 uppercase tracking-wider block mb-0.5">Total Kapasitas</span>
                        <span class="text-lg font-black text-blue-900 leading-none">
                            {{ number_format($summary['total_capacity'], 2) }} Jam
                        </span>
                    </div>
                    <span class="mt-1.5 text-[9px] text-blue-600/80 font-medium">Bulan Berjalan</span>
                </div>

                {{-- Card 2: Avg Kapasitas --}}
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 shadow-sm flex flex-col justify-between min-h-[76px]">
                    <div>
                        <span class="text-[9px] font-bold text-indigo-500 uppercase tracking-wider block mb-0.5">Avg Kapasitas</span>
                        <span class="text-lg font-black text-indigo-900 leading-none">
                            {{ number_format($summary['avg_capacity'], 2) }} Jam/Hari
                        </span>
                    </div>
                    <span class="mt-1.5 text-[9px] text-indigo-600/80 font-medium">Rata-rata Kapasitas</span>
                </div>

                {{-- Card 3: Hari Produksi --}}
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3 shadow-sm flex flex-col justify-between min-h-[76px]">
                    <div>
                        <span class="text-[9px] font-bold text-emerald-500 uppercase tracking-wider block mb-0.5">Hari Produksi</span>
                        <span class="text-lg font-black text-emerald-900 leading-none">
                            {{ $summary['production_days'] }} Hari
                        </span>
                    </div>
                    <span class="mt-1.5 text-[9px] text-emerald-600/80 font-medium">Kapasitas &gt; 0 Jam</span>
                </div>

                {{-- Card 4: Hari Libur --}}
                <div class="bg-rose-50 border border-rose-100 rounded-xl p-3 shadow-sm flex flex-col justify-between min-h-[76px]">
                    <div>
                        <span class="text-[9px] font-bold text-rose-500 uppercase tracking-wider block mb-0.5">Hari Libur</span>
                        <span class="text-lg font-black text-rose-900 leading-none">
                            {{ $summary['holiday_days'] }} Hari
                        </span>
                    </div>
                    <span class="mt-1.5 text-[9px] text-rose-600/80 font-medium">Kapasitas = 0 Jam</span>
                </div>

                {{-- Card 5: Jam Overtime --}}
                <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 shadow-sm flex flex-col justify-between min-h-[76px]">
                    <div>
                        <span class="text-[9px] font-bold text-amber-500 uppercase tracking-wider block mb-0.5">Jam Overtime</span>
                        <span class="text-lg font-black text-amber-900 leading-none">
                            {{ number_format($summary['total_overtime'], 2) }} Jam
                        </span>
                    </div>
                    <span class="mt-1.5 text-[9px] text-amber-600/80 font-medium">Lembur Per Shift</span>
                </div>

                {{-- Card 6: Scope --}}
                <div class="bg-purple-50 border border-purple-100 rounded-xl p-3 shadow-sm flex flex-col justify-between min-h-[76px]">
                    <div>
                        <span class="text-[9px] font-bold text-purple-500 uppercase tracking-wider block mb-0.5">Scope</span>
                        <span class="text-lg font-black text-purple-900 leading-none block truncate" title="{{ $summary['scope_line1'] }}">
                            {{ $summary['scope_line1'] }}
                        </span>
                    </div>
                    <span class="mt-1.5 text-[9px] text-purple-600/80 font-medium block truncate" title="{{ $summary['scope_line2'] }}">
                        {{ $summary['scope_line2'] }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Handsontable Container --}}
        <div class="p-4 bg-gray-50/50">
            <div id="capacityGrid" class="w-full overflow-hidden rounded-lg border border-gray-200 shadow-inner"></div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/handsontable/handsontable.full.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('capacityGrid');
            const gridData = @json($gridData);

            // Helper untuk mengembalikan nilai efektif kapasitas (Bug #2 & #3)
            function getEffectiveVal(value) {
                if (value === null || value === undefined || String(value).trim() === '') {
                    return 7.0;
                }
                const val = parseFloat(value);
                return isNaN(val) ? 7.0 : val;
            }

            // Validator custom untuk memastikan jam kerja antara 0 s/d 24 (Temuan #4)
            function hoursValidator(value, callback) {
                if (value === null || value === undefined || String(value).trim() === '') {
                    callback(true);
                    return;
                }
                const val = parseFloat(value);
                if (!isNaN(val) && val >= 0 && val <= 24) {
                    callback(true);
                } else {
                    callback(false);
                }
            }

            const hot = new Handsontable(container, {
                data: gridData,
                rowHeaders: true,
                colHeaders: ['Tanggal', 'Shift 1 (Jam)', 'Shift 2 (Jam)', 'Shift 3 (Jam)', 'Total Jam', 'Notes (Alasan Exception)', 'Status'],
                columns: [
                    { data: 'date', readOnly: true, className: 'htCenter' },
                    { data: 'shift_1', type: 'numeric', numericFormat: { pattern: '0.00' }, validator: hoursValidator },
                    { data: 'shift_2', type: 'numeric', numericFormat: { pattern: '0.00' }, validator: hoursValidator },
                    { data: 'shift_3', type: 'numeric', numericFormat: { pattern: '0.00' }, validator: hoursValidator },
                    { data: 'total', type: 'numeric', numericFormat: { pattern: '0.00' }, readOnly: true },
                    { data: 'notes', type: 'text' },
                    { data: 'status', readOnly: true, className: 'htCenter' }
                ],
                stretchH: 'all',
                autoWrapRow: true,
                height: 'auto',
                width: '100%',
                licenseKey: 'non-commercial-and-evaluation',
                afterChange: function(changes, source) {
                    // Mencegah rekursi infinite loop saat mengubah data total/status
                    if (source === 'recalculate') {
                        return;
                    }
                    if (!changes) return;
                    
                    const updates = [];
                    changes.forEach(([row, prop, oldVal, newVal]) => {
                        if (['shift_1', 'shift_2', 'shift_3', 'notes'].includes(prop)) {
                            const s1 = getEffectiveVal(this.getDataAtRowProp(row, 'shift_1'));
                            const s2 = getEffectiveVal(this.getDataAtRowProp(row, 'shift_2'));
                            const s3 = getEffectiveVal(this.getDataAtRowProp(row, 'shift_3'));
                            const notes = (this.getDataAtRowProp(row, 'notes') || '').toString().trim();
                            
                            const total = s1 + s2 + s3;
                            const status = (s1 === 7 && s2 === 7 && s3 === 7 && notes === '') ? 'Default' : 'Exception';
                            
                            updates.push([row, 'total', total]);
                            updates.push([row, 'status', status]);
                        }
                    });
                    
                    if (updates.length > 0) {
                        this.setDataAtRowProp(updates, 'recalculate');
                    }
                },
                cells: function(row, col, prop) {
                    const cellProperties = {};
                    const status = this.instance.getDataAtRowProp(row, 'status');
                    let classes = '';
                    
                    if (status === 'Exception') {
                        classes += 'bg-green-50/50 text-green-900 font-semibold ';
                    }
                    
                    // Kolom text atau numeric alignment
                    if ([1, 2, 3, 4].includes(col)) {
                        classes += 'htRight ';
                    } else if (col === 0 || col === 6) {
                        classes += 'htCenter ';
                    }
                    
                    cellProperties.className = classes.trim();
                    return cellProperties;
                }
            });

            // Aksi Reset ke Default
            const btnReset = document.getElementById('btnReset');
            if (btnReset) {
                btnReset.addEventListener('click', function() {
                    const alertConfirm = window.Swal 
                        ? Swal.fire({
                            title: 'Reset Kapasitas?',
                            text: "Semua nilai akan dikembalikan ke 7.00 jam dan notes dikosongkan.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Ya, Reset!'
                          })
                        : Promise.resolve({ isConfirmed: confirm("Reset seluruh data kapasitas bulan ini ke 7-7-7?") });

                    alertConfirm.then((result) => {
                        if (result.value || result.isConfirmed) {
                            const count = hot.countRows();
                            const resetUpdates = [];
                            for (let row = 0; row < count; row++) {
                                resetUpdates.push([row, 'shift_1', 7.0]);
                                resetUpdates.push([row, 'shift_2', 7.0]);
                                resetUpdates.push([row, 'shift_3', 7.0]);
                                resetUpdates.push([row, 'notes', '']);
                            }
                            hot.setDataAtRowProp(resetUpdates);
                            
                            if (window.Swal) {
                                Swal.fire('Direset!', 'Silakan klik Simpan untuk menerapkan perubahan ke database.', 'success');
                            }
                        }
                    });
                });
            }

            // Aksi Simpan data via AJAX
            const btnSave = document.getElementById('btnSave');
            if (btnSave) {
                btnSave.addEventListener('click', function() {
                    // Validasi validitas data grid sebelum kirim
                    const count = hot.countRows();
                    for (let r = 0; r < count; r++) {
                        const s1 = getEffectiveVal(hot.getDataAtRowProp(r, 'shift_1'));
                        const s2 = getEffectiveVal(hot.getDataAtRowProp(r, 'shift_2'));
                        const s3 = getEffectiveVal(hot.getDataAtRowProp(r, 'shift_3'));
                        if (s1 < 0 || s1 > 24 || s2 < 0 || s2 > 24 || s3 < 0 || s3 > 24) {
                            if (window.Swal) {
                                Swal.fire('Error Input!', `Jam kapasitas pada baris ke-${r+1} tidak valid (harus antara 0 s/d 24).`, 'error');
                            } else {
                                alert(`Jam kapasitas pada baris ke-${r+1} tidak valid (harus antara 0 s/d 24).`);
                            }
                            return;
                        }
                    }

                    // Tampilkan loader
                    btnSave.disabled = true;
                    btnSave.innerText = 'Menyimpan...';

                    const payload = {
                        _token: '{{ csrf_token() }}',
                        month: document.getElementById('month').value,
                        machine_code: document.getElementById('machine_code').value,
                        grid: hot.getSourceData()
                    };

                    fetch('{{ route("planned_capacity.save") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(res => {
                        btnSave.disabled = false;
                        btnSave.innerText = 'Simpan Perubahan';

                        if (res.status === 200 && res.body.success) {
                            if (window.Swal) {
                                Swal.fire('Berhasil!', res.body.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                alert(res.body.message);
                                window.location.reload();
                            }
                        } else {
                            const errorMsg = res.body.message || 'Terjadi kesalahan sistem.';
                            if (window.Swal) {
                                Swal.fire('Gagal!', errorMsg, 'error');
                            } else {
                                alert('Simpan gagal: ' + errorMsg);
                            }
                        }
                    })
                    .catch(err => {
                        btnSave.disabled = false;
                        btnSave.innerText = 'Simpan Perubahan';
                        console.error('Save failed:', err);
                        if (window.Swal) {
                            Swal.fire('Error!', 'Koneksi ke server terputus.', 'error');
                        } else {
                            alert('Koneksi terputus.');
                        }
                    });
                });
            }
        });
    </script>
@endpush

