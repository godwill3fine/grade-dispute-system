<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SystemSettingController;
use App\Models\Student;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/identify', [AuthController::class, 'identify']);
Route::post('/set-password', [AuthController::class, 'setPassword']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/units/search', [DisputeController::class, 'searchUnits']);

Route::middleware(['role:student'])->group(function () {
    Route::post('/disputes', [DisputeController::class, 'store']);
    Route::post('/dispute/store', [DisputeController::class, 'store']);
    Route::get('/student/disputes/{student_id}', [DisputeController::class, 'studentDisputes']);
});

Route::middleware(['role:lecturer,admin'])->group(function () {
    Route::get('/lecturer/disputes/{lecturer_id}', [DisputeController::class, 'lecturerDisputes']);
    Route::post('/dispute/respond', [DisputeController::class, 'respond']);
    Route::post('/dispute/status', [DisputeController::class, 'updateStatus']);
    Route::post('/dispute/update-status', [DisputeController::class, 'updateStatus']);
    Route::get('/lecturer/alerts/{lecturer_id}', [DisputeController::class, 'lecturerAlerts']);
});

Route::middleware(['role:student,lecturer,admin'])->group(function () {
    Route::get('/dispute/logs/{dispute_id}', [DisputeController::class, 'disputeLogs']);
    Route::get('/dispute/{id}/timeline', [DisputeController::class, 'timeline']);
    Route::get('/dispute/attachments/{id}', [DisputeController::class, 'disputeAttachments']);
    Route::get('/attachment/download/{id}', [DisputeController::class, 'downloadAttachment']);

    Route::get('/notifications/{userId}', [NotificationController::class, 'userNotifications']);
    Route::post('/notifications/read/{id}', [NotificationController::class, 'markAsRead']);
});

Route::middleware(['role:admin'])->group(function () {
    Route::get('/admin/dashboard', [DisputeController::class, 'adminDashboard']);
    Route::get('/admin/disputes', [DisputeController::class, 'allDisputes']);
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
