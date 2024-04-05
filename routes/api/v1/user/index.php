<?php

use App\Http\Controllers\V1\User\BuildingController;
use App\Http\Controllers\V1\User\ComplianceController;
use Illuminate\Support\Facades\Route;

Route::prefix('compliance')->controller(ComplianceController::class)->group(function () {
    Route::post('/', 'completeCompliance');
});

Route::prefix('buildings')->controller(BuildingController::class)->group(function () {
    Route::post('/', 'addBuilding');
    Route::get('/', 'getBuildings');
    Route::get('/{building_id}', 'getBuilding');
    Route::delete('/', 'removeBuilding');

    Route::prefix('public')->group(function () {
        Route::get('/', 'getAllAvailableBuildings');
    });
});
