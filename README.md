# Shopify Data Visualization Extension

A comprehensive Shopify extension that provides rich data visualization dashboards for store owners using Laravel, Inertia.js, and Vue.

## Project Overview

This extension connects to Shopify's Admin API to fetch store data, processes it into meaningful insights, and creates interactive visualizations using Vue.js within a Laravel/Inertia.js application.

## Features

-   **Sales Performance Dashboards**: Daily/weekly/monthly views
-   **Product Performance Analysis**: Identify top/underperforming products
-   **Customer Segmentation Visualizations**: Understand your customer base
-   **Inventory Management Insights**: Track stock levels
-   **Custom KPI Tracking**: Monitor what matters to your business
-   **Export Capabilities**: CSV, PDF formats
-   **Report Scheduling**: Automated email delivery

## Technical Stack

-   **Backend**: Laravel 10.x
-   **Frontend**: Vue 3 with Inertia.js
-   **Visualization Libraries**: Chart.js/Vue-Chart.js
-   **Database**: MySQL
-   **Authentication**: Laravel Breeze with Shopify OAuth
-   **CSS Framework**: Tailwind CSS

## Installation & Setup

### Prerequisites

-   PHP 8.1+
-   Composer
-   Node.js 16+
-   Yarn
-   MySQL
-   Shopify Partner account

### Installation Steps

1. Clone the repository:

```bash
git clone https://github.com/w3scloud/inside-dashboard
cd inside-dashboard
```

2. Install PHP dependencies:

```bash
composer install
```

3. Install JavaScript dependencies:

```bash
yarn install
```

4. Set up environment variables:

```bash
cp .env.example .env
# Edit .env with your database and Shopify credentials
```

5. Generate application key:

```bash
php artisan key:generate
```

6. Run migrations:

```bash
php artisan migrate
```

7. Build assets:

```bash
yarn dev
```

8. Start the development server:

```bash
php artisan serve
```

### Shopify App Setup

1. Create a new app in your Shopify Partner Dashboard
2. Configure the App URL and Allowed redirection URLs
3. Note your API Key (Client ID) and API Secret Key (Client Secret)
4. Update your `.env` file with these credentials:

```
SHOPIFY_API_KEY=your_client_id
SHOPIFY_API_SECRET=your_client_secret
SHOPIFY_SCOPES=read_products,write_products,read_orders,read_customers
SHOPIFY_APP_URL=https://your-app-url.com
```

## Development Workflow

### Running the Development Environment

```bash
# Terminal 1 - Laravel server
php artisan serve

# Terminal 2 - Vite dev server for hot module replacement
yarn dev

# Terminal 3 - Ngrok for public URL (if developing locally)
ngrok http 8000
```

### Project Structure Overview

```
inside-dashboard/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── DashboardController.php
│   │   │   ├── ReportController.php
│   │   │   ├── ProductController.php
│   │   │   ├── CustomerController.php
│   │   │   └── SettingsController.php
│   │   └── Middleware/
│   │       └── HandleShopifyAuth.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Store.php
│   │   ├── Dashboard.php
│   │   ├── Widget.php
│   │   └── Report.php
│   └── Services/
│       ├── ShopifyService.php
│       ├── DataCollectionService.php
│       ├── DataTransformationService.php
│       ├── AnalyticsService.php
│       └── ReportingService.php
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   ├── Auth/
│   │   │   ├── Dashboard/
│   │   │   ├── Reports/
│   │   │   ├── Products/
│   │   │   ├── Customers/
│   │   │   └── Settings/
│   │   ├── Components/
│   │   │   ├── Layout/
│   │   │   ├── Charts/
│   │   │   ├── Widgets/
│   │   │   └── Forms/
│   └── views/
├── routes/
│   └── web.php
└── config/
    └── shopify.php
```

## Key Features Implementation

### 1. Shopify Authentication

The application uses OAuth to authenticate with Shopify stores:

```php
// ShopifyController.php
public function initiateOAuth(Request $request)
{
    $shop = $request->input('shop');
    // Generate the authorization URL
    $authUrl = $this->shopifyService->getAuthUrl($shop);
    return redirect()->away($authUrl);
}
```

### 2. Dashboard System

Create custom dashboards with different widgets:

```php
// DashboardController.php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
    ]);

    $dashboard = new Dashboard([
        'name' => $validated['name'],
        'description' => $validated['description'] ?? null,
        'layout' => [],
    ]);

    $store->dashboards()->save($dashboard);
    // ...
}
```

### 3. Data Visualization

Using Vue.js components with Chart.js for visualization:

```javascript
// LineChart.vue
setup(props) {
    const chartCanvas = ref(null);
    let chart = null;

    const createChart = () => {
        if (!chartCanvas.value || !props.chartData) return;

        // Create new chart
        chart = new Chart(chartCanvas.value, {
            type: 'line',
            data: props.chartData,
            options: props.options
        });
    };
    // ...
}
```

### 4. Reporting System

Generate and schedule reports:

```php
// ReportingService.php
public function generateReport(Report $report): array
{
    try {
        $store = $report->store;
        $config = $report->config;

        // Get date range from config or use defaults
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(30);

        // Get report data based on type
        $reportData = $this->getReportData(
            $store,
            $report->type,
            $startDate,
            $endDate,
            $report->filters ?? []
        );
        // ...
    }
}
```

## Deployment

### Production Build

```bash
yarn build
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Server Requirements

-   PHP 8.1+
-   Composer
-   Web server (Nginx or Apache)
-   MySQL 8.0+
-   SSL certificate

## Troubleshooting

### Common Issues

1. **OAuth Errors**: Check that your Shopify API credentials and redirect URLs are correctly set
2. **Data Not Loading**: Verify your store is active and API scopes are properly set
3. **Visualization Issues**: Check browser console for JavaScript errors

### Debug Tips

-   Enable Laravel debug mode in development
-   Use Laravel Telescope for request/response monitoring
-   Check Shopify API request logs

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For questions or issues, please create a GitHub issue or contact support@yourcompany.com.
