@extends('layouts.app')

@section('title', 'Daftar Harian Downtime')

@section('content')

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Daftar Harian Downtime</h1>
        <p class="text-gray-500">Pilih tanggal untuk melihat detail laporan downtime.</p>
    </div>

    @if($dates->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900">Belum ada data downtime</h3>
            <p class="text-gray-500 mt-1">Data downtime yang diinput akan muncul di sini berdasarkan tanggal.</p>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach($dates as $row)
                <a href="{{ route('daily_report.downtime.show', $row->downtime_date) }}"
                    class="group bg-white rounded-xl border border-gray-200 p-4 shadow-sm hover:shadow-md hover:border-blue-400 transition-all duration-200 flex items-center justify-between">

                    <div class="flex items-center gap-4">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-full bg-red-50 text-red-600 group-hover:bg-red-600 group-hover:text-white transition-colors shrink-0">
                            <span
                                class="text-xs font-bold uppercase">{{ \Carbon\Carbon::parse($row->downtime_date)->locale('id')->isoFormat('MMM') }}</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700">
                                {{ \Carbon\Carbon::parse($row->downtime_date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                            </h3>
                            <p class="text-xs text-gray-400 font-mono">
                                {{ \Carbon\Carbon::parse($row->downtime_date)->format('d-m-Y') }} • {{ $row->total_logs }} Kejadian
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-6 text-right">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Total Durasi</p>
                            <p class="text-sm font-bold text-gray-800">{{ number_format($row->total_minutes) }} <span
                                    class="text-[10px] font-normal text-gray-500">menit</span></p>
                        </div>

                        {{-- LOCK ICON --}}
                        <div class="px-3 cursor-pointer z-10" onclick="toggleLock(event, '{{ $row->downtime_date }}')"
                            title="{{ $row->is_locked ? 'Locked (Click to Unlock)' : 'Unlocked (Click to Lock)' }}">
                            @if($row->is_locked)
                                <!-- Green Locked (Closed Solid) -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="w-6 h-6 text-green-600 hover:text-green-700 transition-colors">
                                    <path fill-rule="evenodd"
                                        d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z"
                                        clip-rule="evenodd" />
                                </svg>
                            @else
                                <!-- Gray Unlocked (Open Outline) -->
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" class="w-6 h-6 text-gray-400 hover:text-gray-600 transition-colors">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            @endif
                        </div>
                        <div class="pl-4">
                            <svg class="w-5 h-5 text-gray-300 group-hover:text-blue-500 transition-colors" fill="none"
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

@push('scripts')
    <script>
        function toggleLock(event, date) {
            // Prevent clicking the parent link
            event.preventDefault();
            event.stopPropagation();

            fetch("{{ route('daily_report.downtime.toggle_lock') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ date: date })
            })
                .then(response => {
                    if (response.status === 403) {
                        Swal.fire('Unauthorized', 'Hanya Direction/MR yang bisa mengubah lock.', 'error');
                        return Promise.reject('Unauthorized');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Reload to reflect icon change
                        window.location.reload();
                    } else {
                        Swal.fire('Error', 'Gagal update lock status', 'error');
                    }
                })
                .catch(err => console.error(err));
        }
    </script>
@endpush