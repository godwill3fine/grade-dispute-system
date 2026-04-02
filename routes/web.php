<?php

use Illuminate\Support\Facades\Route;
use App\Models\Student;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DisputeController;

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


Route::get('/test-model', function () {
    return Student::all();
});
