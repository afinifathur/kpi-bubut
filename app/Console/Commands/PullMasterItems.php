<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MdItemMirror;

class PullMasterItems extends Command
{
    protected $signature = 'pull:master-items';
    protected $description = 'Pull active items from Master Data KPI';

    public function handle(): int
    {
        $this->info('Pulling master items...');

        $count = 0;

        DB::connection('master')
            ->table('md_items')
            ->where('status', 'active')
            ->orderBy('code')
            ->chunk(500, function ($items) use (&$count) {

                foreach ($items as $item) {

                    $mirror = MdItemMirror::where('code', $item->code)->first();

                    if (
                        $mirror &&
                        $mirror->source_updated_at &&
                        \Carbon\Carbon::parse($mirror->source_updated_at)->equalTo(\Carbon\Carbon::parse($item->updated_at))
                    ) {
                        continue; // tidak ada perubahan
                    }

                    MdItemMirror::updateOrCreate(
                        ['code' => $item->code],
                        [
                            'name' => $item->name,
                            'department_code' => $item->department_code, // ✅ WAJIB
                            'cycle_time_sec' => $item->cycle_time_sec,
                            'status' => $item->status,
                            'aisi' => $item->aisi,
                            'standard' => $item->standard,
                            'unit_weight' => $item->unit_weight,
                            'source_updated_at' => $item->updated_at,
                            'last_sync_at' => now(),
                        ]
                    );

                    $count++;
                }
            });

        $this->info("Pulled {$count} items.");

        return Command::SUCCESS;
    }
}
