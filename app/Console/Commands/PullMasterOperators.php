<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MdOperatorMirror;

class PullMasterOperators extends Command
{
    protected $signature = 'pull:master-operators';
    protected $description = 'Pull active operators from Master Data KPI';

    public function handle()
    {
        $operators = DB::connection('master')
            ->table('md_operators')
            ->where('status', 'active')
            ->get();

        $count = 0;

        foreach ($operators as $op) {
            $mirror = MdOperatorMirror::where('code', $op->code)->first();

            if ($mirror && $mirror->source_updated_at && \Carbon\Carbon::parse($mirror->source_updated_at)->equalTo(\Carbon\Carbon::parse($op->updated_at))) {
                continue;
            }

            MdOperatorMirror::updateOrCreate(
                ['code' => $op->code],
                [
                    'name' => $op->name,
                    'department_code' => $op->department_code,
                    'employment_seq' => $op->employment_seq,
                    'status' => $op->status,
                    'source_updated_at' => $op->updated_at,
                    'last_sync_at' => now(),
                ]
            );

            $count++;
        }

        $this->info("Pulled {$count} operators.");
    }
}
