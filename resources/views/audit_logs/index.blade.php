@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')

    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Audit Logs</h1>
            <p class="text-gray-500">Rekam jejak aktivitas sistem.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6 p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-2 px-3">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Aksi</label>
                <select name="action" class="block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-2 px-3">
                    <option value="">Semua Aksi</option>
                    <option value="LOGIN" {{ request('action') == 'LOGIN' ? 'selected' : '' }}>LOGIN</option>
                    <option value="CREATE" {{ request('action') == 'CREATE' ? 'selected' : '' }}>CREATE (Penambahan)</option>
                    <option value="DELETE" {{ request('action') == 'DELETE' ? 'selected' : '' }}>DELETE (Penghapusan)</option>
                    {{-- <option value="UPDATE">UPDATE</option> --}}
                </select>
            </div>

            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Cari User / IP</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama User, IP, atau Model..."
                       class="block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-2 px-3">
            </div>

            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Filter
            </button>
            
            @if(request()->anyFilled(['date', 'action', 'search']))
                <a href="{{ route('audit_logs.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100 font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Waktu</th>
                        <th class="px-6 py-3">User / Role</th>
                        <th class="px-6 py-3">IP Address</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                        <th class="px-6 py-3">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr class="odd:bg-white even:bg-gray-50 hover:bg-blue-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                            <div class="text-[10px] text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">{{ $log->user_name ?? 'System' }}</div>
                            <div class="text-xs text-gray-500 uppercase">{{ $log->role ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 font-mono text-gray-600">
                            {{ $log->ip_address }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $color = match($log->action) {
                                    'LOGIN' => 'bg-blue-100 text-blue-800',
                                    'CREATE' => 'bg-green-100 text-green-800',
                                    'DELETE' => 'bg-red-100 text-red-800',
                                    'UPDATE' => 'bg-orange-100 text-orange-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $color }}">
                                {{ $log->action }}
                            </span>
                            <div class="text-[10px] text-gray-400 mt-1">{{ $log->model }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if ($log->details)
                                <button type="button"
                                    onclick="viewDetail({{ json_encode($log->details) }}, '{{ $log->action }}', '{{ $log->model }}')"
                                    class="text-blue-600 hover:text-blue-800 underline text-xs font-medium focus:outline-none">
                                    Lihat Data
                                </button>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">
                            Belum ada aktivitas terekam.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    </div>

    @push('scripts')
    <script>
        function viewDetail(details, action, model) {
            // Mapping technical keys to human-readable Indonesian labels
            const keyMap = {
                // Common
                'id': 'ID',
                'name': 'Nama',
                'email': 'Email',
                'role': 'Role',
                'created_at': 'Waktu Dibuat',
                'updated_at': 'Waktu Diupdate',
                
                // Production / Production Input
                'production_date': 'Tanggal Produksi',
                'item_name': 'Nama Item',
                'item_code': 'Kode Item',
                'qty_pcs': 'Jumlah (PCS)',
                'qty_kg': 'Jumlah (KG)',
                'process': 'Proses',
                'operator_name': 'Nama Operator',
                'remark': 'Keterangan',
                'shift': 'Shift',
                'mesin': 'Mesin',
                'target_pcs': 'Target (PCS)',
                'target_kg': 'Target (KG)',
                'category': 'Kategori',
                'description': 'Deskripsi',

                // Downtime
                'reason': 'Alasan',
                'duration': 'Durasi (Menit)',
                'start_time': 'Waktu Mulai',
                'end_time': 'Waktu Selesai',

                // Reject
                'reject_type': 'Jenis Reject',
                'defra_pcs': 'Reject (PCS)',
                'defra_kg': 'Reject (KG)',
            };

            let tableHtml = `
                <div class="text-left">
                    <div class="mb-4 pb-2 border-b border-gray-100 italic text-gray-500 text-xs">
                        Aksi: ${action} | Model: ${model}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse">
                            <tbody>
            `;

            for (const [key, value] of Object.entries(details)) {
                const label = keyMap[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                let displayValue = value;

                // Handle arrays or objects if any (pretty print)
                if (typeof value === 'object' && value !== null) {
                    displayValue = `<pre class="bg-gray-50 p-1 rounded font-mono text-[10px]">${JSON.stringify(value, null, 2)}</pre>`;
                }

                tableHtml += `
                    <tr class="border-b border-gray-50">
                        <th class="py-2 pr-4 font-semibold text-gray-500 w-1/3 align-top whitespace-nowrap">${label}</th>
                        <td class="py-2 text-gray-800 break-words">${displayValue ?? '-'}</td>
                    </tr>
                `;
            }

            tableHtml += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'Detail Aktivitas',
                html: tableHtml,
                width: '600px',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#3b82f6',
                customClass: {
                    container: 'my-swal-container',
                    popup: 'rounded-xl',
                    title: 'text-lg font-bold text-gray-800'
                }
            });
        }
    </script>
    @endpush
@endsection
