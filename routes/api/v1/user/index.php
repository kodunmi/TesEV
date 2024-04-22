<?php

use App\Http\Controllers\V1\User\AuthController;
use App\Http\Controllers\V1\User\BuildingController;
use App\Http\Controllers\V1\User\CardController;
use App\Http\Controllers\V1\User\ComplianceController;
use App\Http\Controllers\V1\User\SubscriptionController;
use App\Http\Controllers\V1\User\TripController;
use Illuminate\Support\Facades\Route;

Route::prefix('compliance')->controller(ComplianceController::class)->group(function () {
    Route::post('/', 'completeCompliance');
});

Route::prefix('buildings')->controller(BuildingController::class)->group(function () {
    Route::post('/', 'addBuilding');
    Route::get('/', 'getBuildings');
    Route::delete('/', 'removeBuilding');

    Route::prefix('public')->group(function () {
        Route::get('/', 'getAllAvailableBuildings');
    });

    Route::prefix('{building_id}')->group(function () {
        Route::get('/', 'getBuilding');
        Route::get('/vehicles', 'getVehicles');
        Route::post('/vehicles/available', 'getAvailableVehicles');
    });
});

Route::prefix('subscriptions')->controller(SubscriptionController::class)->group(function () {
    Route::get('/packages', 'getPackages');
    Route::get('/', 'getAllSubscriptions');
    Route::get('/active', 'getUserActiveSubscriptions');
    Route::get('/expired', 'getUserExpiredSubscriptions');
    Route::get('/transactions', 'transactionHistory');

    Route::prefix('{package_id}')->group(function () {
        Route::post('/subscribe', 'subscribe');
        Route::post('/unsubscribe', 'unsubscribe');
        Route::post('/reactivate', 'reactiveSubscription');
    });
});


Route::prefix('trips')->controller(TripController::class)->group(function () {
    Route::post('/costing', 'getCosting');
    Route::post('/', 'createTrip');

    Route::prefix('{trip_id}')->group(function () {
        Route::post('/add', 'addExtraTime');
        Route::post('/report', 'reportTrip');
    });
});

Route::prefix('cards')->controller(CardController::class)->group(function () {
    Route::post('/', 'addCard');
    Route::get('/', 'getCards');
    Route::get('/default', 'getDefaultCard');
});


Route::prefix('settings')->group(function () {
    Route::prefix('password')->group(function () {
        Route::post('/reset', [AuthController::class, 'resetPassword']);
    });
});
