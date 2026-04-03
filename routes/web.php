<?php

use Illuminate\Support\Facades\Route;
use App\Models\Student;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\NotificationController;

Route::get('/', function () {
    return view('welcome');
});
// Connection test
// use Illuminate\Support\Facades\DB;

// Route::get('/test-db', function () {
//     try {
//         DB::connection()->getPdo();
//         return "Database connected successfully!";
//     } catch (\Exception $e) {
//         return "Database connection failed: " . $e->getMessage();
//     }
// });
Route::post('/identify', [AuthController::class, 'identify']);
Route::post('/set-password', [AuthController::class, 'setPassword']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/units/search', [DisputeController::class, 'searchUnits']);
Route::post('/disputes', [DisputeController::class, 'store']);
Route::get('/lecturer/disputes/{lecturer_id}', [DisputeController::class, 'lecturerDisputes']);
Route::post('/dispute/update-status', [DisputeController::class, 'updateStatus']);
Route::post('/dispute/respond', [DisputeController::class, 'respond']);
Route::get('/student/disputes/{student_id}', [DisputeController::class, 'studentDisputes']);
Route::get('/dispute/logs/{dispute_id}', [DisputeController::class, 'disputeLogs']);
Route::get('/admin/dashboard', [DisputeController::class, 'adminDashboard']);
Route::get('/admin/disputes', [DisputeController::class, 'allDisputes']);
Route::get('/admin/disputes/escalated', [DisputeController::class, 'escalatedDisputes']);
Route::get('/admin/reports/units', [DisputeController::class, 'disputesPerUnit']);
Route::get('/admin/reports/lecturers', [DisputeController::class, 'disputesPerLecturer']);
Route::get('/admin/reports/monthly', [DisputeController::class, 'monthlyDisputes']);
Route::get('/admin/reports/resolution-time', [DisputeController::class, 'resolutionTime']);
Route::get('/dispute/{id}/timeline', [DisputeController::class, 'timeline']);
Route::get('/notifications/{userId}', [NotificationController::class, 'userNotifications']);
Route::post('/notifications/read/{id}', [NotificationController::class, 'markAsRead']);


Route::get('/test-model', function () {
    return Student::all();
});
