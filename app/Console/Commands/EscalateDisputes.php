<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Models\Dispute;
use App\Models\Staff;
use App\Models\DisputeLog;
use Carbon\Carbon;

#[Signature('app:escalate-disputes')]
#[Description('Escalate stale disputes to HOD or Admin based on inactivity thresholds')]
class EscalateDisputes extends Command
{
    /**
     * Execute the console command.
     */


    public function handle()
    {
        $escalatedToHod = 0;
        $escalatedToAdmin = 0;

        /** @var EloquentCollection<int, Dispute> $disputes */
        $disputes = Dispute::whereIn('status', ['pending', 'under_review', 'escalated'])->get();

        foreach ($disputes as $dispute) {
            /** @var Dispute $dispute */

            $days = Carbon::now()->startOfDay()->diffInDays(
                $dispute->updated_at->copy()->startOfDay(),
                true
            );

            // Escalate Lecturer -> HOD
            if (($dispute->status == 'pending' || $dispute->status == 'under_review') && $days >= 3) {

                $hod = Staff::where('role', 'hod')->first();

                if ($hod) {
                    $dispute->assigned_to = $hod->staff_id;
                    $dispute->assigned_role = 'hod';
                    $dispute->status = 'escalated';
                    $dispute->save();
                    $escalatedToHod++;

                    DisputeLog::create([
                        'dispute_id' => $dispute->id,
                        'performed_by' => 'system',
                        'action' => 'Automatically escalated to HOD'
                    ]);
                }
            }

            // Escalate HOD -> Admin
            elseif ($dispute->status == 'escalated' && $dispute->assigned_role == 'hod' && $days >= 6) {

                $admin = Staff::where('role', 'admin')->first();

                if ($admin) {
                    $dispute->assigned_to = $admin->staff_id;
                    $dispute->assigned_role = 'admin';
                    $dispute->status = 'escalated';
                    $dispute->save();
                    $escalatedToAdmin++;

                    DisputeLog::create([
                        'dispute_id' => $dispute->id,
                        'performed_by' => 'system',
                        'action' => 'Automatically escalated to Admin'
                    ]);
                }
            }
        }

        $this->info("Escalation complete. To HOD: {$escalatedToHod}, To Admin: {$escalatedToAdmin}.");

        return 0;
    }
}

