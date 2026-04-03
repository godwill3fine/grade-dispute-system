<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$d = App\Models\Dispute::query()->orderByDesc('id')->first();
if (!$d) { echo "no disputes\n"; exit; }
$now = Carbon\Carbon::now();
$days = $now->diffInDays($d->updated_at, true);

echo "id={$d->id}\n";
echo "status={$d->status}\n";
echo "assigned_role={$d->assigned_role}\n";
echo "assigned_to={$d->assigned_to}\n";
echo "created_at={$d->created_at}\n";
echo "updated_at={$d->updated_at}\n";
echo "now={$now}\n";
echo "days={$days}\n";

echo "staff:\n";
foreach (App\Models\Staff::select('staff_id','role')->orderBy('staff_id')->get() as $s) {
  echo "{$s->staff_id} {$s->role}\n";
}
