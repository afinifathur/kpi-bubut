@extends('layouts.app')

@section('title', 'Daftar Harian Kerusakan')

@section('content')

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Daftar Harian Kerusakan</h1>
        <p class="text-gray-500">Pilih tanggal untuk melihat detail laporan kerusakan (reject).</p>
    </div>

    @if($dates->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900">Belum ada data kerusakan</h3>
            <p class="text-gray-500 mt-1">Data kerusakan yang diinput akan muncul di sini berdasarkan tanggal.</p>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach($dates as $row)
                <a href="{{ route('daily_report.reject.show', $row->reject_date) }}"
                    class="group bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-md hover:border-orange-400 transition-all duration-200 flex items-center justify-between">

                    <div class="flex items-center gap-4">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-full bg-orange-50 text-orange-600 group-hover:bg-orange-600 group-hover:text-white transition-colors shrink-0">
                            <span
                                class="text-xs font-bold uppercase">{{ \Carbon\Carbon::parse($row->reject_date)->locale('id')->isoFormat('MMM') }}</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 group-hover:text-orange-700">
                                {{ \Carbon\Carbon::parse($row->reject_date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                            </h3>
                            <p class="text-xs text-gray-400 font-mono">
                                {{ \Carbon\Carbon::parse($row->reject_date)->format('d-m-Y') }} • {{ $row->total_logs }} Input
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-6 text-right">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Total Reject</p>
                            <p class="text-sm font-bold text-gray-800">
                                {{ number_format($row->total_qty) }}
                                <span class="text-[10px] font-normal text-gray-500">pcs</span>
                            </p>
                        </div>

                        {{-- LOCK ICON --}}
                        <div class="px-3"
                            title="{{ $row->is_locked ? 'Locked' : 'Unlocked' }}">
                            @if($row->is_locked)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="w-6 h-6 text-green-600">
                                    <path fill-rule="evenodd"
                                        d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z"
                                        clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" class="w-6 h-6 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            @endif
                        </div>
                        <div class="pl-4">
                            <svg class="w-5 h-5 text-gray-300 group-hover:text-orange-500 transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>

                </a>
            @endforeach
        </div>
    @endif

@endsection
