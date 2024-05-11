<?php

use App\Http\Controllers\V1\Admin\AdminManagementController;
use App\Http\Controllers\V1\Admin\BuildingManagementController;
use App\Http\Controllers\V1\Admin\SubscriptionManagementController;
use App\Http\Controllers\V1\Admin\TransactionManagementController;
use App\Http\Controllers\V1\Admin\TripManagementController;
use App\Http\Controllers\V1\Admin\UserManagementController;
use App\Http\Controllers\V1\Admin\VehicleManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('admins')->controller(AdminManagementController::class)->group(function () {
    Route::get('/', 'getAdmins');
    Route::post('/', 'addAdmin');
    Route::get('/analytics', 'getAdminAnalytics');

    Route::prefix('{admin_id}')->group(function () {
        Route::get('/', 'getAdmin');
        Route::put('/', 'updateAdmin');
        Route::put('/status/toggle', 'toggleStatus');
        Route::delete('/', 'removeAdmin');

        Route::prefix('role')->group(function () {
            Route::get('/', 'getAdminRole');
            Route::put('/change', 'changeAdminRole');
        });
        Route::prefix('password')->group(function () {
            Route::post('/reset', 'resetAdminPassword');
        });
    });

    Route::prefix('roles')->group(function () {
        Route::get('/', 'getRoles');
    });
});

Route::prefix('buildings')->controller(BuildingManagementController::class)->group(function () {
    Route::get('/', 'getBuildings');
    Route::post('/', 'addBuilding');
    Route::get('/analytics', 'getBuildingAnalytics');

    Route::prefix('{building_id}')->group(function () {
        Route::get('/', 'getBuilding');
        Route::put('/', 'updateBuilding');
        Route::delete('/', 'deleteBuilding');
        Route::put('/toggle/status', 'toggleAvailability');

        Route::prefix('vehicles')->group(function () {
            Route::post('/add', 'addVehicleToBuilding');
            Route::post('/add-async', 'addVehicleToBuildingAsync');
            Route::post('/remove', 'removeVehicleFromBuilding');
            Route::post('/remove-async', 'removeVehicleFromBuildingAsync');
        });
    });
});

Route::prefix('subscriptions')->controller(SubscriptionManagementController::class)->group(function () {
    Route::get('/', 'getSubscriptions');
    Route::get('/analytics', 'getSubscriptionsAnalytics');

    Route::prefix('products')->group(function () {
        Route::get('/', 'getProducts');

        Route::prefix('{product_id}')->group(function () {
            Route::get('/', 'getProduct');
            Route::put('/', 'updateProduct');
            Route::delete('/', 'deleteProduct');
        });
    });

    Route::prefix('{subscription_id}')->group(function () {
        Route::get('/', 'getSubscription');
        Route::get('/users', 'getSubscriptionUsers');
    });
});

Route::prefix('transactions')->controller(TransactionManagementController::class)->group(function () {
    Route::get('/', 'getTransactions');
    Route::get('/{transaction_id}', 'getTransaction');
    Route::get('/analytics', 'getTransactionAnalytics');
});

Route::prefix('trips')->controller(TripManagementController::class)->group(function () {
    Route::get('/', 'getTrips');
    Route::get('/analytics', 'getTripsAnalytics');

    Route::prefix('{trip_id}')->group(function () {
        Route::get('/', 'getTrip');
        Route::put('/', 'updateTrip');
        Route::delete('/', 'deleteTrip');
        Route::post('/toggle', 'toggleTripStatus');

        Route::prefix('charges')->group(function () {
            Route::get('/', 'getTripCharges');
            Route::post('/', 'addTripCharge');
        });
    });
});

Route::prefix('users')->controller(UserManagementController::class)->group(function () {
    Route::get('/', 'getUsers');
    Route::post('/', 'addUser');
    Route::get('/analytics', 'getUserAnalytics');

    Route::prefix('{user_id}')->group(function () {
        Route::get('/', 'getUser');
        Route::put('/', 'updateUser');
        Route::delete('/', 'deleteUser');
        Route::post('/toggle', 'toggleUserStatus');
        Route::post('/verify', 'verifyUser');

        Route::prefix('buildings')->group(function () {
            Route::get('/', 'getUserBuildings');
            Route::post('/', 'verifyUserBuilding');
        });
    });
});

Route::prefix('vehicles')->controller(VehicleManagementController::class)->group(function () {
    Route::get('/', 'getVehicles');
    Route::post('/', 'addVehicles');
    Route::get('/analytics', 'getVehicleAnalytics');

    Route::prefix('{vehicle_id}')->group(function () {
        Route::get('/', 'getVehicle');
        Route::get('/trips', 'getVehicleTrips');
        Route::put('/', 'updateVehicle');
        Route::delete('/', 'deleteVehicle');
        Route::post('/toggle', 'toggleVehicleStatus');
    });
});
