<?php

use App\Http\Controllers\Auth\ShopifyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

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

// Public landing page - show Shopify login
Route::get('/', [ShopifyController::class, 'showLogin'])
    ->name('home');

// Authentication routes
// Note: We don't use 'guest' middleware here because authenticated users
// need to access these routes to be redirected to dashboard if they have a store
Route::get('/login', [ShopifyController::class, 'showLogin'])
    ->name('login');

Route::get('/auth/shopify', [ShopifyController::class, 'showLogin'])
    ->name('shopify.login');

// OAuth initiation (POST) - only for guests
Route::middleware('guest')->group(function () {
    // Handle shop domain submission and initiate OAuth
    Route::post('/auth/shopify', [ShopifyController::class, 'initiateOAuth'])
        ->name('shopify.auth');

    // OAuth callback from Shopify
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
        Route::get('/dashboard/{id}/fetch-data', [DashboardController::class, 'fetchData'])
            ->name('dashboard.data');
        Route::put('/dashboard/{id}/layout', [DashboardController::class, 'updateLayout'])
            ->name('dashboard.layout.update');
        Route::post('/dashboard/{id}/widget', [DashboardController::class, 'addWidget'])
            ->name('dashboard.widget.add');
        Route::put('/dashboard/{id}/widget/{widgetId}', [DashboardController::class, 'updateWidget'])
            ->name('dashboard.widget.update');
        Route::delete('/dashboard/{id}/widget/{widgetId}', [DashboardController::class, 'removeWidget'])
            ->name('dashboard.widget.remove');

        // Data table reports
        Route::prefix('data')->name('data.')->group(function () {
            Route::get('/abandoned-checkouts', [DataController::class, 'abandonedCheckouts'])
                ->name('abandoned_checkouts');
            Route::get('/abandoned-checkouts-discount-codes', [DataController::class, 'abandonedCheckoutsDiscountCodes'])
                ->name('abandoned_checkouts_discount_codes');
            Route::get('/abandoned-checkouts-line-item', [DataController::class, 'abandonedCheckoutsLineItem'])
                ->name('abandoned_checkouts_line_item');
            Route::get('/abandoned-checkouts-shipping-line', [DataController::class, 'abandonedCheckoutsShippingLine'])
                ->name('abandoned_checkouts_shipping_line');
            Route::get('/collections', [DataController::class, 'collections'])
                ->name('collections');
            Route::get('/collections-products', [DataController::class, 'collectionsProducts'])
                ->name('collections_products');
            Route::get('/countries', [DataController::class, 'countries'])
                ->name('countries');
            Route::get('/customer-address', [DataController::class, 'customerAddress'])
                ->name('customer_address');
            Route::get('/customer-saved-searches', [DataController::class, 'customerSavedSearches'])
                ->name('customer_saved_searches');
            Route::get('/customers', [DataController::class, 'customers'])
                ->name('customers');
            Route::get('/discount-entitled-collections', [DataController::class, 'discountEntitledCollections'])
                ->name('discount_entitled_collections');
            Route::get('/discount-entitled-country', [DataController::class, 'discountEntitledCountry'])
                ->name('discount_entitled_country');
            Route::get('/discount-entitled-products', [DataController::class, 'discountEntitledProducts'])
                ->name('discount_entitled_products');
            Route::get('/discount-entitled-variants', [DataController::class, 'discountEntitledVariants'])
                ->name('discount_entitled_variants');
            Route::get('/discount-prerequisite-collection', [DataController::class, 'discountPrerequisiteCollection'])
                ->name('discount_prerequisite_collection');
            Route::get('/discount-prerequisite-customers', [DataController::class, 'discountPrerequisiteCustomers'])
                ->name('discount_prerequisite_customers');
            Route::get('/discount-prerequisite-product', [DataController::class, 'discountPrerequisiteProduct'])
                ->name('discount_prerequisite_product');
        });

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

// Logout route
Route::post('/logout', [ShopifyController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

require __DIR__.'/auth.php';
