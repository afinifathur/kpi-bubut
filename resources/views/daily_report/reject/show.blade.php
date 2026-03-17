@extends('layouts.app')

@section('title', 'Detail Kerusakan - ' . $date)

@section('content')

    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-gray-400 text-sm mb-1">
                <a href="{{ route('daily_report.reject.index') }}" class="hover:text-orange-600 transition-colors">Daftar
                    Harian</a>
                <span>/</span>
                <span class="text-gray-600 font-medium">Detail Kerusakan</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">
                {{ \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('daily_report.reject.pdf', $date) }}" target="_blank"
                class="flex items-center gap-2 bg-white border border-gray-200 px-4 py-2 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all shadow-sm">
                <span class="material-icons-round text-red-500">picture_as_pdf</span>
                Export PDF
            </a>
            @if(!$isLocked && !auth()->user()->isReadOnly())
                <a href="{{ route('reject.create') }}"
                    class="flex items-center gap-2 bg-orange-600 px-4 py-2 rounded-xl text-sm font-bold text-white hover:bg-orange-700 transition-all shadow-lg shadow-orange-900/20">
                    <span class="material-icons-round">add</span>
                    Input Baru
                </a>
            @endif
        </div>
    </div>

    @if($isLocked)
        <div class="bg-green-50 border border-green-200 p-4 rounded-2xl flex items-center gap-3 mb-6">
            <span class="material-icons-round text-green-600">lock</span>
            <p class="text-sm font-medium text-green-800">Data hari ini telah dikunci (Final). Tidak dapat menambah atau
                menghapus data.</p>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider w-32">Mesin</th>
                        <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Detail</th>
                        <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right w-24">Qty</th>
                        <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider w-48">Operator</th>
                        @if(!$isLocked && !auth()->user()->isReadOnly())
                            <th class="p-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center w-20">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold font-mono">
                                    {{ $row->machine_code }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-gray-800 leading-tight">
                                    {{ $row->item->name ?? $row->item_code }}
                                </div>
                                <div class="text-xs text-orange-600 font-medium mt-1">
                                    {{ $row->reject_reason }}
                                </div>
                                @if($row->note)
                                    <div class="text-[10px] text-gray-400 italic mt-1 bg-gray-50 inline-block px-1.5 rounded">
                                        "{{ $row->note }}"
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <span class="text-lg font-black text-slate-700">{{ number_format($row->reject_qty) }}</span>
                                <span class="text-[10px] text-gray-400 font-bold uppercase ml-0.5">pcs</span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                        {{ substr($row->operator->name ?? $row->operator_code, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-gray-700">{{ $row->operator->name ?? $row->operator_code }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono">{{ $row->operator_code }}</div>
                                    </div>
                                </div>
                            </td>
                            @if(!$isLocked && !auth()->user()->isReadOnly())
                                <td class="p-4 text-center">
                                    <form action="{{ route('daily_report.reject.destroy', $row->id) }}" method="POST"
                                        onsubmit="return confirm('Hapus data reject ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-300 hover:text-red-500 transition-colors">
                                            <span class="material-icons-round text-lg">delete</span>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-gray-400">
                                <span class="material-icons-round text-4xl mb-2 block">inventory_2</span>
                                Belum ada data reject untuk tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
