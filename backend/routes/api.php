<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::prefix('me')->group(function (): void {
        Route::get('profile', [MeController::class, 'profile']);
        Route::get('employment', [MeController::class, 'employment']);
        Route::get('salary', [MeController::class, 'salary']);
        Route::get('certificates', [MeController::class, 'certificates']);
        Route::get('leave', [MeController::class, 'leaveIndex']);
        Route::post('leave', [MeController::class, 'leaveStore']);
        Route::patch('leave/{leaveRequest}/cancel', [MeController::class, 'leaveCancel']);
        Route::get('assets', [MeController::class, 'assets']);
        Route::get('asset-requests', [MeController::class, 'assetRequestIndex']);
        Route::post('asset-requests', [MeController::class, 'assetRequestStore']);
        Route::patch('asset-requests/{assetRequest}/cancel', [MeController::class, 'assetRequestCancel']);
        Route::get('documents', [MeController::class, 'documentsIndex']);
        Route::post('documents', [MeController::class, 'documentsStore']);
        Route::delete('documents/{employeeDocument}', [MeController::class, 'documentsDestroy']);
    });
});
