<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SystemSettingController;
use App\Models\Student;
use Illuminate\Support\Facades\Route;

// Public entry points for the landing page and authentication screens.
Route::get('/', [FrontendController::class, 'landing'])->name('portal.landing');
Route::get('/portal/auth', [FrontendController::class, 'authPage'])->name('portal.auth');
Route::get('/portal/signup', [FrontendController::class, 'signupPage'])->name('portal.signup');
Route::get('/portal/signup/password', [FrontendController::class, 'signupPasswordPage'])->name('portal.signup.password');
Route::get('/portal/password/forgot', [FrontendController::class, 'forgotPasswordPage'])->name('portal.password.forgot');
Route::get('/portal/password/forgot/email', [FrontendController::class, 'forgotPasswordEmailPage'])->name('portal.password.forgot.email');
Route::get('/portal/password/forgot/verify', [FrontendController::class, 'forgotPasswordVerifyPage'])->name('portal.password.forgot.verify');
Route::get('/portal/password/forgot/reset', [FrontendController::class, 'forgotPasswordResetPage'])->name('portal.password.forgot.reset');
Route::get('/login', fn () => redirect()->route('portal.auth'))->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('portal.logout');

// Authenticated portal pages are rendered server-side and hydrated by page-specific JavaScript.
Route::middleware('auth')->prefix('portal')->group(function () {
    Route::get('/app', [FrontendController::class, 'appHome'])->name('portal.app');

    Route::get('/student/dashboard', [FrontendController::class, 'studentDashboard'])->name('portal.student.dashboard');
    Route::get('/student/submit-dispute', [FrontendController::class, 'studentSubmitDispute'])->name('portal.student.submit');
    Route::get('/student/tickets', [FrontendController::class, 'studentTickets'])->name('portal.student.tickets');
    Route::get('/student/notifications', [FrontendController::class, 'studentNotifications'])->name('portal.student.notifications');
    Route::get('/student/profile', [FrontendController::class, 'studentProfile'])->name('portal.student.profile');
    Route::get('/student/settings', [FrontendController::class, 'studentSettings'])->name('portal.student.settings');
    Route::get('/student/disputes', [FrontendController::class, 'studentDisputes'])->name('portal.student.disputes');
    Route::get('/student/disputes/{id}', [FrontendController::class, 'studentDisputeDetail'])->name('portal.student.dispute.detail');

    Route::get('/lecturer/dashboard', [FrontendController::class, 'lecturerDashboard'])->name('portal.lecturer.dashboard');
    Route::get('/lecturer/disputes', [FrontendController::class, 'lecturerDisputes'])->name('portal.lecturer.disputes');
    Route::get('/lecturer/history', [FrontendController::class, 'lecturerHistory'])->name('portal.lecturer.history');
    Route::get('/lecturer/disputes/{id}', [FrontendController::class, 'lecturerDisputeDetail'])->name('portal.lecturer.dispute.detail');

    Route::get('/admin/dashboard', [FrontendController::class, 'adminDashboard'])->name('portal.admin.dashboard');
    Route::get('/admin/disputes', [FrontendController::class, 'adminDisputes'])->name('portal.admin.disputes');
    Route::get('/admin/disputes/create', [FrontendController::class, 'adminCreateTicket'])->name('portal.admin.disputes.create');
    Route::get('/admin/disputes/{id}', [FrontendController::class, 'adminManageTicket'])->name('portal.admin.disputes.manage');
    Route::get('/admin/users', [FrontendController::class, 'adminUsers'])->name('portal.admin.users');
    Route::get('/admin/users/create', [FrontendController::class, 'adminUserCreate'])->name('portal.admin.users.create');
    Route::get('/admin/users/{identifier}/edit', [FrontendController::class, 'adminUserEdit'])
        ->where('identifier', '.*')
        ->name('portal.admin.users.edit');
    Route::get('/admin/escalations', [FrontendController::class, 'adminEscalations'])->name('portal.admin.escalations');
    Route::get('/admin/reports', [FrontendController::class, 'adminReports'])->name('portal.admin.reports');
    Route::get('/admin/settings', [FrontendController::class, 'adminSettings'])->name('portal.admin.settings');
    Route::get('/admin/activity', [FrontendController::class, 'adminActivity'])->name('portal.admin.activity');
});

// JSON authentication endpoints used by the login and password reset forms.
Route::post('/identify', [AuthController::class, 'identify']);
Route::post('/set-password', [AuthController::class, 'setPassword']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPasswordReset']);
Route::post('/forgot-password/identify', [AuthController::class, 'forgotPasswordIdentify']);
Route::post('/forgot-password/email', [AuthController::class, 'forgotPasswordSendCode']);
Route::post('/forgot-password/verify', [AuthController::class, 'forgotPasswordVerifyCode']);
Route::post('/forgot-password/reset', [AuthController::class, 'forgotPasswordReset']);

Route::get('/units/search', [DisputeController::class, 'searchUnits']);

// Student-only dispute endpoints cover submission and the student's own queue.
Route::middleware(['role:student'])->group(function () {
    Route::post('/disputes', [DisputeController::class, 'store']);
    Route::post('/dispute/store', [DisputeController::class, 'store']);
    Route::get('/student/disputes/{student_id}', [DisputeController::class, 'studentDisputes'])
        ->where('student_id', '.*');
});

// Lecturer, staff, and admin reviewers share the response and status update APIs.
Route::middleware(['role:lecturer,staff,admin'])->group(function () {
    Route::get('/lecturer/disputes/{lecturer_id}', [DisputeController::class, 'lecturerDisputes'])
        ->where('lecturer_id', '.*');
    Route::post('/dispute/respond', [DisputeController::class, 'respond']);
    Route::post('/dispute/status', [DisputeController::class, 'updateStatus']);
    Route::post('/dispute/update-status', [DisputeController::class, 'updateStatus']);
    Route::get('/lecturer/alerts/{lecturer_id}', [DisputeController::class, 'lecturerAlerts'])
        ->where('lecturer_id', '.*');
});

// Shared dispute utilities are available to every signed-in portal role.
Route::middleware(['role:student,lecturer,staff,admin'])->group(function () {
    Route::get('/dispute/logs/{dispute_id}', [DisputeController::class, 'disputeLogs']);
    Route::get('/dispute/{id}/timeline', [DisputeController::class, 'timeline']);
    Route::get('/dispute/attachments/{id}', [DisputeController::class, 'disputeAttachments']);
    Route::get('/attachment/download/{id}', [DisputeController::class, 'downloadAttachment']);

    Route::post('/notifications/read/{id}', [NotificationController::class, 'markAsRead']);
    Route::get('/notifications/{userId}', [NotificationController::class, 'userNotifications'])
        ->where('userId', '.*');
});

// Admin APIs manage users, reports, settings, and higher-level dispute oversight.
Route::middleware(['role:admin'])->group(function () {
    Route::get('/admin/dashboard', [DisputeController::class, 'adminDashboard']);
    Route::get('/admin/disputes', [DisputeController::class, 'allDisputes']);
    Route::post('/admin/disputes', [DisputeController::class, 'adminStoreTicket']);
    Route::post('/admin/disputes/{id}/manage', [DisputeController::class, 'adminUpdateTicket']);
    Route::get('/admin/students/lookup', [DisputeController::class, 'adminLookupStudent']);
    Route::get('/admin/units/routing', [DisputeController::class, 'adminUnitRouting']);
    Route::get('/admin/users', [AdminController::class, 'userDirectory']);
    Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::post('/admin/users/import', [AdminController::class, 'importUsers']);
    Route::post('/admin/users/{identifier}/actions', [AdminController::class, 'userAction'])
        ->where('identifier', '.*')
        ->name('admin.users.actions');
    Route::post('/admin/users/{identifier}', [AdminController::class, 'updateUser'])
        ->where('identifier', '.*')
        ->name('admin.users.update');
    Route::get('/admin/disputes/escalated', [DisputeController::class, 'escalatedDisputes']);
    Route::get('/admin/escalated', [DisputeController::class, 'escalatedDisputes']);
    Route::get('/admin/disputes/filter', [DisputeController::class, 'filterDisputes']);
    Route::get('/admin/alerts', [DisputeController::class, 'alerts']);

    Route::get('/admin/reports/units', [DisputeController::class, 'disputesPerUnit']);
    Route::get('/admin/reports/lecturers', [DisputeController::class, 'disputesPerLecturer']);
    Route::get('/admin/reports/monthly', [DisputeController::class, 'monthlyDisputes']);
    Route::get('/admin/reports/resolution-time', [DisputeController::class, 'resolutionTime']);

    Route::get('/admin/charts/disputes-per-month', [DisputeController::class, 'disputesPerMonth']);
    Route::get('/admin/charts/disputes-by-status', [DisputeController::class, 'disputesByStatus']);
    Route::get('/admin/charts/disputes-by-unit', [DisputeController::class, 'disputesByUnit']);
    Route::get('/admin/charts/disputes-by-lecturer', [DisputeController::class, 'disputesByLecturer']);
    Route::get('/admin/charts/avg-resolution-time', [DisputeController::class, 'averageResolutionTime']);

    Route::get('/admin/settings', [SystemSettingController::class, 'index']);
    Route::post('/admin/settings/update', [SystemSettingController::class, 'update']);

    Route::get('/admin/activity', [AdminController::class, 'recentActivity']);
    Route::get('/test-model', function () {
        return Student::all();
    });
});
