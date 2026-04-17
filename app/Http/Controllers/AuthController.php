<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetCodeMail;
use App\Models\Student;
use App\Models\Lecturer;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    private const PASSWORD_RESET_SESSION_KEY = 'password_reset_flow';
    private const PASSWORD_RESET_CODE_TTL_MINUTES = 10;
    private const PASSWORD_RESET_VERIFICATION_TTL_MINUTES = 15;

    private function resolveUserProfile(string $identifier, string $role): ?array
    {
        if ($role === 'student') {
            $student = Student::find($identifier);
            if (!$student) {
                return null;
            }

            return [
                'name' => $student->display_name,
                'email' => strtolower($student->reg_number).'@grade-dispute.local',
            ];
        }

        if ($role === 'lecturer') {
            $lecturer = Lecturer::find($identifier);
            if (!$lecturer) {
                return null;
            }

            return [
                'name' => $lecturer->display_name,
                'email' => strtolower($lecturer->lecturer_id).'@grade-dispute.local',
            ];
        }

        if ($role === 'staff') {
            $staff = Staff::where('staff_id', $identifier)->first();
            if (!$staff) {
                return null;
            }

            return [
                'name' => $staff->display_name,
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

        $identifier = trim((string) $request->identifier);
        $user = User::where('identifier', $identifier)->first();

        if ($user && in_array($user->role, ['student', 'lecturer', 'staff', 'admin'], true)) {
            return response()->json([
                'status' => 'existing_user',
                'role' => $user->role,
            ]);
        }

        if (Staff::where('staff_id', $identifier)->exists()) {
            $role = 'staff';
        } elseif (Lecturer::find($identifier)) {
            $role = 'lecturer';
        } elseif (Student::find($identifier)) {
            $role = 'student';
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }

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

        $status = $this->normalizeAccountStatus($user->status);

        if ($status === 'pending invite') {
            return response()->json([
                'error' => 'This account is waiting for administrative approval before sign-in is enabled.',
            ], 403);
        }

        if ($status === 'inactive') {
            return response()->json([
                'error' => 'This account is inactive. Contact an administrator for help.',
            ], 403);
        }

        if ($user->require_password_reset) {
            return response()->json([
                'error' => 'Reset your password before signing in to this account.',
                'next' => route('portal.password.forgot', ['identifier' => $user->identifier]),
            ], 409);
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

    public function forgotPasswordIdentify(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = trim((string) $request->identifier);
        $user = User::where('identifier', $identifier)->first();

        if (! $user) {
            $this->clearPasswordResetFlow($request);

            return response()->json([
                'error' => 'We could not find that University ID in the system. Redirecting you to sign up.',
                'signup_url' => route('portal.signup'),
            ], 404);
        }

        $this->storePasswordResetFlow($request, [
            'identifier' => $user->identifier,
            'email' => strtolower(trim((string) $user->email)),
            'code_hash' => null,
            'code_expires_at' => null,
            'verified_at' => null,
        ]);

        return response()->json([
            'message' => 'University ID confirmed.',
            'next' => route('portal.password.forgot.email'),
        ]);
    }

    public function forgotPasswordSendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $flow = $this->passwordResetFlow($request);
        $identifier = trim((string) ($flow['identifier'] ?? ''));

        if ($identifier === '') {
            return response()->json([
                'error' => 'Start by entering your University ID first.',
            ], 409);
        }

        $user = User::where('identifier', $identifier)->first();
        if (! $user) {
            $this->clearPasswordResetFlow($request);

            return response()->json([
                'error' => 'That account is no longer available. Start again from the beginning.',
            ], 404);
        }

        $enteredEmail = strtolower(trim((string) $request->email));
        $storedEmail = strtolower(trim((string) $user->email));

        if ($enteredEmail !== $storedEmail) {
            return response()->json([
                'error' => 'That email address does not match our records for this account.',
            ], 422);
        }

        $code = (string) random_int(100000, 999999);

        try {
            Mail::to($user->email)->send(
                new PasswordResetCodeMail(
                    (string) $user->name,
                    $code,
                    self::PASSWORD_RESET_CODE_TTL_MINUTES
                )
            );
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => 'Unable to send the verification code right now. Please try again shortly.',
            ], 500);
        }

        $this->storePasswordResetFlow($request, [
            'identifier' => $user->identifier,
            'email' => $storedEmail,
            'code_hash' => Hash::make($code),
            'code_expires_at' => now()->addMinutes(self::PASSWORD_RESET_CODE_TTL_MINUTES)->toIso8601String(),
            'verified_at' => null,
        ]);

        return response()->json([
            'message' => 'Verification code sent.',
            'next' => route('portal.password.forgot.verify'),
        ]);
    }

    public function forgotPasswordVerifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'regex:/^\d{6}$/'],
        ]);

        $flow = $this->passwordResetFlow($request);
        $identifier = trim((string) ($flow['identifier'] ?? ''));
        $codeHash = (string) ($flow['code_hash'] ?? '');
        $expiresAt = (string) ($flow['code_expires_at'] ?? '');

        if ($identifier === '' || $codeHash === '' || $expiresAt === '') {
            return response()->json([
                'error' => 'Request a new verification code to continue.',
            ], 409);
        }

        if (now()->greaterThan(\Illuminate\Support\Carbon::parse($expiresAt))) {
            $this->storePasswordResetFlow($request, [
                'code_hash' => null,
                'code_expires_at' => null,
                'verified_at' => null,
            ]);

            return response()->json([
                'error' => 'That verification code has expired. Request a new code and try again.',
            ], 422);
        }

        if (! Hash::check((string) $request->code, $codeHash)) {
            return response()->json([
                'error' => 'The verification code you entered is invalid.',
            ], 422);
        }

        $this->storePasswordResetFlow($request, [
            'code_hash' => null,
            'code_expires_at' => null,
            'verified_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'message' => 'Verification successful.',
            'next' => route('portal.password.forgot.reset'),
        ]);
    }

    public function forgotPasswordReset(Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $flow = $this->passwordResetFlow($request);
        $identifier = trim((string) ($flow['identifier'] ?? ''));
        $verifiedAt = (string) ($flow['verified_at'] ?? '');

        if ($identifier === '' || $verifiedAt === '') {
            return response()->json([
                'error' => 'Verify your reset code before setting a new password.',
            ], 409);
        }

        if (now()->greaterThan(\Illuminate\Support\Carbon::parse($verifiedAt)->addMinutes(self::PASSWORD_RESET_VERIFICATION_TTL_MINUTES))) {
            $this->clearPasswordResetFlow($request);

            return response()->json([
                'error' => 'Your reset session expired. Start the reset process again.',
            ], 422);
        }

        $user = User::where('identifier', $identifier)->first();
        if (! $user) {
            $this->clearPasswordResetFlow($request);

            return response()->json([
                'error' => 'That account is no longer available. Start again from the beginning.',
            ], 404);
        }

        $user->password = Hash::make((string) $request->password);
        $user->require_password_reset = false;
        $user->save();

        $this->clearPasswordResetFlow($request);

        return response()->json([
            'message' => 'Password reset successful',
            'identifier' => $user->identifier,
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

    private function passwordResetFlow(Request $request): array
    {
        $flow = $request->session()->get(self::PASSWORD_RESET_SESSION_KEY, []);

        return is_array($flow) ? $flow : [];
    }

    private function storePasswordResetFlow(Request $request, array $data): void
    {
        $request->session()->put(
            self::PASSWORD_RESET_SESSION_KEY,
            array_merge($this->passwordResetFlow($request), $data)
        );
    }

    private function clearPasswordResetFlow(Request $request): void
    {
        $request->session()->forget(self::PASSWORD_RESET_SESSION_KEY);
    }

    private function normalizeAccountStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return match ($normalized) {
            'inactive', 'disabled', 'suspended' => 'inactive',
            'pending', 'pending invite' => 'pending invite',
            default => 'active',
        };
    }
}
