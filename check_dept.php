<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$dept = DB::connection('master')->table('md_departments')->where('code', '404.7')->first();
if ($dept) {
    echo "EXISTS: " . $dept->name;
} else {
    echo "DOES NOT EXIST";
}
