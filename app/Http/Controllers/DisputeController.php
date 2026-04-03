<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\UnitLecturer;
use App\Models\Dispute;
use App\Models\Staff;
use App\Models\DisputeLog;
use Illuminate\Support\Facades\DB;


class DisputeController extends Controller
{
    public function searchUnits(Request $request)
    {
        $query = $request->query('q');

        $units = Unit::where('unit_code', 'like', "%$query%")
            ->orWhere('unit_name', 'like', "%$query%")
            ->get();

        return response()->json($units);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'type' => 'required|in:unit,general',
            'title' => 'required',
            'description' => 'required'
        ]);

        // UNIT-BASED DISPUTE
        if ($request->type === 'unit') {

            $request->validate([
                'unit_code' => 'required',
                'course' => 'required'
            ]);

            // Find lecturer
            $mapping = UnitLecturer::where('unit_code', $request->unit_code)
                ->where('course', $request->course)
                ->first();

            if (!$mapping) {
                return response()->json(['error' => 'No lecturer assigned to this unit'], 404);
            }

            $assigned_to = $mapping->lecturer_id;
            $assigned_role = 'lecturer';
        }

    // GENERAL DISPUTE
    else {

            $request->validate([
                'assigned_role' => 'required'
            ]);

            $staff = Staff::where('role', $request->assigned_role)->first();

            if (!$staff) {
                return response()->json(['error' => 'Staff not found'], 404);
            }

            $assigned_to = $staff->staff_id;
        }

        // Create dispute
        $dispute = Dispute::create([
            'student_id' => $request->student_id,
            'type' => $request->type,
            'unit_code' => $request->unit_code ?? null,
            'assigned_to' => $assigned_to,
            'assigned_role' => $assigned_role ?? $request->assigned_role,
            'title' => $request->title,
            'description' => $request->description
        ]);

        return response()->json([
            'message' => 'Dispute submitted successfully',
            'data' => $dispute
        ]);
    }
    public function lecturerDisputes($lecturer_id)
    {
        $disputes = Dispute::where('assigned_to', $lecturer_id)
            ->where('assigned_role', 'lecturer')
            ->get();

        return response()->json($disputes);
    }
    public function updateStatus(Request $request)
    {
        $request->validate([
            'dispute_id' => 'required',
            'status' => 'required'
        ]);

        $dispute = Dispute::find($request->dispute_id);

        if (!$dispute) {
            return response()->json(['error' => 'Dispute not found'], 404);
        }

        $dispute->status = $request->status;
        $dispute->save();

        return response()->json([
            'message' => 'Status updated',
            'data' => $dispute
        ]);
    }

    public function respond(Request $request)
    {
        $request->validate([
            'dispute_id' => 'required',
            'performed_by' => 'required',
            'action' => 'required'
        ]);

        $log = DisputeLog::create([
            'dispute_id' => $request->dispute_id,
            'performed_by' => $request->performed_by,
            'action' => $request->action
        ]);

        return response()->json([
            'message' => 'Response added',
            'data' => $log
        ]);
    }
    public function studentDisputes($student_id)
    {
        $disputes = Dispute::where('student_id', $student_id)->get();

        return response()->json($disputes);
    }
    public function disputeLogs($dispute_id)
    {
        $logs = DisputeLog::where('dispute_id', $dispute_id)->get();

        return response()->json($logs);
    }
    public function adminDashboard()
    {
        return response()->json([
            'total_disputes' => Dispute::count(),
            'pending' => Dispute::where('status', 'pending')->count(),
            'under_review' => Dispute::where('status', 'under_review')->count(),
            'resolved' => Dispute::where('status', 'resolved')->count(),
            'escalated' => Dispute::where('status', 'escalated')->count(),
        ]);
    }
    public function allDisputes()
    {
        return Dispute::orderBy('created_at', 'desc')->get();
    }
    public function escalatedDisputes()
    {
        return Dispute::where('status', 'escalated')
            ->orWhere('status', 'escalated_admin')
            ->get();
    }
    public function disputesPerUnit()
    {
        $data = DB::table('disputes')
            ->leftJoin('units', 'disputes.unit_code', '=', 'units.unit_code')
            ->select(
                'disputes.unit_code',
                'units.unit_name',
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('disputes.unit_code')
            ->groupBy('disputes.unit_code', 'units.unit_name')
            ->orderByDesc('total')
            ->get();

        return response()->json($data);
    }
    public function disputesPerLecturer()
    {
        $data = DB::table('disputes')
            ->select('assigned_to', DB::raw('COUNT(*) as total'))
            ->where('assigned_role', 'lecturer')
            ->groupBy('assigned_to')
            ->get();

        return response()->json($data);
    }
    public function monthlyDisputes()
    {
        $data = DB::table('disputes')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }
    public function resolutionTime()
    {
        $data = DB::table('disputes')
            ->where('status', 'resolved')
            ->select(DB::raw('AVG(DATEDIFF(updated_at, created_at)) as avg_days'))
            ->first();

        return response()->json($data);
    }
    

}
