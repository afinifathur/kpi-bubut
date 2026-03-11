<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// PENTING: users disimpan di koneksi 'master' (masterdata_kpi), bukan 'mysql' (kpi_bubut)
$cols = \Illuminate\Support\Facades\Schema::connection('master')->getColumnListing('users');

$updates = [];
if (in_array('department_code', $cols))
    $updates['department_code'] = '404.7';
if (in_array('department', $cols))
    $updates['department'] = '404.7';

if (!empty($updates)) {
    \Illuminate\Support\Facades\DB::connection('master')->table('users')
        ->where('email', 'adminbubuttimur@peroniks.com')
        ->update($updates);
    echo "Successfully updated user department columns: " . implode(', ', array_keys($updates)) . " to 404.7\n";
} else {
    echo "Could not find any department column in users table.\n";
}
