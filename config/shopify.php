<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for your Shopify integration.
    |
    */

    'api_key' => env('SHOPIFY_API_KEY', ''),
    'api_secret' => env('SHOPIFY_API_SECRET', ''),
    'api_version' => env('SHOPIFY_API_VERSION', '2023-07'),
    'app_url' => env('APP_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Scopes
    |--------------------------------------------------------------------------
    |
    | This is the list of scopes your application needs to access the Shopify API.
    |
    */
    'scopes' => [
        'read_products',
        'read_orders',
        'read_customers',
        'read_inventory',
        'read_content',
        'read_themes',
        'read_analytics',
        'read_reports', // Required for report generation
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Topics
    |--------------------------------------------------------------------------
    |
    | This is the list of webhook topics your application will register with Shopify.
    |
    */
    'webhooks' => [
        'app/uninstalled',
        'shop/update',
        'products/create',
        'products/update',
        'products/delete',
        'orders/create',
        'orders/updated',
        'orders/cancelled',
        'customers/create',
        'customers/update',
        'customers/delete',
        'inventory_levels/connect',
        'inventory_levels/update',
        'inventory_items/update',
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Types
    |--------------------------------------------------------------------------
    |
    | This is the list of report types available in the application.
    |
    */
    'report_types' => [
        'sales_summary' => [
            'name' => 'Sales Summary',
            'description' => 'Overview of sales performance',
        ],
        'product_performance' => [
            'name' => 'Product Performance',
            'description' => 'Analysis of product sales and performance',
        ],
        'inventory_status' => [
            'name' => 'Inventory Status',
            'description' => 'Current inventory levels and status',
        ],
        'customer_insights' => [
            'name' => 'Customer Insights',
            'description' => 'Analysis of customer behavior and segmentation',
        ],
    ],
];
