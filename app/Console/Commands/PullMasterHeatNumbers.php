<?php

namespace App\Console\Commands;

use App\Models\MdHeatNumberMirror;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PullMasterHeatNumbers extends Command
{
    protected $signature = 'pull:master-heat-numbers';

    protected $description = 'Pull Heat Numbers from masterdatakpi source';

    public function handle()
    {
        $this->info('Starting Heat Number synchronization...');
        $count = 0;

        // Ambil data dari koneksi 'master'
        DB::connection('master')
            ->table('md_heat_numbers')
            ->where('status', 'active')
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    $travelerNumber = ! empty($row->traveler_number) ? trim($row->traveler_number) : null;

                    if ($travelerNumber) {
                        $mirror = MdHeatNumberMirror::where('traveler_number', $travelerNumber)->first();
                    } else {
                        $mirror = MdHeatNumberMirror::where('heat_number', $row->heat_number)
                            ->where('item_code', $row->item_code)
                            ->whereNull('traveler_number')
                            ->first();
                    }

                    // Compare source_updated_at to avoid unnecessary writes
                    if ($mirror && $mirror->source_updated_at && Carbon::parse($mirror->source_updated_at)->equalTo(Carbon::parse($row->updated_at))) {
                        continue;
                    }

                    $match = $travelerNumber
                        ? ['traveler_number' => $travelerNumber]
                        : ['heat_number' => trim($row->heat_number), 'item_code' => trim($row->item_code), 'traveler_number' => null];

                    MdHeatNumberMirror::updateOrCreate(
                        $match,
                        [
                            'traveler_number' => $travelerNumber,
                            'heat_number' => trim($row->heat_number),
                            'item_code' => trim($row->item_code),
                            'kode_produksi' => $row->kode_produksi ? trim($row->kode_produksi) : null,
                            'item_name' => $row->item_name ? trim($row->item_name) : null,
                            'size' => $row->size ? trim($row->size) : null,
                            'customer' => $row->customer ? trim($row->customer) : null,
                            'line' => $row->line ? trim($row->line) : null,
                            'cor_qty' => (int) $row->cor_qty,
                            'status' => $row->status ?? 'active',
                            'source_updated_at' => $row->updated_at,
                            'last_sync_at' => now(),
                        ]
                    );
                    $count++;
                }
            });

        $this->info("Heat Number synchronization finished. Total updated: {$count}");
    }
}
