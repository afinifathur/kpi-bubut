<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('email', 'adminbubuttimur@peroniks.com')->first();
$deptCode = $user->department_code;
echo "1. Department Code Value: '" . $deptCode . "'\n";
echo "2. Department Code Length: " . strlen($deptCode) . "\n";
echo "3. User Data:\n" . json_encode(['email' => $user->email, 'dcode' => $user->department_code]) . "\n";
