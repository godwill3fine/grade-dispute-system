<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function landing(): RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return redirect()->route('portal.auth');
    }

    public function authPage(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.entry');
    }

    public function signupPage(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.signup');
    }

    public function signupPasswordPage(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        $identifier = trim((string) $request->query('identifier', ''));
        $role = trim((string) $request->query('role', ''));

        if ($identifier === '' || $role === '') {
            return redirect()
                ->route('portal.signup')
                ->with('auth_error', 'Start sign up by entering your identifier first.');
        }

        return view('auth.signup-password', [
            'identifier' => $identifier,
            'role' => $role,
        ]);
    }

    public function forgotPasswordPage(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        request()->session()->forget('password_reset_flow');

        return view('auth.forgot-password');
    }

    public function forgotPasswordEmailPage(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        $flow = $request->session()->get('password_reset_flow', []);
        $identifier = trim((string) ($flow['identifier'] ?? ''));

        if ($identifier === '') {
            return redirect()
                ->route('portal.password.forgot')
                ->with('auth_error', 'Start by entering your University ID first.');
        }

        return view('auth.forgot-password-email', [
            'identifier' => $identifier,
        ]);
    }

    public function forgotPasswordVerifyPage(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        $flow = $request->session()->get('password_reset_flow', []);
        $identifier = trim((string) ($flow['identifier'] ?? ''));
        $email = trim((string) ($flow['email'] ?? ''));

        if ($identifier === '' || $email === '') {
            return redirect()
                ->route('portal.password.forgot')
                ->with('auth_error', 'Start the reset process again with your University ID.');
        }

        return view('auth.forgot-password-verify', [
            'identifier' => $identifier,
            'maskedEmail' => $this->maskEmail($email),
        ]);
    }

    public function forgotPasswordResetPage(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        $flow = $request->session()->get('password_reset_flow', []);
        $identifier = trim((string) ($flow['identifier'] ?? ''));
        $verifiedAt = trim((string) ($flow['verified_at'] ?? ''));

        if ($identifier === '' || $verifiedAt === '') {
            return redirect()
                ->route('portal.password.forgot')
                ->with('auth_error', 'Verify your reset code before setting a new password.');
        }

        return view('auth.forgot-password-reset', [
            'identifier' => $identifier,
        ]);
    }

    public function appHome(): RedirectResponse
    {
        return $this->redirectByRole(Auth::user()->role);
    }

    public function studentDashboard(): View
    {
        $this->ensureRole('student');

        return view('student.dashboard');
    }

    public function studentSubmitDispute(): View
    {
        $this->ensureRole('student');

        return view('student.submit-dispute');
    }

    public function studentTickets(): View
    {
        $this->ensureRole('student');

        return view('student.tickets');
    }

    public function studentNotifications(): View
    {
        $this->ensureRole('student');

        return view('student.notifications');
    }

    public function studentDisputes(): View
    {
        return $this->studentTickets();
    }

    public function studentDisputeDetail(int $id): View
    {
        $this->ensureRole('student');

        return view('student.dispute-detail', [
            'disputeId' => $id,
        ]);
    }

    public function lecturerDashboard(): View
    {
        $this->ensureRole('lecturer');

        return view('lecturer.dashboard');
    }

    public function lecturerDisputes(): View
    {
        $this->ensureRole('lecturer');

        return view('lecturer.disputes');
    }

    public function lecturerHistory(): View
    {
        $this->ensureRole('lecturer');

        return view('lecturer.history');
    }

    public function lecturerDisputeDetail(int $id): View
    {
        $this->ensureRole('lecturer');

        return view('lecturer.dispute-detail', [
            'disputeId' => $id,
        ]);
    }

    public function adminDashboard(): View
    {
        $this->ensureRole('admin');

        return view('admin.dashboard');
    }

    public function adminDisputes(): View
    {
        $this->ensureRole('admin');

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $units = Unit::query()
            ->with('department:id,name')
            ->orderBy('unit_code')
            ->get(['unit_code', 'unit_name', 'department_id']);

        return view('admin.disputes', [
            'adminDisputeMeta' => [
                'departments' => $departments
                    ->map(fn (Department $department) => [
                        'id' => $department->id,
                        'name' => $department->name,
                    ])
                    ->values(),
                'units' => $units
                    ->map(fn (Unit $unit) => [
                        'unit_code' => $unit->unit_code,
                        'unit_name' => $unit->unit_name,
                        'department_name' => $unit->department?->name,
                    ])
                    ->values(),
            ],
        ]);
    }

    public function adminUsers(): View
    {
        $this->ensureRole('admin');

        return view('admin.users');
    }

    public function adminReports(): View
    {
        $this->ensureRole('admin');

        return view('admin.reports');
    }

    public function adminEscalations(): View
    {
        $this->ensureRole('admin');

        return view('admin.escalations');
    }

    public function adminSettings(): View
    {
        $this->ensureRole('admin');

        return view('admin.settings');
    }

    public function adminActivity(): View
    {
        $this->ensureRole('admin');

        return view('admin.activity');
    }

    private function ensureRole(string ...$roles): void
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role, $roles, true)) {
            abort(403);
        }
    }

    private function redirectByRole(string $role): RedirectResponse
    {
        if ($role === 'student') {
            return redirect()->route('portal.student.dashboard');
        }

        if ($role === 'lecturer') {
            return redirect()->route('portal.lecturer.dashboard');
        }

        if ($role === 'admin') {
            return redirect()->route('portal.admin.dashboard');
        }

        Auth::logout();

        return redirect()
            ->route('portal.auth')
            ->with('auth_error', 'Your role is not yet supported in this web interface.');
    }

    private function maskEmail(string $email): string
    {
        $normalized = trim($email);
        if ($normalized === '' || ! str_contains($normalized, '@')) {
            return $normalized;
        }

        [$local, $domain] = explode('@', $normalized, 2);
        $localLength = strlen($local);

        if ($localLength <= 2) {
            $maskedLocal = substr($local, 0, 1).str_repeat('*', max($localLength - 1, 1));
        } else {
            $maskedLocal = substr($local, 0, 1).str_repeat('*', max($localLength - 2, 1)).substr($local, -1);
        }

        return $maskedLocal.'@'.$domain;
    }
}
