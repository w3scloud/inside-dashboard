<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Settings/NoStore');
        }

        // Get app settings
        $settings = [
            'theme' => $store->getSetting('theme', 'light'),
            'dashboard_refresh_interval' => $store->getSetting('dashboard_refresh_interval', 0),
            'default_date_range' => $store->getSetting('default_date_range', 30),
            'email_notifications' => $store->getSetting('email_notifications', true),
        ];

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
                'plan' => $store->plan_name,
                'owner' => $store->shop_owner,
                'email' => $store->email,
                'timezone' => $store->timezone,
                'currency' => $store->currency,
                'installed_at' => $store->installed_at ? $store->installed_at->format('Y-m-d H:i:s') : null,
            ],
        ]);
    }

    /**
     * Update the settings.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'required|string|in:light,dark,auto',
            'dashboard_refresh_interval' => 'required|integer|min:0',
            'default_date_range' => 'required|integer|min:1|max:365',
            'email_notifications' => 'required|boolean',
        ]);

        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('settings.index')
                ->with('error', 'No active store found.');
        }

        // Update settings in the metadata
        $metadata = $store->metadata ?? [];
        $settings = $metadata['settings'] ?? [];

        $settings = array_merge($settings, [
            'theme' => $validated['theme'],
            'dashboard_refresh_interval' => $validated['dashboard_refresh_interval'],
            'default_date_range' => $validated['default_date_range'],
            'email_notifications' => $validated['email_notifications'],
        ]);

        $metadata['settings'] = $settings;
        $store->update(['metadata' => $metadata]);

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Display the account settings page.
     *
     * @return \Inertia\Response
     */
    public function account()
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Settings/NoStore');
        }

        return Inertia::render('Settings/Account', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
                'plan' => $store->plan_name,
                'owner' => $store->shop_owner,
                'email' => $store->email,
            ],
        ]);
    }

    /**
     * Update the user's account settings.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAccount(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'current_password' => 'required_with:password|current_password',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Update user details
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        // Update password if provided
        if (isset($validated['password'])) {
            $userData['password'] = bcrypt($validated['password']);
        }

        $user->update($userData);

        // If email was changed, mark it as unverified
        if ($user->wasChanged('email')) {
            $user->email_verified_at = null;
            $user->sendEmailVerificationNotification();
            $user->save();
        }

        return redirect()->route('settings.account')
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Display the webhook settings page.
     *
     * @return \Inertia\Response
     */
    public function webhooks()
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Settings/NoStore');
        }

        // Get webhook settings from store metadata
        $metadata = $store->metadata ?? [];
        $webhookSettings = $metadata['webhooks'] ?? [
            'app_uninstalled' => true,
            'shop_update' => true,
            'products_update' => true,
            'orders_update' => true,
            'customers_update' => true,
            'inventory_update' => true,
        ];

        return Inertia::render('Settings/Webhooks', [
            'webhookSettings' => $webhookSettings,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Update webhook settings.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateWebhooks(Request $request)
    {
        $validated = $request->validate([
            'app_uninstalled' => 'boolean',
            'shop_update' => 'boolean',
            'products_update' => 'boolean',
            'orders_update' => 'boolean',
            'customers_update' => 'boolean',
            'inventory_update' => 'boolean',
        ]);

        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('settings.webhooks')
                ->with('error', 'No active store found.');
        }

        // Update webhook settings in metadata
        $metadata = $store->metadata ?? [];
        $metadata['webhooks'] = $validated;
        $store->update(['metadata' => $metadata]);

        // TODO: Register/unregister webhooks with Shopify based on settings

        return redirect()->route('settings.webhooks')
            ->with('success', 'Webhook settings updated successfully.');
    }

    /**
     * Display the API settings page.
     *
     * @return \Inertia\Response
     */
    public function api()
    {
        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return Inertia::render('Settings/NoStore');
        }

        // Get API settings
        $metadata = $store->metadata ?? [];
        $apiSettings = $metadata['api'] ?? [
            'enabled' => false,
            'read_only' => true,
            'api_key' => null,
        ];

        // Generate API key if not already present
        if (! $apiSettings['api_key']) {
            $apiSettings['api_key'] = bin2hex(random_bytes(16));
            $metadata['api'] = $apiSettings;
            $store->update(['metadata' => $metadata]);
        }

        return Inertia::render('Settings/Api', [
            'apiSettings' => $apiSettings,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->shop_domain,
            ],
        ]);
    }

    /**
     * Update API settings.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateApi(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'read_only' => 'boolean',
            'regenerate_key' => 'boolean',
        ]);

        $user = Auth::user();
        $store = $user->stores()->active()->first();

        if (! $store) {
            return redirect()->route('settings.api')
                ->with('error', 'No active store found.');
        }

        // Update API settings
        $metadata = $store->metadata ?? [];
        $apiSettings = $metadata['api'] ?? [
            'enabled' => false,
            'read_only' => true,
            'api_key' => bin2hex(random_bytes(16)),
        ];

        $apiSettings['enabled'] = $validated['enabled'];
        $apiSettings['read_only'] = $validated['read_only'];

        // Regenerate API key if requested
        if ($validated['regenerate_key'] ?? false) {
            $apiSettings['api_key'] = bin2hex(random_bytes(16));
        }

        $metadata['api'] = $apiSettings;
        $store->update(['metadata' => $metadata]);

        return redirect()->route('settings.api')
            ->with('success', 'API settings updated successfully.');
    }
}
