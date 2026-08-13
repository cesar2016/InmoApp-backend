<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatTestController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\ContractFileController;
use App\Http\Controllers\Api\ContractUploadController;
use App\Http\Controllers\Api\GuarantorController;
use App\Http\Controllers\Api\LiquidationController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TenantController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('owners', OwnerController::class);
    Route::apiResource('properties', PropertyController::class);
    Route::apiResource('tenants', TenantController::class);
    Route::apiResource('contracts', ContractController::class);
    Route::post('/contracts/upload', [ContractUploadController::class, 'upload']);
    Route::post('/chat', [ChatTestController::class, 'chat']);
    Route::post('/chat/parse-text', [ChatTestController::class, 'parseText']);
    Route::post('/contracts/smart-save', [ContractUploadController::class, 'smartSave']);
    Route::get('/chat/providers', [ChatTestController::class, 'getProviders']);
    // Diagnostic endpoint to upload and parse without auth (temporary for debugging)
    Route::post('/diagnostic/contracts/upload', [ContractUploadController::class, 'diagnosticUpload']);
    Route::get('/contracts/{contract}/file', [ContractFileController::class, 'show']);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('maintenances', MaintenanceController::class);
    Route::apiResource('guarantors', GuarantorController::class);
    Route::post('/liquidations/send-email', [LiquidationController::class, 'sendEmail']);
    Route::apiResource('liquidations', LiquidationController::class);
    Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow']);
    Route::get('/reports/alerts', [ReportController::class, 'alerts']);
    Route::get('/reports/liquidation/{owner_id}', [ReportController::class, 'liquidation']);
});
