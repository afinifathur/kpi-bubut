<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MdMachineMirror;

class PullMasterMachines extends Command
{
    protected $signature = 'pull:master-machines';
    protected $description = 'Pull ACTIVE machines from masterdata into KPI mirror';

    public function handle(): int
    {
        $masters = DB::connection('master')
            ->table('md_machines')
            ->select([
                'code',
                'name',
                'department_code',
                'line_code',
                'status',
                'last_seen_at',
                'last_active_module',
                'last_sync_at',
            ])
            ->get();

        $synced = 0;

        foreach ($masters as $m) {

            // 🔒 DOUBLE GUARD — HARD STOP
            if (($m->status ?? null) !== 'active') {
                continue;
            }

            $runtimeStatus = $this->computeRuntimeStatus($m->last_seen_at);

            MdMachineMirror::updateOrCreate(
                ['code' => $m->code],
                [
                    'name'               => $m->name,
                    'department_code'    => $m->department_code,
                    'line_code'          => $m->line_code,
                    'status'             => 'active', // force active
                    'runtime_status'     => $runtimeStatus,
                    'last_seen_at'       => $m->last_seen_at,
                    'last_active_module' => $m->last_active_module,
                    'last_sync_at'       => $m->last_sync_at,
                ]
            );

            $synced++;
        }

        $this->info("Pulled {$synced} active machines.");
        return Command::SUCCESS;
    }

    /**
     * Compute runtime status based on last_seen_at.
     * Runtime ≠ lifecycle
     */
    private function computeRuntimeStatus(?string $lastSeenAt): string
    {
        if (!$lastSeenAt) {
            return 'OFFLINE';
        }

        $diff = now()->diffInMinutes($lastSeenAt);

        if ($diff <= 5) {
            return 'ONLINE';
        }

        if ($diff <= 30) {
            return 'STALE';
        }

        return 'OFFLINE';
    }
}
