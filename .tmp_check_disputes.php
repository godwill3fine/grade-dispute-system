<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'DB=' . config('database.default') . PHP_EOL;
echo 'Host=' . config('database.connections.mysql.host') . PHP_EOL;
echo 'Name=' . config('database.connections.mysql.database') . PHP_EOL;

$rows = Illuminate\Support\Facades\DB::table('disputes')
    ->select('id', 'status', 'assigned_role', 'updated_at')
    ->orderBy('id')
    ->get();

echo 'count=' . count($rows) . PHP_EOL;
foreach ($rows as $r) {
    echo $r->id . ' | ' . $r->status . ' | ' . $r->assigned_role . ' | ' . $r->updated_at . PHP_EOL;
}
