<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DowntimeLog;
use App\Models\MdMachine;

class DowntimeController extends Controller
{
    /**
     * Form input downtime
     */
    public function create()
    {
        return view('downtime.input', [
            'machines' => MdMachine::where('active', 1)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Simpan downtime
     */
    public function store(Request $request)
    {
        $request->validate([
            'downtime_date' => 'required|date',
            'machine_code'  => 'required',
            'time_start'    => 'required',
            'time_end'      => 'required|after:time_start',
            'reason'        => 'required',
        ]);

        $start = strtotime($request->time_start);
        $end   = strtotime($request->time_end);

        DowntimeLog::create([
            'downtime_date'   => $request->downtime_date,
            'machine_code'    => $request->machine_code,
            'time_start'      => $request->time_start,
            'time_end'        => $request->time_end,
            'duration_minutes' => ($end - $start) / 60,
            'note'            => $request->reason . ' - ' . ($request->note ?? ''),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Downtime berhasil disimpan');
    }
}
