<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class KanbanKtrResolver
{
    protected string $connectionName;

    public function __construct(?string $connectionName = null)
    {
        $this->connectionName = $connectionName ?? 'kanban';
    }

    /**
     * Resolve KTR (Traveler Number) to full production item details.
     *
     * @param string $ktrCode e.g. "KTR-20260915-0001"
     * @return array
     */
    public function resolve(string $ktrCode): array
    {
        $cleanKtr = trim($ktrCode);

        if (empty($cleanKtr)) {
            return [
                'success' => false,
                'error_code' => 'EMPTY_CODE',
                'message' => 'Kode Kitir (KTR) tidak boleh kosong.',
            ];
        }

        try {
            $row = DB::connection($this->connectionName)
                ->table('sand_casting_casting_result_lines as l')
                ->join('sand_casting_casting_results as r', 'r.id', '=', 'l.sand_casting_casting_result_id')
                ->leftJoin('production_plans as p', 'p.id', '=', 'l.production_plan_id')
                ->leftJoin('sand_casting_casting_order_lines as ol', 'ol.id', '=', 'l.sand_casting_casting_order_line_id')
                ->leftJoin('production_plans as ol_p', 'ol_p.id', '=', 'ol.production_plan_id')
                ->where('l.traveler_number', $cleanKtr)
                ->select([
                    'l.id as line_id',
                    'l.traveler_number',
                    'l.qty_good',
                    'r.heat_number',
                    DB::raw('COALESCE(p.code, ol.code, ol_p.code) as kode_produksi'),
                    DB::raw('COALESCE(p.item_code, ol_p.item_code) as item_code'),
                    DB::raw('COALESCE(p.item_name, ol.item_name, ol_p.item_name) as item_name'),
                    DB::raw('COALESCE(p.size, ol.size, ol_p.size) as size'),
                    DB::raw('COALESCE(p.customer, ol.customer, ol_p.customer) as customer'),
                    DB::raw('COALESCE(p.line_number, ol_p.line_number) as line_number'),
                    DB::raw('COALESCE(p.product_scope, ol_p.product_scope) as product_scope'),
                ])
                ->first();

            if (! $row) {
                return [
                    'success' => false,
                    'error_code' => 'NOT_FOUND',
                    'message' => "Kitir ({$cleanKtr}) tidak ditemukan di sistem Kanban.",
                ];
            }

            // Validasi Scope Flange (Pilot hanya FLANGE)
            if (! $this->isFlangeScope($row->product_scope, $row->item_name, $row->item_code)) {
                return [
                    'success' => false,
                    'error_code' => 'OUT_OF_SCOPE',
                    'message' => "Kitir ({$cleanKtr}) berada di luar scope KPI-Bubut Flange.",
                ];
            }

            // Format Line Number (e.g. 1 -> "LINE 1")
            $formattedLine = $this->formatLineNumber($row->line_number);

            return [
                'success' => true,
                'data' => [
                    'ktr' => $row->traveler_number,
                    'heat_number' => trim((string) $row->heat_number),
                    'kode_produksi' => $row->kode_produksi ? trim((string) $row->kode_produksi) : null,
                    'item_code' => $row->item_code ? trim((string) $row->item_code) : null,
                    'item_name' => $row->item_name ? trim((string) $row->item_name) : null,
                    'size' => $row->size ? trim((string) $row->size) : '-',
                    'customer' => $row->customer ? trim((string) $row->customer) : '-',
                    'line' => $formattedLine ?? '-',
                    'qty_cor' => (int) $row->qty_good,
                ],
            ];
        } catch (Throwable $e) {
            Log::error('[KanbanKtrResolver:ERROR] Failed to resolve KTR: ' . $e->getMessage(), [
                'ktr' => $cleanKtr,
                'exception' => get_class($e),
            ]);

            return [
                'success' => false,
                'error_code' => 'CONNECTION_ERROR',
                'message' => 'Gagal menghubungkan ke data Kanban: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the resolved item belongs to the FLANGE scope.
     */
    protected function isFlangeScope(?string $productScope, ?string $itemName, ?string $itemCode): bool
    {
        $scope = strtoupper(trim((string) $productScope));
        if (str_starts_with($scope, 'FLANGE')) {
            return true;
        }
        if (str_starts_with($scope, 'FITTING')) {
            return false;
        }

        $name = strtolower(trim((string) $itemName));
        if (str_contains($name, 'fitting') || str_contains($name, 'elbow') || str_contains($name, 'tee') || str_contains($name, 'reducer')) {
            return false;
        }

        if (str_contains($name, 'flange') || str_contains($name, 'blind') || str_contains($name, 'flens')) {
            return true;
        }

        // If product_scope is not set and name does not indicate fitting, default to true for Sand Casting flanges
        return true;
    }

    /**
     * Format line number e.g. 1 -> "LINE 1".
     */
    protected function formatLineNumber($lineNumber): ?string
    {
        if ($lineNumber === null || $lineNumber === '') {
            return null;
        }

        $str = trim((string) $lineNumber);
        if (str_starts_with(strtoupper($str), 'LINE')) {
            return strtoupper($str);
        }

        return 'LINE ' . $str;
    }
}
