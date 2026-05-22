<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProcessTarget;
use Illuminate\Support\Facades\DB;

echo "DATABASE CONFIG:\n";
echo "DB_DATABASE: " . env('DB_DATABASE') . "\n";

try {
    $rows = ProcessTarget::select('month', 'year', DB::raw('count(*) as count'))
        ->groupBy('month', 'year')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();
        
    echo "TARGETS SUMMARY:\n";
    foreach ($rows as $row) {
        echo "  Month: {$row->month}, Year: {$row->year} -> {$row->count} records\n";
    }
} catch (\Exception $e) {
    echo "Error querying targets: " . $e->getMessage() . "\n";
}

try {
    $firstRows = ProcessTarget::limit(5)->get();
    echo "SAMPLE TARGET RECORDS:\n";
    foreach ($firstRows as $row) {
        echo "  ID: {$row->id}, Dept: {$row->department_code}, Process: {$row->process_name}, Month: {$row->month}, Year: {$row->year}, Qty: {$row->target_qty}";
        if (isset($row->item_name)) {
            echo ", Item: {$row->item_name}, Size: {$row->size_name}, Unit: {$row->unit}";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "Error querying sample: " . $e->getMessage() . "\n";
}
