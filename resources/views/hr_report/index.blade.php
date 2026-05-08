@extends('layouts.app')

@section('title', 'Laporan HR (Issues)')

@section('content')

    <div class="w-full" style="width: 100%; max-width: none;">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Laporan HR</h1>
                <p class="text-gray-500">Monitoring anomali, issue harian, dan tindak lanjut perbaikan.</p>
            </div>
            @if(auth()->user()->isHrAdmin())
                <a href="{{ route('hr_report.create') }}" target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/30 font-bold">
                    <span class="material-icons-round text-sm">add</span>
                    Laporan Baru
                </a>
            @endif
        </div>

    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4 bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
        <div class="w-full md:w-1/3">
            <form action="{{ route('hr_report.index') }}" method="GET" class="relative group">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari No. Laporan, Judul..." 
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border-gray-200 rounded-xl text-xs font-bold text-gray-600 focus:ring-blue-500 transition-all">
                <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors text-sm">search</span>
                <button type="submit" class="hidden">Cari</button>
            </form>
        </div>

        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Not Started: <span class="text-gray-900">{{ $counts['not_started'] }}</span></span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">In Progress: <span class="text-gray-900">{{ $counts['in_progress'] }}</span></span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Closed: <span class="text-gray-900">{{ $counts['closed'] }}</span></span>
            </div>
        </div>
    </div>

    @if($reports->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center">
            <div class="w-16 h-16 bg-blue-50 text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-icons-round text-3xl">assignment_late</span>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Belum ada laporan issue</h3>
            @if(auth()->user()->canManageHrReports())
                <p class="text-gray-500 mt-1 max-w-sm mx-auto">Klik tombol "Laporan Baru" untuk mendokumentasikan anomali atau issue yang ditemukan.</p>
            @else
                <p class="text-gray-500 mt-1 max-w-sm mx-auto">Belum ada data laporan HR yang tersedia untuk saat ini.</p>
            @endif
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        @php
                            function sortLink($label, $column, $currentSort, $currentDir) {
                                $newDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
                                $icon = $currentSort === $column ? ($currentDir === 'asc' ? 'expand_less' : 'expand_more') : 'unfold_more';
                                $isActive = $currentSort === $column ? 'text-blue-600' : 'text-gray-400';
                                
                                return '<a href="'.route('hr_report.index', array_merge(request()->query(), ['sort' => $column, 'direction' => $newDir])).'" 
                                    class="inline-flex items-center gap-1 hover:text-blue-600 transition-colors '.$isActive.'">
                                    <span>'.$label.'</span>
                                    <span class="material-icons-round text-sm">'.$icon.'</span>
                                </a>';
                            }
                        @endphp
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider">
                                {!! sortLink('No. Laporan', 'report_number', $sort, $direction) !!}
                            </th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider">
                                {!! sortLink('Tanggal', 'report_date', $sort, $direction) !!}
                            </th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider">
                                {!! sortLink('Kategori', 'category', $sort, $direction) !!}
                            </th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider">
                                {!! sortLink('Judul & Masalah', 'title', $sort, $direction) !!}
                            </th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-center">
                                {!! sortLink('Status', 'status', $sort, $direction) !!}
                            </th>
                            <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-wider text-right text-gray-400">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($reports as $report)
                            <tr class="hover:bg-blue-50/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono font-bold text-blue-600 px-2 py-1 bg-blue-50 rounded-lg">{{ $report->report_number }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-700">
                                        {{ \Carbon\Carbon::parse($report->report_date)->isoFormat('D MMM YYYY') }}
                                    </div>
                                    @if($report->target_completion_date)
                                        @php
                                            $isOverdue = $report->target_completion_date->isPast() && $report->status !== 'Closed';
                                        @endphp
                                        <div class="text-[9px] font-bold {{ $isOverdue ? 'text-red-500' : 'text-blue-500' }} uppercase tracking-tight mt-0.5">
                                            {{ $report->target_completion_date->isoFormat('D MMM YYYY') }}
                                        </div>
                                    @endif
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight mt-0.5">
                                        {{ $report->creator->name ?? 'System' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-[9px] font-black bg-white text-gray-500 border border-gray-100 uppercase tracking-widest">
                                        {{ $report->category }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-800 line-clamp-1 group-hover:text-blue-600 transition-colors mb-1">
                                        <a href="{{ route('hr_report.show', $report->id) }}" class="hover:underline">{{ $report->title }}</a>
                                    </div>
                                    <div class="text-[10px] font-medium text-gray-400 line-clamp-1 mb-1">{{ $report->description }}</div>
                                    @if($report->operator_name)
                                        <div class="text-[9px] font-black text-blue-500 uppercase tracking-tight flex items-center gap-1">
                                            <span class="material-icons-round text-[11px]">person</span>
                                            {{ $report->operator_name }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        @php
                                            $statusClasses = [
                                                'Open' => 'bg-red-50 text-red-600 border-red-100',
                                                'Investigating' => 'bg-orange-50 text-orange-600 border-orange-100',
                                                'Action Plan' => 'bg-blue-50 text-blue-600 border-blue-100',
                                                'Monitoring' => 'bg-purple-50 text-purple-600 border-purple-100',
                                                'Closed' => 'bg-green-50 text-green-600 border-green-100'
                                            ];
                                            $class = $statusClasses[$report->status] ?? 'bg-gray-50 text-gray-600 border-gray-100';
                                            $isOverdue = $report->target_completion_date && $report->target_completion_date->isPast() && $report->status !== 'Closed';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black border {{ $class }} uppercase tracking-widest">
                                            {{ $report->status }}
                                        </span>
                                        @php
                                            $appBadgeClasses = [
                                                'draft' => 'bg-gray-100 text-gray-400 border-gray-200',
                                                'pending' => 'bg-gray-100 text-gray-400 border-gray-200',
                                                'submitted' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                'approved' => 'bg-green-100 text-green-700 border-green-200',
                                                'rejected' => 'bg-red-100 text-red-700 border-red-200'
                                            ];
                                            $appLabels = [
                                                'draft' => 'Draft',
                                                'pending' => 'Draft',
                                                'submitted' => 'Waiting Approval',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected'
                                            ];
                                            $appClass = $appBadgeClasses[$report->approval_status] ?? 'bg-gray-100 text-gray-400 border-gray-200';
                                            $appLabel = $appLabels[$report->approval_status] ?? strtoupper($report->approval_status);
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[8px] font-black border {{ $appClass }} uppercase tracking-widest">
                                            {{ $appLabel }}
                                        </span>
                                        @if($isOverdue)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[8px] font-black bg-red-100 text-red-600 border border-red-200 uppercase tracking-widest animate-pulse">
                                                TELAT
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if($report->data_link)
                                            <a href="{{ $report->data_link }}" target="_blank"
                                                class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all"
                                                title="Buka Link Data">
                                                <span class="material-icons-round text-lg">link</span>
                                            </a>
                                        @endif
                                        <a href="{{ route('hr_report.show', $report->id) }}"
                                            class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all"
                                            title="Detail Laporan">
                                            <span class="material-icons-round text-lg">visibility</span>
                                        </a>
                                        @php
                                            $isSubmittedOrApproved = in_array($report->approval_status, ['submitted', 'approved']);
                                            $canEdit = auth()->user()->isHrAdmin() && !$isSubmittedOrApproved;
                                        @endphp
                                        @if($canEdit)
                                            <a href="{{ route('hr_report.edit', $report->id) }}"
                                                class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-xl transition-all"
                                                title="Edit Laporan">
                                                <span class="material-icons-round text-lg">edit</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    </div>
@endsection
