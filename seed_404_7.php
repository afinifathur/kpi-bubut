<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MdItemMirror;
use Illuminate\Support\Facades\DB;

try {
    echo "Starting Database Seeding (Items Only)...\n";

    // Clone Items from 404.1 to 404.7
    $items4041 = MdItemMirror::where('department_code', '404.1')->get();
    $count = 0;
    foreach ($items4041 as $item) {
        $exists = MdItemMirror::where('department_code', '404.7')
            ->where('code', $item->code)
            ->exists();

        if (!$exists) {
            MdItemMirror::create([
                'department_code' => '404.7',
                'code' => $item->code,
                'name' => $item->name,
                'category' => $item->category ?? null,
                'cycle_time_sec' => $item->cycle_time_sec,
                'status' => $item->status,
                'source_updated_at' => \Carbon\Carbon::now(),
                'last_sync_at' => \Carbon\Carbon::now(),
            ]);
            $count++;
        }
    }
    echo "SUCCESS: Cloned $count items from 404.1 to 404.7.\n";

} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
