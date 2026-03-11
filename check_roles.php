<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$users = \App\Models\User::whereIn('email', ['adminhr@peroniks.com', 'managerhr@peroniks.com'])->get();
$data = [];
foreach ($users as $u) {
    $data[$u->email] = $u->role;
}
file_put_contents('roles.json', json_encode($data));
