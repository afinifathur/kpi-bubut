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

    <div class="space-y-2">
        @forelse ($rows as $row)
            <div
                class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden hover:border-blue-200 transition-all duration-200">
                <div class="px-4 py-3">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        {{-- Left: Machine Info --}}
                        <div class="flex items-center gap-3 lg:w-1/4">
                            <div
                                class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center {{ $row->entry_type === 'check' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">
                                <span class="material-icons-round text-lg">
                                    {{ $row->entry_type === 'check' ? 'fact_check' : 'timer_off' }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-slate-800 text-sm truncate uppercase">
                                    {{ $row->machine->name ?? $row->machine_code }}</h3>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span
                                        class="text-[9px] font-mono text-slate-400 uppercase tracking-tighter">{{ $row->machine_code }}</span>
                                    <span
                                        class="text-[9px] font-black uppercase tracking-tighter {{ $row->entry_type === 'check' ? 'text-emerald-500' : 'text-red-500' }}">
                                        {{ $row->entry_type === 'check' ? 'Cek' : 'Stop' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Middle: Detailed Content (Compact) --}}
                        <div class="flex-1">
                            @if($row->entry_type === 'check')
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                    {{-- Checklist Icons --}}
                                    <div class="flex items-center bg-slate-50 p-1 rounded-lg border border-slate-100 gap-1">
                                        @php
                                            $checks = [
                                                ['label' => 'CEKAM', 'val' => $row->check_cekam],
                                                ['label' => 'OZON', 'val' => $row->check_air_ozo],
                                                ['label' => 'ERETAN', 'val' => $row->check_eretan],
                                                ['label' => 'PISAU', 'val' => $row->check_pisau],
                                                ['label' => 'BERSIH', 'val' => $row->check_kebersihan],
                                                ['label' => 'OLI', 'val' => $row->check_oli],
                                            ];
                                        @endphp
                                        @foreach($checks as $check)
                                            <div class="flex flex-col items-center px-1.5 py-0.5 rounded {{ $check['val'] === 'Ya' ? 'text-emerald-600' : 'text-red-500' }}"
                                                title="{{ $check['label'] }}">
                                                <span class="text-[7px] font-black leading-none mb-0.5">{{ $check['label'] }}</span>
                                                <span class="material-icons-round text-[12px]">
                                                    {{ $check['val'] === 'Ya' ? 'check_circle' : 'cancel' }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Technical Specs --}}
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center gap-1.5 px-2 py-1 bg-white border border-slate-100 rounded-lg text-[10px]">
                                            <span class="material-icons-round text-slate-300 text-[12px]">straighten</span>
                                            <span
                                                class="font-bold text-slate-600 truncate max-w-[80px]">{{ $row->size_category }}</span>
                                            <span
                                                class="font-black text-[8px] uppercase text-blue-500">{{ $row->rpm_feeding_mode }}</span>
                                        </div>

                                        @php
                                            $std = $standards[$row->size_category][$row->rpm_feeding_mode] ?? null;
                                            $rpmClass = getStatusClass($row->rpm_value, $std['rpm'] ?? null);
                                            $rpmIdClass = getStatusClass($row->rpm_id_value, $std['rpm'] ?? null);
                                            $feedClass = getStatusClass($row->feeding_value, $std['feeding'] ?? null);
                                            $feedIdClass = getStatusClass($row->feeding_id_value, $std['feeding'] ?? null);
                                        @endphp

                                        <div class="flex gap-3 border-l border-slate-100 pl-3">
                                            <div class="flex flex-col gap-0.5">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-[7px] font-bold text-slate-300 uppercase">RPM S:</span>
                                                    <span class="text-[10px] {{ $rpmClass }}">{{ $row->rpm_value }}</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <span class="text-[7px] font-bold text-slate-300 uppercase">RPM I:</span>
                                                    <span class="text-[10px] {{ $rpmIdClass }}">{{ $row->rpm_id_value ?? '-' }}</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-0.5">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-[7px] font-bold text-slate-300 uppercase">FD S:</span>
                                                    <span class="text-[10px] {{ $feedClass }}">{{ $row->feeding_value }}</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <span class="text-[7px] font-bold text-slate-300 uppercase">FD I:</span>
                                                    <span class="text-[10px] {{ $feedIdClass }}">{{ $row->feeding_id_value ?? '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($row->note)
                                        <div
                                            class="flex items-center gap-1 px-2 py-1 bg-blue-50/50 rounded-lg border border-blue-100/30">
                                            <span class="material-icons-round text-blue-400 text-[12px]">chat_bubble_outline</span>
                                            <p class="text-[10px] text-slate-500 italic truncate max-w-[150px]">{{ $row->note }}</p>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
                                    <div class="flex items-center gap-2 text-red-600">
                                        <span class="material-icons-round text-[14px]">warning</span>
                                        <span class="font-bold text-xs uppercase tracking-tight">{{ $row->reason }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-[10px] text-slate-400">
                                        <div class="flex items-center gap-1 font-medium">
                                            <span class="material-icons-round text-[12px]">schedule</span>
                                            <span>{{ \Carbon\Carbon::parse($row->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($row->end_time)->format('H:i') }}</span>
                                        </div>
                                        <span class="font-black text-red-500 uppercase">{{ $row->duration_minutes }} min</span>
                                    </div>
                                    @if($row->note)
                                        <div class="flex items-center gap-1 px-2 py-1 bg-slate-50 rounded-lg border border-slate-100">
                                            <span class="material-icons-round text-slate-300 text-[12px]">notes</span>
                                            <p class="text-[10px] text-slate-500 italic truncate max-w-[200px]">{{ $row->note }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Right: Actions --}}
                        <div class="flex items-center justify-end lg:w-20 gap-1">
                            @if(!$isLocked && !auth()->user()->isReadOnly())
                                <a href="{{ route('daily_report.downtime.edit', $row->id) }}"
                                   class="w-8 h-8 flex items-center justify-center text-slate-200 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                   title="Edit">
                                    <span class="material-icons-round text-lg">edit</span>
                                </a>

                                <form action="{{ route('daily_report.downtime.destroy', $row->id) }}" method="POST"
                                    class="inline-block delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                        class="w-8 h-8 flex items-center justify-center text-slate-200 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all btn-delete"
                                        title="Hapus">
                                        <span class="material-icons-round text-lg">delete_outline</span>
                                    </button>
                                </form>
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