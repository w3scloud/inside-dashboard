<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'shop_domain',
        'access_token',
        'scopes',
        'name',
        'email',
        'plan_name',
        'plan_display_name',
        'shop_owner',
        'myshopify_domain',
        'money_format',
        'currency',
        'timezone',
        'is_active',
        'installed_at',
        'uninstalled_at',
        'access_scopes',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scopes' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'installed_at' => 'datetime',
        'uninstalled_at' => 'datetime',
    ];

    /**
     * Get the user that owns the store.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the dashboards for the store.
     */
    public function dashboards(): HasMany
    {
        return $this->hasMany(Dashboard::class);
    }

    /**
     * Get the reports for the store.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Check if the store is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Update store from Shopify data.
     */
    public function updateFromShopify(array $shopData): self
    {
        $this->update([
            'name' => $shopData['name'] ?? $this->name,
            'email' => $shopData['email'] ?? $this->email,
            'plan_name' => $shopData['plan_name'] ?? $this->plan_name,
            'plan_display_name' => $shopData['plan_display_name'] ?? $this->plan_display_name,
            'shop_owner' => $shopData['shop_owner'] ?? $this->shop_owner,
            'myshopify_domain' => $shopData['myshopify_domain'] ?? $this->myshopify_domain,
            'money_format' => $shopData['money_format'] ?? $this->money_format,
            'currency' => $shopData['currency'] ?? $this->currency,
            'timezone' => $shopData['timezone'] ?? $this->timezone,
        ]);

        return $this;
    }

    /**
     * Mark the store as active.
     */
    public function markAsActive(): self
    {
        $this->update([
            'is_active' => true,
            'installed_at' => now(),
            'uninstalled_at' => null,
        ]);

        return $this;
    }

    /**
     * Mark the store as inactive.
     */
    public function markAsInactive(): self
    {
        $this->update([
            'is_active' => false,
            'uninstalled_at' => now(),
            'access_token' => null,
        ]);

        return $this;
    }

    /**
     * Scope a query to only include active stores.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
