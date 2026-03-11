<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('email', 'adminbubuttimur@peroniks.com')->first();
if ($user) {
    echo "USER EXISTS. Email: {$user->email}, Role: {$user->role}, Dept: {$user->department_code}\n";
} else {
    echo "USER DOES NOT EXIST.\n";
}
