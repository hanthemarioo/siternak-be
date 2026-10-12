<?php

use App\Http\Controllers\Api\{
    AuthController,
    DashboardController,
    FarmController,
    FlockController,
    GrowerPhaseController,
    LayerPhaseController,
    ReportController,
    UserController
};

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH — Public routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/auth')->middleware('throttle:5,1')->group(function () {
    // Public registration is disabled because this app is managed per farm, not self-service SaaS.
    // Route::post('register',        [AuthController::class, 'register']);
    Route::post('login',           [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| PROTECTED — Requires Sanctum token
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::get('me',                [AuthController::class, 'me']);
        Route::post('logout',           [AuthController::class, 'logout']);
        Route::post('logout-all',       [AuthController::class, 'logoutAll']);
        Route::post('change-password',  [AuthController::class, 'changePassword']);
    });

    Route::apiResource('farms', FarmController::class);
    Route::post('flocks/{flock}/close', [FlockController::class, 'close']);
    Route::apiResource('flocks', FlockController::class);
    Route::get('coops', [FlockController::class, 'getCoops']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('grower-phases', GrowerPhaseController::class);
    Route::apiResource('layer-phases', LayerPhaseController::class);

    Route::prefix('reports')->group(function () {
        Route::get('grower', [ReportController::class, 'grower']);
        Route::get('layer', [ReportController::class, 'layer']);
        Route::get('grower/export', [ReportController::class, 'exportGrower']);
        Route::get('layer/export', [ReportController::class, 'exportLayer']);
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/feed-chart', [DashboardController::class, 'feedChart']);
        Route::get('/egg-chart', [DashboardController::class, 'eggChart']);
        Route::get('/mortality-chart', [DashboardController::class, 'mortalityChart']);
    });
});
