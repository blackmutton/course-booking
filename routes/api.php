<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Backend\TeacherController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/verify', [RegisterController::class, 'verify']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'forget']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // 後台
    Route::middleware('can:access-admin-panel')->prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Welcome to the admin dashboard!']);
        });

        Route::resource('teachers', TeacherController::class);
    });
});