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
use App\Services\NotificationService;
use App\Services\ActivityLogService;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

#[Signature('app:escalate-disputes')]
#[Description('Escalate stale disputes to HOD or Admin based on inactivity thresholds')]
class EscalateDisputes extends Command
{
    private const DEFAULT_HOD_DAYS = 3;
    private const DEFAULT_ADMIN_DAYS = 6;

    /**
     * Execute the console command.
     */


    public function handle()
    {
        $escalatedToHod = 0;
        $escalatedToAdmin = 0;

        /** @var EloquentCollection<int, Dispute> $disputes */
        $disputes = Dispute::whereIn('status', ['pending', 'under_review', 'escalated'])->get();
        $hodDays = $this->getIntSetting('escalation_days_hod', self::DEFAULT_HOD_DAYS);
        $adminDays = $this->getIntSetting('escalation_days_admin', self::DEFAULT_ADMIN_DAYS);
        $hasEscalationLevel = Schema::hasColumn('disputes', 'escalation_level');
        $hasEscalatedAt = Schema::hasColumn('disputes', 'escalated_at');
        $hasEscalationReason = Schema::hasColumn('disputes', 'escalation_reason');

        foreach ($disputes as $dispute) {
            /** @var Dispute $dispute */

            $days = Carbon::now()->startOfDay()->diffInDays(
                $dispute->updated_at->copy()->startOfDay(),
                true
            );

            // Escalate Lecturer -> HOD
            if (($dispute->status == 'pending' || $dispute->status == 'under_review') && $days >= $hodDays) {
                $previousStatus = $dispute->status;

                $hod = Staff::where('role', 'hod')->first();

                if ($hod) {
                    $dispute->assigned_to = $hod->staff_id;
                    $dispute->assigned_role = 'hod';
                    $dispute->status = 'escalated';
                    if ($hasEscalationLevel) {
                        $dispute->escalation_level = max(1, (int) $dispute->escalation_level + 1);
                    }
                    if ($hasEscalatedAt) {
                        $dispute->escalated_at = Carbon::now();
                    }
                    if ($hasEscalationReason) {
                        $dispute->escalation_reason = 'Escalated automatically after '.$days.' day(s) without progress.';
                    }
                    $dispute->save();
                    $escalatedToHod++;

                    DisputeLog::create([
                        'dispute_id' => $dispute->id,
                        'performed_by' => 'system',
                        'action' => 'Automatically escalated to HOD',
                        'old_status' => $previousStatus,
                        'new_status' => 'escalated',
                    ]);

                    // Notify HOD
                    NotificationService::notify(
                        $hod->staff_id,
                        'Dispute Escalated',
                        'A dispute has been escalated to you. Ticket: ' . $dispute->ticket_number,
                        '/staff/tickets/' . $dispute->id,
                        $dispute->id
                    );

                    // Activity log
                    ActivityLogService::log(
                        null,
                        'Dispute Escalated to HOD',
                        'dispute',
                        $dispute->id,
                        'System escalated dispute to HOD'
                    );
                }
            }

            // Escalate HOD -> Admin
            elseif ($dispute->status == 'escalated' && $dispute->assigned_role == 'hod' && $days >= $adminDays) {
                $previousStatus = $dispute->status;

                $admin = Staff::where('role', 'admin')->first();

                if ($admin) {
                    $dispute->assigned_to = $admin->staff_id;
                    $dispute->assigned_role = 'admin';
                    $dispute->status = 'escalated';
                    if ($hasEscalationLevel) {
                        $dispute->escalation_level = max(2, (int) $dispute->escalation_level + 1);
                    }
                    if ($hasEscalatedAt) {
                        $dispute->escalated_at = Carbon::now();
                    }
                    if ($hasEscalationReason) {
                        $dispute->escalation_reason = 'Escalated to admin automatically after '.$days.' day(s).';
                    }
                    $dispute->save();
                    $escalatedToAdmin++;

                    DisputeLog::create([
                        'dispute_id' => $dispute->id,
                        'performed_by' => 'system',
                        'action' => 'Automatically escalated to Admin',
                        'old_status' => $previousStatus,
                        'new_status' => 'escalated',
                    ]);

                    // Notify Admin
                    NotificationService::notify(
                        $admin->staff_id,
                        'Dispute Escalated to Admin',
                        'A dispute has been escalated to you. Ticket: ' . $dispute->ticket_number,
                        '/admin/tickets/' . $dispute->id,
                        $dispute->id
                    );

                    // Activity log
                    ActivityLogService::log(
                        null,
                        'Dispute Escalated to Admin',
                        'dispute',
                        $dispute->id,
                        'System escalated dispute to Admin'
                    );
                }
            }
        }

        $this->info("Escalation complete. To HOD: {$escalatedToHod}, To Admin: {$escalatedToAdmin}.");

        return 0;
    }

    private function getIntSetting(string $key, int $default): int
    {
        $rawValue = SystemSetting::where('setting_key', $key)->value('setting_value');

        if (! is_numeric($rawValue)) {
            return $default;
        }

        $value = (int) $rawValue;
        return $value > 0 ? $value : $default;
    }
}
