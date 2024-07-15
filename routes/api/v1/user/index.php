<?php

use App\Http\Controllers\V1\User\AccountController;
use App\Http\Controllers\V1\User\AuthController;
use App\Http\Controllers\V1\User\BuildingController;
use App\Http\Controllers\V1\User\CardController;
use App\Http\Controllers\V1\User\ComplianceController;
use App\Http\Controllers\V1\User\NotificationController;
use App\Http\Controllers\V1\User\SubscriptionController;
use App\Http\Controllers\V1\User\TransactionController;
use App\Http\Controllers\V1\User\TripController;
use App\Http\Controllers\V1\User\WalletController;
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
    });

    Route::post('/unsubscribe', 'unsubscribe');
    Route::post('/reactivate', 'reactiveSubscription');
});


Route::prefix('trips')->controller(TripController::class)->group(function () {
    Route::post('/costing', 'getCosting');
    Route::get('/', 'getTrips');
    Route::post('/', 'createTrip');

    Route::prefix('{trip_id}')->group(function () {
        Route::get('/', 'getTrip');
        Route::post('/add', 'addExtraTime');
        Route::post('/report', 'reportTrip');
        Route::post('/start', 'startTrip');
        Route::post('/end', 'endTrip');
        Route::post('/cancel', 'cancelTrip');
    });
});

Route::prefix('cards')->controller(CardController::class)->group(function () {
    Route::post('/', 'addCard');
    Route::get('/', 'getCards');
    Route::get('/default', 'getDefaultCard');
    Route::post('/{card_id}/default', 'setDefaultCard');
});


Route::prefix('wallet')->controller(WalletController::class)->group(function () {
    Route::post('/fund', 'fundWallet');
    Route::get('/', 'getBalance');
    Route::get('/transactions', 'getTransactions');
});

Route::prefix('settings')->group(function () {
    Route::prefix('password')->group(function () {
        Route::post('/reset', [AuthController::class, 'resetPassword']);
    });
});


Route::prefix('transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'getAllTransactions']);
    Route::get('/{transaction_id}', [TransactionController::class, 'getTransaction']);
});

Route::prefix('/notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'getAllNotifications']);
    Route::get('/counts', [NotificationController::class, 'getNotificationCounts']);
    Route::post('/', [NotificationController::class, 'getAllNotifications']);
    Route::put('/read', [NotificationController::class, 'markAllNotificationsAsRead']);
    Route::delete('/', [NotificationController::class, 'deleteAllNotifications']);

    Route::prefix('/{id}')->group(function () {
        Route::get('/', [NotificationController::class, 'getNotification']);
        Route::put('/read', [NotificationController::class, 'markNotificationAsRead']);
        Route::delete('/delete', [NotificationController::class, 'deleteNotification']);
    });
});

Route::prefix('account')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [AccountController::class, 'getProfile']);
        Route::prefix('update')->group(function () {
            Route::put('/', [AccountController::class, 'updateProfile']);
            Route::put('/image', [AccountController::class, 'updateProfileImage']);
        });
    });

    Route::prefix('tokens')->group(function () {
        Route::post('/refresh', [AccountController::class, 'refreshToken']);
        Route::post('/delete', [AccountController::class, 'deleteToken']);
    });
});
