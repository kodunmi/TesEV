<?php

use App\Http\Controllers\V1\User\ComplianceController;
use Illuminate\Support\Facades\Route;

Route::prefix('compliance')->controller(ComplianceController::class)->group(function () {
    Route::post('/', 'completeCompliance');
});
