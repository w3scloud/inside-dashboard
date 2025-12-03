<?php

use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('analytics')->group(function () {
    Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/sales', [AnalyticsController::class, 'sales']);
    Route::get('/products', [AnalyticsController::class, 'products']);
    Route::get('/customers', [AnalyticsController::class, 'customers']);
    Route::get('/inventory', [AnalyticsController::class, 'inventory']);
    Route::get('/test-graphql', [AnalyticsController::class, 'testGraphQL']);
    Route::get('/export', [AnalyticsController::class, 'export']);

    // Debug routes - only available in non-production environments
    if (app()->environment(['local', 'testing', 'development'])) {
        Route::get('/debug/all-orders', [AnalyticsController::class, 'debugAllOrders']);
        Route::get('/debug/date-range', [AnalyticsController::class, 'debugDateRange']);
        Route::get('/debug/debugscopes', [AnalyticsController::class, 'debugScopes']);
        Route::get('/debug/graphql-date-query', [AnalyticsController::class, 'debugGraphQLDateQuery']);
    }
});
