<?php

use App\Http\Controllers\V1\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\V1\User\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');

    Route::prefix('register')->group(function () {
        Route::post('/', 'register');
        Route::post('/confirm', 'confirmAccount');
        Route::post('/token/resent', 'resendConfirmAccountToken');
    });

    Route::prefix('password')->group(function () {
        Route::post('/forget', 'forgetPassword');
        Route::post('/confirm', 'confirmResetPasswordToken');
    });
});

Route::prefix('admin')->controller(AdminAuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::prefix('password')->group(function () {
        Route::post('/forget', 'forgetPassword');
        Route::post('/reset', 'resetPassword');
    });
});
