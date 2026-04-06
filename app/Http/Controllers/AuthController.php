<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Lecturer;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    private function resolveUserProfile(string $identifier, string $role): ?array
    {
        if ($role === 'student') {
            $student = Student::find($identifier);
            if (!$student) {
                return null;
            }

            return [
                'name' => $student->full_name,
                'email' => strtolower($student->reg_number).'@grade-dispute.local',
            ];
        }

        if ($role === 'lecturer') {
            $lecturer = Lecturer::find($identifier);
            if (!$lecturer) {
                return null;
            }

            return [
                'name' => $lecturer->full_name,
                'email' => strtolower($lecturer->lecturer_id).'@grade-dispute.local',
            ];
        }

        if ($role === 'staff') {
            $staff = Staff::where('staff_id', $identifier)->first();
            if (!$staff) {
                return null;
            }

            return [
                'name' => $staff->full_name,
                'email' => strtolower($staff->staff_id).'@grade-dispute.local',
            ];
        }

        return null;
    }

    //Identify user
    public function identify(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string'
        ]);

        $identifier = $request->identifier;

        // Check student
        if ($student = Student::find($identifier)) {
            $role = 'student';
        }
        // Check lecturer
        elseif ($lecturer = Lecturer::find($identifier)) {
            $role = 'lecturer';
        }
        // Check staff
        elseif ($staff = Staff::where('staff_id', $identifier)->first()) {
            $role = 'staff';
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if account exists
        $user = User::where('identifier', $identifier)->first();

        if (!$user) {
            return response()->json([
                'status' => 'first_time',
                'role' => $role
            ]);
        }

        return response()->json([
            'status' => 'existing_user',
            'role' => $role
        ]);
    }

    //First-Time Password Setup
    public function setPassword(Request $request)
    {
        $request->validate([
            'identifier' => 'required',
            'role' => 'required|in:student,lecturer,staff,admin',
            'password' => 'required|min:6|confirmed'
        ]);

        $exists = User::where('identifier', $request->identifier)->exists();

        if ($exists) {
            return response()->json(['error' => 'Account already exists'], 400);
        }

        $profile = $this->resolveUserProfile($request->identifier, $request->role);
        if (!$profile && $request->role !== 'admin') {
            return response()->json(['error' => 'User profile not found'], 404);
        }

        User::create([
            'identifier' => $request->identifier,
            'role' => $request->role,
            'name' => $profile['name'] ?? ('Admin '.$request->identifier),
            'email' => $profile['email'] ?? strtolower($request->identifier).'@grade-dispute.local',
            'password' => Hash::make($request->password)
        ]);

        return response()->json(['message' => 'Account created successfully']);
    }

    //Login(Returning User)
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required',
            'password' => 'required'
        ]);
    
        $user = User::where('identifier', $request->identifier)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        auth()->login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login' => now()])->save();
    
        return response()->json([
            'message' => 'Login successful',
            'role' => $user->role,
            'identifier' => $user->identifier,
        ]);
    }

    // Forgot Password / Reset Password
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|exists:users,identifier',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::where('identifier', $request->identifier)->first();
        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password reset successful',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (! $request->expectsJson()) {
            return redirect()->route('portal.auth');
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
