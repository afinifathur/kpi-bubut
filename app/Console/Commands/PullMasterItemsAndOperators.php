<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MdItemMirror;
use App\Models\MdOperatorMirror;

class PullMasterItemsAndOperators extends Command
{
    protected $signature = 'pull:master-items-operators';
    protected $description = 'Pull ACTIVE items and operators from Master Data';

    public function handle(): int
    {
        /*
        |--------------------------------------------------------------------------
        | ITEMS (ENTITAS)
        |--------------------------------------------------------------------------
        */
        $items = DB::connection('master')
            ->table('md_items')
            ->select([
                'code',
                'name',
                'department_code',
                'cycle_time_sec',
                'status',
            ])
            ->get();

        $itemSynced = 0;

        foreach ($items as $item) {

            // 🔒 DOUBLE GUARD — SKIP INACTIVE
            if (($item->status ?? null) !== 'active') {
                continue;
            }

            MdItemMirror::updateOrCreate(
                ['code' => $item->code],
                [
                    'name'             => $item->name,
                    'department_code'  => $item->department_code,
                    'cycle_time_sec'   => $item->cycle_time_sec,
                    'status'           => 'active', // force active
                ]
            );

            $itemSynced++;
        }

        /*
        |--------------------------------------------------------------------------
        | OPERATORS (MANUSIA — HISTORI)
        |--------------------------------------------------------------------------
        */
        $operators = DB::connection('master')
            ->table('md_operators')
            ->select([
                'code',
                'name',
                'department_code',
                'status',
            ])
            ->get();

        $operatorSynced = 0;

        foreach ($operators as $op) {

            // 🔒 HARD STOP — OPERATOR INACTIVE TIDAK PERNAH DITARIK
            if (($op->status ?? null) !== 'active') {
                continue;
            }

            MdOperatorMirror::updateOrCreate(
                ['code' => $op->code],
                [
                    'name'             => $op->name,
                    'department_code'  => $op->department_code,
                    'status'           => 'active', // force active
                ]
            );

            $operatorSynced++;
        }

        $this->info("Pulled {$itemSynced} active items & {$operatorSynced} active operators.");
        return Command::SUCCESS;
    }
}
