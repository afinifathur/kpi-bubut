@extends('layouts.app')

@section('title', 'Detail Laporan HR')

@section('content')

    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <a href="{{ route('hr_report.index') }}" class="p-2 bg-white rounded-xl shadow-sm border border-gray-100 text-gray-400 hover:text-blue-600 transition-all">
                    <span class="material-icons-round leading-none">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Laporan HR</h1>
                    <p class="text-sm text-gray-400 font-mono">{{ $report->report_number }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('hr_report.pdf', $report->id) }}" target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all font-bold text-sm shadow-lg shadow-blue-500/20">
                    <span class="material-icons-round text-sm">picture_as_pdf</span>
                    Download PDF
                </a>
                @if(auth()->user()->canManageHrReports())
                    <a href="{{ route('hr_report.edit', $report->id) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 transition-all font-bold text-sm">
                        <span class="material-icons-round text-sm">edit</span>
                        Edit
                    </a>
                    <form action="{{ route('hr_report.destroy', $report->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2 bg-red-50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all border border-red-100">
                            <span class="material-icons-round leading-none">delete</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <!-- Case Info Card -->
            <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-[0.03] scale-[4] rotate-12 select-none">
                    <span class="material-icons-round text-8xl">assignment</span>
                </div>
                
                <div class="relative z-10">
                    <div class="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b border-gray-50">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 bg-blue-100 text-blue-700 rounded text-[10px] font-black uppercase tracking-wider">
                                {{ $report->category }}
                            </span>
                        </div>
                        <span class="text-gray-200">|</span>
                        <div class="flex items-center gap-2 text-gray-400">
                            <span class="material-icons-round text-sm">calendar_today</span>
                            <span class="text-xs font-bold uppercase tracking-wider">{{ \Carbon\Carbon::parse($report->report_date)->isoFormat('dddd, D MMMM Y') }}</span>
                        </div>
                    </div>

                    <h2 class="text-3xl font-black text-gray-900 mb-6">{{ $report->title }}</h2>
                    
                    <div class="grid grid-cols-1 gap-8">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Deskripsi Masalah</label>
                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-gray-700 leading-relaxed whitespace-pre-line text-sm">
                                {{ $report->description }}
                            </div>
                        </div>

                        <div class="space-y-6">
                            @if($report->data_link)
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Tautan Data (Evidence)</label>
                                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl flex items-center justify-between group">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-500/20 shrink-0">
                                                <span class="material-icons-round">link</span>
                                            </div>
                                            <div class="overflow-hidden">
                                                <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest leading-none mb-1">Traceable Link</p>
                                                <p class="text-xs font-bold text-blue-900 truncate">{{ $report->data_link }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ $report->data_link }}" target="_blank"
                                            class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition-all shadow-md shrink-0 ml-4">
                                            Buka Bukti
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Status Laporan</label>
                                <div class="bg-white border border-gray-100 rounded-2xl p-4 flex items-center justify-between">
                                    @php
                                        $statusClasses = [
                                            'Open' => 'bg-red-100 text-red-600 border-red-200',
                                            'Investigating' => 'bg-orange-100 text-orange-600 border-orange-200',
                                            'Action Plan' => 'bg-blue-100 text-blue-600 border-blue-200',
                                            'Monitoring' => 'bg-purple-100 text-purple-600 border-purple-200',
                                            'Closed' => 'bg-green-100 text-green-600 border-green-200'
                                        ];
                                        $class = $statusClasses[$report->status] ?? 'bg-gray-100 text-gray-600 border-gray-200';
                                    @endphp
                                    <div class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black border {{ $class }} uppercase tracking-widest pulse-animation">
                                        {{ $report->status }}
                                    </div>

                                    <form action="{{ route('hr_report.update', $report->id) }}" method="POST" class="flex-1 ml-6">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="category" value="{{ $report->category }}">
                                        <input type="hidden" name="title" value="{{ $report->title }}">
                                        <input type="hidden" name="description" value="{{ $report->description }}">
                                        <input type="hidden" name="data_link" value="{{ $report->data_link }}">
                                        <input type="hidden" name="root_cause" value="{{ $report->root_cause }}">
                                        <input type="hidden" name="corrective_action" value="{{ $report->corrective_action }}">
                                        
                                        <select name="status" onchange="this.form.submit()" 
                                            class="w-full bg-gray-50 border-gray-100 rounded-xl text-[10px] font-bold text-gray-500 focus:ring-blue-500 transition-all cursor-pointer"
                                            @if(!auth()->user()->canManageHrReports()) disabled @endif>
                                            @foreach(['Open', 'Investigating', 'Action Plan', 'Monitoring', 'Closed'] as $st)
                                                <option value="{{ $st }}" {{ $report->status == $st ? 'selected' : '' }}>Set Status: {{ $st }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analysis & Action Card -->
            <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-8 border-b border-gray-50 pb-6">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                        <span class="material-icons-round">analytics</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-800">Analisis & Tindakan</h3>
                        <p class="text-xs text-gray-400">Hasil investigasi dan rencana perbaikan</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-orange-400 rounded-full"></span>
                            <label class="text-[10px] font-bold text-orange-400 uppercase tracking-widest">Akar Masalah (Root Cause)</label>
                        </div>
                        <div class="bg-orange-50/20 rounded-2xl p-6 border border-orange-100/50 text-gray-700 text-sm leading-relaxed min-h-[100px]">
                            @if($report->root_cause)
                                {{ $report->root_cause }}
                            @else
                                <span class="text-gray-400 italic">Belum ada analisis akar masalah.</span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <label class="text-[10px] font-bold text-green-500 uppercase tracking-widest">Tindakan Perbaikan</label>
                        </div>
                        <div class="bg-green-50/20 rounded-2xl p-6 border border-green-100/50 text-gray-700 text-sm leading-relaxed min-h-[100px]">
                            @if($report->corrective_action)
                                {{ $report->corrective_action }}
                            @else
                                <span class="text-gray-400 italic">Belum ada rencana tindakan perbaikan.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>


            
            <div class="text-center py-8">
                <p class="text-[10px] text-gray-300 font-mono tracking-widest uppercase">KPI-Bubut Internal Tracking System • {{ date('Y') }}</p>
            </div>
        </div>
    </div>

    <style>
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.1); }
            70% { box-shadow: 0 0 0 10px rgba(0, 0, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 0, 0, 0); }
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
    </style>

@endsection
