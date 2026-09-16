<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MdItemMirror;
use App\Models\MdOperatorMirror;
use App\Models\MdMachineMirror;

use App\Services\Integration\KanbanKtrResolver;

class AutocompleteController extends Controller
{
    protected KanbanKtrResolver $ktrResolver;

    public function __construct(KanbanKtrResolver $ktrResolver)
    {
        $this->ktrResolver = $ktrResolver;
    }

    /**
     * Resolve KTR Code (Traveler Barcode) via Kanban service
     */
    public function resolveKtr(Request $request)
    {
        $code = $request->get('code') ?? $request->get('ktr') ?? '';
        $result = $this->ktrResolver->resolve((string) $code);

        if (!($result['success'] ?? false)) {
            $statusCode = match ($result['error_code'] ?? '') {
                'OUT_OF_SCOPE' => 422,
                'EMPTY_CODE' => 400,
                'NOT_FOUND' => 404,
                default => 500,
            };

            return response()->json($result, $statusCode);
        }

        return response()->json($result);
    }

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
