<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$d = Illuminate\Support\Facades\DB::table('disputes')->select('id','status','assigned_role','assigned_to','updated_at')->where('id',3)->first();
$l = Illuminate\Support\Facades\DB::table('dispute_logs')->where('dispute_id',3)->orderByDesc('id')->limit(2)->get();
if (!$d) { echo "missing\n"; exit; }
echo "id={$d->id} status={$d->status} role={$d->assigned_role} to={$d->assigned_to} updated_at={$d->updated_at}\n";
foreach ($l as $x) { echo "log {$x->id}: {$x->action}\n"; }
