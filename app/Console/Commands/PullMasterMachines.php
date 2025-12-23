<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MdMachineMirror;

class PullMasterMachines extends Command
{
    protected $signature = 'pull:master-machines';
    protected $description = 'Pull master machine data from Master Data System';

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

        foreach ($masters as $m) {
            // runtime_status dihitung di MASTER via accessor,
            // jadi kita pull dari SELECT terpisah (computed via SQL view)
            // atau (opsi praktis) hitung ulang dengan aturan yang sama:
            $runtimeStatus = $this->computeRuntimeStatus($m->last_seen_at);

            MdMachineMirror::updateOrCreate(
                ['code' => $m->code],
                [
                    'name' => $m->name,
                    'department_code' => $m->department_code,
                    'line_code' => $m->line_code,
                    'status' => $m->status,
                    'runtime_status' => $runtimeStatus,
                    'last_seen_at' => $m->last_seen_at,
                    'last_active_module' => $m->last_active_module,
                    'last_sync_at' => $m->last_sync_at,
                ]
            );
        }

        $this->info("Pulled {$masters->count()} machines.");
        return Command::SUCCESS;
    }

    private function computeRuntimeStatus(?string $lastSeenAt): string
    {
        if (!$lastSeenAt) {
            return 'OFFLINE';
        }

        $diff = now()->diffInMinutes($lastSeenAt);

        if ($diff <= 5) return 'ONLINE';
        if ($diff <= 30) return 'STALE';
        return 'OFFLINE';
    }
}
