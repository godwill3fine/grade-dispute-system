<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\UnitLecturer;
use App\Models\Dispute;
use App\Models\Staff;


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
    
}
