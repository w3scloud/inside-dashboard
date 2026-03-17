<?php

namespace App\Http\Controllers;

use App\Services\ShopifyService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DataController extends Controller
{
    protected ShopifyService $shopifyService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }
    /**
     * Available data tables / entities for reports.
     */
    protected array $tables = [
        'abandoned_checkouts' => 'Abandoned Checkouts',
        'abandoned_checkouts_discount_codes' => 'Abandoned Checkouts Discount Codes',
        'abandoned_checkouts_line_item' => 'Abandoned Checkouts Line Item',
        'abandoned_checkouts_shipping_line' => 'Abandoned Checkouts Shipping Line',
        'collections' => 'Collections',
        'collections_products' => 'Collections Products',
        'countries' => 'Countries',
        'customer_address' => 'Customer Address',
        'customer_saved_searches' => 'Customer Saved Searches',
        'customers' => 'Customers',
        'discount_entitled_collections' => 'Discount Entitled Collections',
        'discount_entitled_country' => 'Discount Entitled Country',
        'discount_entitled_products' => 'Discount Entitled Products',
        'discount_entitled_variants' => 'Discount Entitled Variants',
        'discount_prerequisite_collection' => 'Discount Prerequisite Collection ID',
        'discount_prerequisite_customers' => 'Discount Prerequisite Customers',
        'discount_prerequisite_product' => 'Discount Prerequisite Product',
    ];

    /**
     * Return column configuration and optional sample rows for a table.
     */
    protected function getTableConfig(string $tableKey): array
    {
        switch ($tableKey) {
            case 'abandoned_checkouts':
                return [
                    'columns' => [
                        ['key' => 'checkout_id', 'label' => 'Checkout ID'],
                        ['key' => 'token', 'label' => 'Token'],
                        ['key' => 'created_at', 'label' => 'Created Date'],
                        ['key' => 'amount', 'label' => 'Amount'],
                        ['key' => 'currency', 'label' => 'Currency'],
                        ['key' => 'cart_token', 'label' => 'Cart Token'],
                        ['key' => 'email', 'label' => 'Email'],
                        ['key' => 'accepts_marketing', 'label' => 'Accepts Marketing'],
                        ['key' => 'landing_site', 'label' => 'Landing Site'],
                        ['key' => 'referring_site', 'label' => 'Refering Site'],
                        ['key' => 'note', 'label' => 'Note'],
                        ['key' => 'is_tax_included', 'label' => 'Is Tax Included'],
                        ['key' => 'customer_id', 'label' => 'Customer ID'],
                        ['key' => 'billing_address_1', 'label' => 'Billing Address Line 1'],
                        ['key' => 'billing_address_2', 'label' => 'Billing Address Line 2'],
                        ['key' => 'billing_city', 'label' => 'Billing City'],
                        ['key' => 'billing_company', 'label' => 'Billing Company'],
                        ['key' => 'billing_country', 'label' => 'Billing Country'],
                        ['key' => 'billing_first_name', 'label' => 'Billing First Name'],
                        ['key' => 'billing_last_name', 'label' => 'Billing Last Name'],
                        ['key' => 'billing_phone', 'label' => 'Billing Phone'],
                        ['key' => 'billing_province', 'label' => 'Billing Province'],
                        ['key' => 'billing_zip', 'label' => 'Billing Zip'],
                        ['key' => 'billing_latitude', 'label' => 'Billing Latitude'],
                        ['key' => 'billing_longitude', 'label' => 'Billing Longitude'],
                        ['key' => 'shipping_address_1', 'label' => 'Shipping Address Line 1'],
                        ['key' => 'shipping_address_2', 'label' => 'Shipping Address Line 2'],
                        ['key' => 'shipping_city', 'label' => 'Shipping City'],
                        ['key' => 'shipping_country', 'label' => 'Shipping Country'],
                        ['key' => 'shipping_company', 'label' => 'Shipping Company'],
                        ['key' => 'shipping_first_name', 'label' => 'Shipping First Name'],
                        ['key' => 'shipping_last_name', 'label' => 'Shipping Last Name'],
                        ['key' => 'shipping_latitude', 'label' => 'Shipping Latitude'],
                        ['key' => 'shipping_longitude', 'label' => 'Shipping Longitude'],
                        ['key' => 'shipping_phone', 'label' => 'Shipping Phone'],
                        ['key' => 'shipping_province', 'label' => 'Shipping Province'],
                        ['key' => 'shipping_zip', 'label' => 'Shipping Zip'],
                    ],
                    'rows' => [],
                ];
            case 'customers':
                return [
                    'columns' => [
                        ['key' => 'customer_id', 'label' => 'Customer ID'],
                        ['key' => 'first_name', 'label' => 'First Name'],
                        ['key' => 'last_name', 'label' => 'Last Name'],
                        ['key' => 'email', 'label' => 'Email'],
                        ['key' => 'phone', 'label' => 'Phone'],
                        ['key' => 'tags', 'label' => 'Tags'],
                        ['key' => 'tax_exempt', 'label' => 'Tax Exempt'],
                        ['key' => 'created_at', 'label' => 'Created At'],
                        ['key' => 'updated_at', 'label' => 'Updated At'],
                    ],
                    'rows' => [],
                ];
            default:
                return [
                    'columns' => [
                        ['key' => 'id', 'label' => 'ID'],
                        ['key' => 'created_at', 'label' => 'Created Date'],
                    ],
                    'rows' => [],
                ];
        }
    }

    protected function buildTablePayload(string $tableKey)
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! isset($this->tables[$tableKey])) {
            abort(404);
        }

        $table = [
            'key' => $tableKey,
            'label' => $this->tables[$tableKey],
        ];

        $config = $this->getTableConfig($tableKey);

        // For specific tables, fetch live data from Shopify
        $rows = $config['rows'];
        if ($tableKey === 'abandoned_checkouts' && $store) {
            $apiResponse = $this->shopifyService->makeApiCall(
                $store,
                'GET',
                '/admin/api/2023-07/checkouts.json',
                ['limit' => 50] // basic limit; can be extended with pagination later
            );

            if ($apiResponse && isset($apiResponse['checkouts'])) {
                $rows = collect($apiResponse['checkouts'])
                    ->map(function ($checkout) {
                        return [
                            'checkout_id' => $checkout['id'] ?? null,
                            'token' => $checkout['token'] ?? null,
                            'created_at' => $checkout['created_at'] ?? null,
                            'amount' => $checkout['subtotal_price'] ?? null,
                            'currency' => $checkout['currency'] ?? null,
                            'cart_token' => $checkout['cart_token'] ?? null,
                            'email' => $checkout['email'] ?? null,
                            'accepts_marketing' => $checkout['buyer_accepts_marketing'] ?? null,
                            'landing_site' => $checkout['landing_site'] ?? null,
                            'referring_site' => $checkout['referring_site'] ?? null,
                            'note' => $checkout['note'] ?? null,
                            'is_tax_included' => $checkout['taxes_included'] ?? null,
                            'customer_id' => $checkout['customer']['id'] ?? null,
                            'billing_address_1' => $checkout['billing_address']['address1'] ?? null,
                            'billing_address_2' => $checkout['billing_address']['address2'] ?? null,
                            'billing_city' => $checkout['billing_address']['city'] ?? null,
                            'billing_company' => $checkout['billing_address']['company'] ?? null,
                            'billing_country' => $checkout['billing_address']['country'] ?? null,
                            'billing_first_name' => $checkout['billing_address']['first_name'] ?? null,
                            'billing_last_name' => $checkout['billing_address']['last_name'] ?? null,
                            'billing_phone' => $checkout['billing_address']['phone'] ?? null,
                            'billing_province' => $checkout['billing_address']['province'] ?? null,
                            'billing_zip' => $checkout['billing_address']['zip'] ?? null,
                            'billing_latitude' => $checkout['billing_address']['latitude'] ?? null,
                            'billing_longitude' => $checkout['billing_address']['longitude'] ?? null,
                            'shipping_address_1' => $checkout['shipping_address']['address1'] ?? null,
                            'shipping_address_2' => $checkout['shipping_address']['address2'] ?? null,
                            'shipping_city' => $checkout['shipping_address']['city'] ?? null,
                            'shipping_country' => $checkout['shipping_address']['country'] ?? null,
                            'shipping_company' => $checkout['shipping_address']['company'] ?? null,
                            'shipping_first_name' => $checkout['shipping_address']['first_name'] ?? null,
                            'shipping_last_name' => $checkout['shipping_address']['last_name'] ?? null,
                            'shipping_latitude' => $checkout['shipping_address']['latitude'] ?? null,
                            'shipping_longitude' => $checkout['shipping_address']['longitude'] ?? null,
                            'shipping_phone' => $checkout['shipping_address']['phone'] ?? null,
                            'shipping_province' => $checkout['shipping_address']['province'] ?? null,
                            'shipping_zip' => $checkout['shipping_address']['zip'] ?? null,
                        ];
                    })
                    ->toArray();
            }
        }

        if ($tableKey === 'customers' && $store) {
            $apiResponse = $this->shopifyService->getCustomers($store, ['limit' => 50]);
            dd($apiResponse);

            if ($apiResponse && isset($apiResponse['customers'])) {
                $customers = $apiResponse['customers'];
            } elseif ($apiResponse && isset($apiResponse['customers']['customers'])) {
                // GraphQL fallback structure
                $customers = $apiResponse['customers']['customers'];
            } else {
                $customers = [];
            }

            $rows = collect($customers)
                ->map(function ($customer) {
                    return [
                        'customer_id' => $customer['id'] ?? null,
                        'first_name' => $customer['first_name'] ?? null,
                        'last_name' => $customer['last_name'] ?? null,
                        'email' => $customer['email'] ?? null,
                        'phone' => $customer['phone'] ?? null,
                        'tags' => isset($customer['tags']) && is_array($customer['tags'])
                            ? implode(', ', $customer['tags'])
                            : ($customer['tags'] ?? null),
                        'tax_exempt' => $customer['tax_exempt'] ?? null,
                        'created_at' => $customer['created_at'] ?? null,
                        'updated_at' => $customer['updated_at'] ?? null,
                    ];
                })
                ->toArray();
        }

        return [
            'table' => $table,
            'columns' => $config['columns'],
            'rows' => $rows,
            'store' => $store ? [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ] : null,
        ];
    }

    public function abandonedCheckouts()
    {
        $payload = $this->buildTablePayload('abandoned_checkouts');

        return Inertia::render('Data/AbandonedCheckouts', $payload);
    }

    public function customers()
    {
        $payload = $this->buildTablePayload('customers');
        dd($payload);

        return Inertia::render('Data/Customers', $payload);
    }

    public function abandonedCheckoutsDiscountCodes()
    {
        $payload = $this->buildTablePayload('abandoned_checkouts_discount_codes');

        return Inertia::render('Data/AbandonedCheckoutsDiscountCodes', $payload);
    }

    public function abandonedCheckoutsLineItem()
    {
        $payload = $this->buildTablePayload('abandoned_checkouts_line_item');

        return Inertia::render('Data/AbandonedCheckoutsLineItem', $payload);
    }

    public function abandonedCheckoutsShippingLine()
    {
        $payload = $this->buildTablePayload('abandoned_checkouts_shipping_line');

        return Inertia::render('Data/AbandonedCheckoutsShippingLine', $payload);
    }

    public function collections()
    {
        $payload = $this->buildTablePayload('collections');

        return Inertia::render('Data/Collections', $payload);
    }

    public function collectionsProducts()
    {
        $payload = $this->buildTablePayload('collections_products');

        return Inertia::render('Data/CollectionsProducts', $payload);
    }

    public function countries()
    {
        $payload = $this->buildTablePayload('countries');

        return Inertia::render('Data/Countries', $payload);
    }

    public function customerAddress()
    {
        $payload = $this->buildTablePayload('customer_address');

        return Inertia::render('Data/CustomerAddress', $payload);
    }

    public function customerSavedSearches()
    {
        $payload = $this->buildTablePayload('customer_saved_searches');
       

        return Inertia::render('Data/CustomerSavedSearches', $payload);
    }

    public function discountEntitledCollections()
    {
        $payload = $this->buildTablePayload('discount_entitled_collections');

        return Inertia::render('Data/DiscountEntitledCollections', $payload);
    }

    public function discountEntitledCountry()
    {
        $payload = $this->buildTablePayload('discount_entitled_country');

        return Inertia::render('Data/DiscountEntitledCountry', $payload);
    }

    public function discountEntitledProducts()
    {
        $payload = $this->buildTablePayload('discount_entitled_products');

        return Inertia::render('Data/DiscountEntitledProducts', $payload);
    }

    public function discountEntitledVariants()
    {
        $payload = $this->buildTablePayload('discount_entitled_variants');

        return Inertia::render('Data/DiscountEntitledVariants', $payload);
    }

    public function discountPrerequisiteCollection()
    {
        $payload = $this->buildTablePayload('discount_prerequisite_collection');

        return Inertia::render('Data/DiscountPrerequisiteCollection', $payload);
    }

    public function discountPrerequisiteCustomers()
    {
        $payload = $this->buildTablePayload('discount_prerequisite_customers');

        return Inertia::render('Data/DiscountPrerequisiteCustomers', $payload);
    }

    public function discountPrerequisiteProduct()
    {
        $payload = $this->buildTablePayload('discount_prerequisite_product');

        return Inertia::render('Data/DiscountPrerequisiteProduct', $payload);
    }
}

