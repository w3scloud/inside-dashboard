<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ShopifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Shopify services
        $this->app->singleton(\App\Services\ShopifyService::class);
        $this->app->singleton(\App\Services\AnalyticsService::class);
        $this->app->singleton(\App\Services\DataCollectionService::class);
        $this->app->singleton(\App\Services\DataTransformationService::class);
        $this->app->singleton(\App\Services\ReportingService::class);
        $this->app->singleton(\App\Services\MockAnalyticsService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share common Shopify configuration with views
        View::composer('*', function ($view) {
            $view->with('shopifyConfig', [
                'api_version' => config('shopify.api_version'),
                'app_url' => config('app.url'),
            ]);
        });

        // Register additional middleware if needed
        if (method_exists($this->app['router'], 'aliasMiddleware')) {
            $this->app['router']->aliasMiddleware(
                'shopify.validate',
                \App\Http\Middleware\ValidateShopifyStore::class
            );
        }

        // Boot additional Shopify-related services
        $this->bootShopifyServices();
    }

    /**
     * Boot Shopify-related services.
     */
    private function bootShopifyServices(): void
    {
        // Register event listeners for Shopify events if needed
        // \Event::listen('shopify.store.connected', function ($store) {
        //     // Handle store connection events
        // });

        // You can add more Shopify-specific bootstrap logic here
    }
}
