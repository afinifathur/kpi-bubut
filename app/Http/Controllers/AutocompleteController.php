<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MdItemMirror;
use App\Models\MdOperatorMirror;
use App\Models\MdMachineMirror;

class AutocompleteController extends Controller
{
    /**
     * Search Items
     * Returns JSON list of items matching the query.
     */
    public function searchItems(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $isExact = $request->boolean('exact');

        $items = MdItemMirror::where('status', 'active')
            ->where(function ($q) use ($query, $isExact) {
                if ($isExact) {
                    $q->where('code', $query);
                } else {
                    $q->where('code', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                }
            })
            ->limit(20)
            ->get(['code', 'name', 'cycle_time_sec']);

        return response()->json($items);
    }

    /**
     * Search Operators
     * Returns JSON list of operators matching the query.
     */
    public function searchOperators(Request $request)
    {
        $query = $request->get('q', '');

        $operatorsQuery = MdOperatorMirror::where('status', 'active');

        if (strlen($query) >= 1) {
            $operatorsQuery->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            });
        }

        $operators = $operatorsQuery->orderBy('employment_seq')
            ->limit(20)
            ->get(['code', 'name']);

        return response()->json($operators);
    }
    /**
     * Search Machines
     * Returns JSON list of machines matching the query.
     */
    public function searchMachines(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $machines = MdMachineMirror::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['code', 'name', 'line_code']);

        return response()->json($machines);
    }

    /**
     * Search Heat Numbers
     */
    public function searchHeatNumbers(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $heatNumbers = \App\Models\MdHeatNumberMirror::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('heat_number', 'like', "%{$query}%")
                    ->orWhere('item_name', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'heat_number', 'item_code', 'item_name', 'size', 'customer', 'line']);

        return response()->json($heatNumbers);
    }

    /**
     * Get Item Statistics (Average Cycle Time)
     */
    public function getItemStats(string $code)
    {
        // Calculate average cycle time from ProductionLog
        $avgSeconds = \App\Models\ProductionLog::where('item_code', $code)
            ->where('cycle_time_used_sec', '>', 0)
            ->avg('cycle_time_used_sec');

        if (!$avgSeconds) {
            return response()->json([
                'average_seconds' => 0,
                'formatted' => 'Belum ada data'
            ]);
        }

        $avgSeconds = round($avgSeconds);
        $minutes = floor($avgSeconds / 60);
        $seconds = $avgSeconds % 60;

        return response()->json([
            'average_seconds' => $avgSeconds,
            'formatted' => "{$minutes}m {$seconds}s"
        ]);
    }
}
