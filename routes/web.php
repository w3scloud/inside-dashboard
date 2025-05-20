<?php

use App\Http\Controllers\Auth\ShopifyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public landing page
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('welcome');

// Authentication routes (from Laravel Breeze)
Route::middleware('guest')->group(function () {
    // Default Laravel Breeze routes
    Route::get('login', [ProfileController::class, 'showLogin'])
        ->name('login');

    // Shopify-specific routes
    Route::get('/auth/shopify', [ShopifyController::class, 'initiateOAuth'])
        ->name('shopify.auth');
    Route::get('/auth/callback', [ShopifyController::class, 'handleCallback'])
        ->name('shopify.callback');
});

// Webhook routes (no CSRF, no auth)
Route::post('/webhooks/{topic}', [WebhookController::class, 'handle'])
    ->withoutMiddleware(['web'])
    ->name('webhooks.handle');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Shopify store validation
    Route::middleware(['shopify.store'])->group(function () {
        // Dashboard routes
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/dashboard/create', [DashboardController::class, 'create'])
            ->name('dashboard.create');
        Route::post('/dashboard', [DashboardController::class, 'store'])
            ->name('dashboard.store');
        Route::get('/dashboard/{id}', [DashboardController::class, 'show'])
            ->name('dashboard.show');
        Route::get('/dashboard/{id}/edit', [DashboardController::class, 'edit'])
            ->name('dashboard.edit');
        Route::put('/dashboard/{id}', [DashboardController::class, 'update'])
            ->name('dashboard.update');
        Route::delete('/dashboard/{id}', [DashboardController::class, 'destroy'])
            ->name('dashboard.destroy');

        // Dashboard data and widget routes
        Route::get('/dashboard/{id}/data', [DashboardController::class, 'fetchData'])
            ->name('dashboard.data');
        Route::put('/dashboard/{id}/layout', [DashboardController::class, 'updateLayout'])
            ->name('dashboard.layout.update');
        Route::post('/dashboard/{id}/widget', [DashboardController::class, 'addWidget'])
            ->name('dashboard.widget.add');
        Route::put('/dashboard/{id}/widget/{widgetId}', [DashboardController::class, 'updateWidget'])
            ->name('dashboard.widget.update');
        Route::delete('/dashboard/{id}/widget/{widgetId}', [DashboardController::class, 'removeWidget'])
            ->name('dashboard.widget.remove');

        // Report routes
        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports');
        Route::get('/reports/create', [ReportController::class, 'create'])
            ->name('reports.create');
        Route::post('/reports', [ReportController::class, 'store'])
            ->name('reports.store');
        Route::get('/reports/{id}', [ReportController::class, 'show'])
            ->name('reports.show');
        Route::get('/reports/{id}/edit', [ReportController::class, 'edit'])
            ->name('reports.edit');
        Route::put('/reports/{id}', [ReportController::class, 'update'])
            ->name('reports.update');
        Route::delete('/reports/{id}', [ReportController::class, 'destroy'])
            ->name('reports.destroy');
        Route::get('/reports/{id}/generate', [ReportController::class, 'generate'])
            ->name('reports.generate');
        Route::get('/reports/{id}/download', [ReportController::class, 'download'])
            ->name('reports.download');

        // Product analytics routes
        Route::get('/products', [ProductController::class, 'index'])
            ->name('products');
        Route::get('/products/performance', [ProductController::class, 'performance'])
            ->name('products.performance');
        Route::get('/products/inventory', [ProductController::class, 'inventory'])
            ->name('products.inventory');
        Route::get('/products/{id}', [ProductController::class, 'show'])
            ->name('products.show');

        // Customer analytics routes
        Route::get('/customers', [CustomerController::class, 'index'])
            ->name('customers');
        Route::get('/customers/segments', [CustomerController::class, 'segments'])
            ->name('customers.segments');
        Route::get('/customers/{id}', [CustomerController::class, 'show'])
            ->name('customers.show');

        // Settings routes
        Route::get('/settings', [SettingsController::class, 'index'])
            ->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->name('settings.update');
        Route::get('/settings/account', [SettingsController::class, 'account'])
            ->name('settings.account');
        Route::put('/settings/account', [SettingsController::class, 'updateAccount'])
            ->name('settings.account.update');
    });

    // Profile routes (from Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Auth routes already defined by Laravel Breeze, including:
// - Login
// - Register
// - Password reset
// - Email verification

// Logout route
Route::post('/logout', [ShopifyController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

require __DIR__.'/auth.php';
require __DIR__.'/auth.php';
