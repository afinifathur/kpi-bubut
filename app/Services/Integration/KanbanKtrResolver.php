<?php

namespace App\Services\Integration;

use App\Models\MdHeatNumberMirror;
use Illuminate\Support\Facades\Log;
use Throwable;

class KanbanKtrResolver
{
    protected ?string $connectionName;

    public function __construct(?string $connectionName = null)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * Resolve KTR (Traveler Number) to full production item details via local mirror.
     *
     * @param  string  $ktrCode  e.g. "KTR-20260915-0001"
     */
    public function resolve(string $ktrCode): array
    {
        $cleanKtr = strtoupper(trim($ktrCode));

        if (empty($cleanKtr)) {
            return [
                'success' => false,
                'error_code' => 'EMPTY_CODE',
                'message' => 'Kode Kitir (KTR) tidak boleh kosong.',
            ];
        }

        try {
            $row = MdHeatNumberMirror::where('traveler_number', $cleanKtr)->first();

            if (! $row) {
                return [
                    'success' => false,
                    'error_code' => 'NOT_FOUND',
                    'message' => "Kitir ({$cleanKtr}) tidak ditemukan di Master Data.",
                ];
            }

            // Validasi Scope Flange (Pilot hanya FLANGE)
            if (! $this->isFlangeScope(null, $row->item_name, $row->item_code)) {
                return [
                    'success' => false,
                    'error_code' => 'OUT_OF_SCOPE',
                    'message' => "Kitir ({$cleanKtr}) berada di luar scope KPI-Bubut Flange.",
                ];
            }

            // Format Line Number (e.g. 1 -> "LINE 1")
            $formattedLine = $this->formatLineNumber($row->line);

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
                    'line' => $formattedLine ?? ($row->line ?: '-'),
                    'qty_cor' => (int) $row->cor_qty,
                ],
            ];
        } catch (Throwable $e) {
            Log::error('[KanbanKtrResolver:ERROR] Failed to resolve KTR: '.$e->getMessage(), [
                'ktr' => $cleanKtr,
                'exception' => get_class($e),
            ]);

            return [
                'success' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message' => 'Gagal memproses data Kitir: '.$e->getMessage(),
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

        return 'LINE '.$str;
    }
}
