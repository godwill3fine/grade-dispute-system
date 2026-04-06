<?php

namespace App\Http\Controllers;

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

        return view('auth.forgot-password');
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

        return view('admin.disputes');
    }

    public function adminReports(): View
    {
        $this->ensureRole('admin');

        return view('admin.reports');
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
}
