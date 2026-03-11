<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductionLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::where('email', 'adminbubuttimur@peroniks.com')->first();
Auth::login($user);

// Simulate operatorIndex query
$dates = ProductionLog::selectRaw('
        production_date, 
        SUM(actual_qty) as total_qty, 
        SUM(target_qty) as total_target, 
        AVG(achievement_percent) as avg_kpi,
        COUNT(*) as total_logs
    ')
    ->groupBy('production_date')
    ->orderBy('production_date', 'desc')
    ->get();

echo "As User: " . $user->email . " (Dept: " . $user->department_code . " Role: " . $user->role . ")\n";
echo "Found " . count($dates) . " dates.\n";
foreach ($dates as $d) {
    echo $d->production_date . " -> " . $d->total_logs . " logs\n";
}

$sql = ProductionLog::selectRaw('production_date, COUNT(*)')->toSql();
echo "\nSQL Generated:\n" . $sql . "\n";
$bindings = ProductionLog::selectRaw('production_date, COUNT(*)')->getBindings();
echo "Bindings: " . json_encode($bindings) . "\n";

