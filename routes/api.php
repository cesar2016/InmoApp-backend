<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\MaintenanceController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('owners', OwnerController::class);
    Route::apiResource('properties', PropertyController::class);
    Route::apiResource('tenants', TenantController::class);
    Route::apiResource('contracts', ContractController::class);
    Route::post('/contracts/upload', [\App\Http\Controllers\Api\ContractUploadController::class, 'upload']);
    Route::get('/contracts/{contract}/file', [\App\Http\Controllers\Api\ContractFileController::class, 'show']);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('maintenances', App\Http\Controllers\Api\MaintenanceController::class);
    Route::apiResource('guarantors', App\Http\Controllers\Api\GuarantorController::class);
    Route::get('/reports/cash-flow', [App\Http\Controllers\Api\ReportController::class, 'cashFlow']);
    Route::get('/reports/alerts', [\App\Http\Controllers\Api\ReportController::class, 'alerts']);
    Route::get('/reports/liquidation/{owner_id}', [\App\Http\Controllers\Api\ReportController::class, 'liquidation']);
});
