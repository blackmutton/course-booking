<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Backend\TeacherController;
use App\Http\Controllers\CourseRegistrationController;
use App\Http\Controllers\Frontend\CourseController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/verify/{token}', [RegisterController::class, 'verifySigned'])
    ->middleware('signed')
    ->name('api.verify.signed');
Route::get('/course-registrations/{courseRegistration}/confirm', [CourseRegistrationController::class, 'confirm'])
    ->middleware('signed')
    ->name('api.course-registrations.confirm');
Route::apiResource('/courses', CourseController::class)->only(['index', 'show']);
Route::post('/courses/{course}/enroll', [CourseController::class, 'register'])->name('courses.enroll');
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
            return response()->json(['message' => 'Success']);
        });

        Route::resource('teachers', TeacherController::class);
    });
});
