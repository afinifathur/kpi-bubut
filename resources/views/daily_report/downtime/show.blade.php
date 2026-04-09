@extends('layouts.app')

@section('title', 'Detail Harian Downtime & Cek Mesin')

@section('content')

    @php
        $standards = [
            '1/2" - 3/4"' => [
                'kasar' => ['rpm' => 380, 'feeding' => 0.17],
                'finish' => ['rpm' => 450, 'feeding' => 0.2]
            ],
            '1"' => [
                'kasar' => ['rpm' => 350, 'feeding' => 0.17],
                'finish' => ['rpm' => 450, 'feeding' => 0.2]
            ],
            '1-1/4" - 2"' => [
                'kasar' => ['rpm' => 300, 'feeding' => 0.17],
                'finish' => ['rpm' => 380, 'feeding' => 0.2]
            ],
            '2" - 2-1/2"' => [
                'kasar' => ['rpm' => 300, 'feeding' => 0.17],
                'finish' => ['rpm' => 350, 'feeding' => 0.2]
            ],
            '3" - 4"' => [
                'kasar' => ['rpm' => 250, 'feeding' => 0.17],
                'finish' => ['rpm' => 320, 'feeding' => 0.2]
            ],
            '5" - 6"' => [
                'kasar' => ['rpm' => 220, 'feeding' => 0.17],
                'finish' => ['rpm' => 260, 'feeding' => 0.2]
            ],
            '8"' => [
                'kasar' => ['rpm' => 200, 'feeding' => 0.16],
                'finish' => ['rpm' => 240, 'feeding' => 0.2]
            ]
        ];

        function getStatusClass($input, $std)
        {
            if (!$input || !$std)
                return 'text-slate-500';
            $val = (float) $input;
            $diff = abs($val - $std) / $std;
            return $diff > 0.2 ? 'text-red-600 font-black' : 'text-emerald-600 font-black';
        }
    @endphp

    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('daily_report.downtime.index') }}"
                    class="text-slate-400 hover:text-slate-600 transition-colors">
                    <span class="material-icons-round">arrow_back</span>
                </a>
                <h1 class="text-2xl font-bold text-slate-800">Detail Laporan Harian Mesin</h1>
            </div>
            <p class="text-sm text-slate-500 ml-8">
                {{ \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('daily_report.downtime.pdf', $date) }}" target="_blank"
                class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-red-500/20 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                <span class="material-icons-round text-sm mr-2">picture_as_pdf</span>
                PDF
            </a>
            <a href="{{ route('daily_report.downtime.excel', $date) }}"
                class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-emerald-500/20 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                <span class="material-icons-round text-sm mr-2">description</span>
                Excel
            </a>
        </div>
    </div>

    @if(session('success'))
        <div
            class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl shadow-sm flex items-center animate-fade-in-down">
            <span class="material-icons-round mr-2 text-emerald-500">check_circle</span>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @php
        $groupedRows = $rows->groupBy('machine_code');
        $isReadOnly = auth()->user()->isReadOnly();
    @endphp

    <div class="space-y-4">
        @forelse ($groupedRows as $machineCode => $mRows)
            @php
                $first = $mRows->first();
                $checks = $mRows->where('entry_type', 'check');
                $stops = $mRows->where('entry_type', 'downtime');
                $hasCheck = $checks->isNotEmpty();
                $hasStop = $stops->isNotEmpty();
                
                // Use first check for status icons, or first entry if no check exists
                $baseRow = $hasCheck ? $checks->first() : $first;
                $machineName = $baseRow->machine->name ?? $machineCode;
                $statusColor = $hasStop ? 'text-red-600 bg-red-50' : 'text-emerald-600 bg-emerald-50';
                $statusIcon = $hasStop ? 'timer_off' : 'fact_check';
            @endphp
            
            <div class="bg-white rounded-2xl shadow-md border border-slate-100 overflow-hidden hover:shadow-lg hover:border-blue-200 transition-all duration-300">
                <div class="px-5 py-4">
                    <div class="flex flex-col lg:flex-row gap-8">
                        {{-- Left: Machine Info & Checklist --}}
                        <div class="lg:w-1/5 flex flex-col space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center {{ $statusColor }} shadow-inner">
                                    <span class="material-icons-round text-xl">{{ $statusIcon }}</span>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-extrabold text-slate-800 text-sm leading-tight uppercase tracking-tight">{{ $machineName }}</h3>
                                    <span class="text-[9px] font-mono text-slate-400 uppercase tracking-widest block mt-0.5">{{ $machineCode }}</span>
                                </div>
                            </div>

                            @if($hasCheck)
                                @php $row = $checks->first(); @endphp
                                <div class="space-y-4">
                                    {{-- Checklist Icons (Ultra Compact Grid) --}}
                                    <div class="grid grid-cols-3 gap-1 bg-slate-50/50 p-1.5 rounded-xl border border-slate-100">
                                        @php
                                            $checklist = [
                                                ['label' => 'CEKAM', 'val' => $row->check_cekam],
                                                ['label' => 'OZON', 'val' => $row->check_air_ozo],
                                                ['label' => 'ERETAN', 'val' => $row->check_eretan],
                                                ['label' => 'PISAU', 'val' => $row->check_pisau],
                                                ['label' => 'BERSIH', 'val' => $row->check_kebersihan],
                                                ['label' => 'OLI', 'val' => $row->check_oli],
                                            ];
                                        @endphp
                                        @foreach($checklist as $item)
                                            <div class="flex flex-col items-center justify-center p-1 rounded-lg {{ $item['val'] === 'Ya' ? 'text-emerald-600 bg-white shadow-sm' : 'text-rose-500 bg-rose-50/30' }}"
                                                title="{{ $item['label'] }}">
                                                <span class="text-[6px] font-bold leading-none mb-0.5 opacity-70">{{ $item['label'] }}</span>
                                                <span class="material-icons-round text-[12px]">
                                                    {{ $item['val'] === 'Ya' ? 'check_circle' : 'cancel' }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Size Category Badge --}}
                                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 border border-indigo-100/50 rounded-full text-[10px]">
                                        <span class="material-icons-round text-indigo-400 text-[12px]">straighten</span>
                                        <span class="font-bold text-indigo-700">Size: {{ $row->size_category }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Right: Content Lists --}}
                        <div class="flex-1 space-y-5">
                            {{-- Check Entries Section --}}
                            @if($hasCheck)
                                <div>
                                    <div class="flex items-center justify-between mb-2 px-1">
                                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                            <span class="w-2 h-0.5 rounded-full bg-emerald-400"></span>
                                            Pengecekan Setting
                                        </h4>
                                    </div>
                                    <div class="space-y-1.5">
                                        @foreach($checks as $row)
                                            @php
                                                $std = $standards[$row->size_category][$row->rpm_feeding_mode] ?? null;
                                                $rpmClass = getStatusClass($row->rpm_value, $std['rpm'] ?? null);
                                                $rpmIdClass = getStatusClass($row->rpm_id_value, $std['rpm'] ?? null);
                                                $feedClass = getStatusClass($row->feeding_value, $std['feeding'] ?? null);
                                                $feedIdClass = getStatusClass($row->feeding_id_value, $std['feeding'] ?? null);
                                            @endphp
                                            <div class="group relative flex items-center gap-4 py-2 px-4 bg-white border border-slate-100 rounded-xl hover:bg-slate-50 hover:border-slate-200 transition-all">
                                                <div class="w-14 flex-shrink-0">
                                                    <span class="text-[10px] font-extrabold uppercase {{ $row->rpm_feeding_mode === 'finish' ? 'text-blue-600' : 'text-slate-400' }}">
                                                        {{ $row->rpm_feeding_mode }}
                                                    </span>
                                                </div>
                                                
                                                <div class="grid grid-cols-4 gap-x-8 flex-1">
                                                    {{-- RPM Samping --}}
                                                    <div class="flex flex-col">
                                                        <span class="text-[7px] font-bold text-slate-400 uppercase">RPM SP</span>
                                                        <span class="text-[11px] {{ $rpmClass }}">{{ $row->rpm_value }}</span>
                                                    </div>
                                                    {{-- RPM ID --}}
                                                    <div class="flex flex-col">
                                                        <span class="text-[7px] font-bold text-slate-400 uppercase">RPM ID</span>
                                                        <span class="text-[11px] {{ $rpmIdClass }}">{{ $row->rpm_id_value ?? '-' }}</span>
                                                    </div>
                                                    {{-- FD Samping --}}
                                                    <div class="flex flex-col">
                                                        <span class="text-[7px] font-bold text-slate-400 uppercase">FD SP</span>
                                                        <span class="text-[11px] {{ $feedClass }}">{{ $row->feeding_value }}</span>
                                                    </div>
                                                    {{-- FD ID --}}
                                                    <div class="flex flex-col">
                                                        <span class="text-[7px] font-bold text-slate-400 uppercase">FD ID</span>
                                                        <span class="text-[11px] {{ $feedIdClass }}">{{ $row->feeding_id_value ?? '-' }}</span>
                                                    </div>
                                                </div>

                                                {{-- Inline Note & Actions --}}
                                                <div class="flex items-center gap-3">
                                                    @if($row->note)
                                                        <div class="group/note relative">
                                                            <span class="material-icons-round text-slate-300 text-sm cursor-help hover:text-blue-400 transition-colors">info</span>
                                                            <div class="absolute bottom-full right-0 mb-2 w-48 p-2 bg-slate-800 text-white text-[9px] rounded-lg opacity-0 pointer-events-none group-hover/note:opacity-100 transition-opacity z-10 shadow-xl">
                                                                {{ $row->note }}
                                                                <div class="absolute top-full right-2 border-4 border-transparent border-t-slate-800"></div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if(!$isLocked && !$isReadOnly)
                                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <a href="{{ route('daily_report.downtime.edit', $row->id) }}"
                                                               class="w-7 h-7 flex items-center justify-center text-slate-300 hover:text-blue-600 hover:bg-white rounded-lg transition-all"
                                                               title="Edit">
                                                                <span class="material-icons-round text-sm">edit</span>
                                                            </a>
                                                            <form action="{{ route('daily_report.downtime.destroy', $row->id) }}" method="POST" class="delete-form">
                                                                @csrf @method('DELETE')
                                                                <button type="button" class="w-7 h-7 flex items-center justify-center text-slate-300 hover:text-red-600 hover:bg-white rounded-lg transition-all btn-delete" title="Hapus">
                                                                    <span class="material-icons-round text-sm">delete_outline</span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Downtime Section --}}
                            @if($hasStop)
                                <div>
                                    <h4 class="text-[10px] font-black text-rose-400 uppercase tracking-widest flex items-center gap-2 mb-2 px-1">
                                        <span class="w-2 h-0.5 rounded-full bg-rose-400"></span>
                                        Downtime / Stop
                                    </h4>
                                    <div class="space-y-1.5">
                                        @foreach($stops as $row)
                                            <div class="group relative flex items-center gap-4 py-2 px-4 bg-rose-50/30 border border-rose-100/50 rounded-xl hover:bg-rose-50/60 transition-all">
                                                <div class="flex-1 flex items-center gap-4">
                                                    <div class="flex items-center gap-1.5 text-rose-600 flex-shrink-0">
                                                        <span class="material-icons-round text-[14px]">report_problem</span>
                                                        <span class="font-black text-[11px] uppercase">{{ $row->reason }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-3 text-[10px] text-slate-500 border-l border-rose-100 pl-4">
                                                        <span class="flex items-center gap-1">
                                                            <span class="material-icons-round text-[12px] opacity-40">schedule</span>
                                                            {{ \Carbon\Carbon::parse($row->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($row->end_time)->format('H:i') }}
                                                        </span>
                                                        <span class="font-black text-rose-500 px-1.5 py-0.5 bg-rose-100/50 rounded text-[9px] uppercase">{{ $row->duration_minutes }} min</span>
                                                    </div>
                                                    @if($row->note)
                                                        <span class="text-[10px] text-slate-400 italic truncate max-w-[200px]">"{{ $row->note }}"</span>
                                                    @endif
                                                </div>

                                                @if(!$isLocked && !$isReadOnly)
                                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <a href="{{ route('daily_report.downtime.edit', $row->id) }}"
                                                           class="w-7 h-7 flex items-center justify-center text-slate-300 hover:text-blue-600 hover:bg-white rounded-lg transition-all"
                                                           title="Edit">
                                                            <span class="material-icons-round text-sm">edit</span>
                                                        </a>
                                                        <form action="{{ route('daily_report.downtime.destroy', $row->id) }}" method="POST" class="delete-form">
                                                            @csrf @method('DELETE')
                                                            <button type="button" class="w-7 h-7 flex items-center justify-center text-slate-300 hover:text-red-600 hover:bg-white rounded-lg transition-all btn-delete" title="Hapus">
                                                                <span class="material-icons-round text-sm">delete_outline</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center">
                <span class="material-icons-round text-slate-300 text-5xl mb-4">folder_open</span>
                <p class="text-slate-500 font-medium">Data tidak ditemukan untuk tanggal ini</p>
            </div>
        @endforelse
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function () {
                    const form = this.closest('.delete-form');
                    Swal.fire({
                        title: 'Hapus Laporan?',
                        text: "Data yang dihapus tidak dapat dikembalikan!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#94a3b8',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal',
                        customClass: {
                            popup: 'rounded-2xl',
                            confirmButton: 'rounded-xl font-bold py-3 px-6',
                            cancelButton: 'rounded-xl font-bold py-3 px-6'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    })
                });
            });
        </script>
    @endpush

@endsection