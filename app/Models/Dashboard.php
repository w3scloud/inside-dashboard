<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dashboard extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'description',
        'is_default',
        'layout',
        'settings',
        'last_viewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'layout' => 'array',
        'settings' => 'array',
        'is_default' => 'boolean',
        'last_viewed_at' => 'datetime',
    ];

    /**
     * Get the store that owns the dashboard.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the widgets for the dashboard.
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class);
    }

    /**
     * Mark this dashboard as the default for the store.
     */
    public function markAsDefault(): self
    {
        // First, unmark any other dashboards as default
        Dashboard::where('store_id', $this->store_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Then mark this one as default
        $this->update(['is_default' => true]);

        return $this;
    }

    /**
     * Update the last viewed timestamp.
     */
    public function updateLastViewed(): self
    {
        $this->update(['last_viewed_at' => now()]);

        return $this;
    }

    /**
     * Get widgets from the layout.
     */
    public function getWidgetsFromLayout(): array
    {
        return $this->layout ?? [];
    }

    /**
     * Add a widget to the layout.
     */
    public function addWidget(array $widget): self
    {
        $layout = $this->layout ?? [];
        $layout[] = $widget;

        $this->update(['layout' => $layout]);

        return $this;
    }

    /**
     * Update a widget in the layout.
     */
    public function updateWidget(string $widgetId, array $updatedWidget): self
    {
        $layout = $this->layout ?? [];

        foreach ($layout as $index => $widget) {
            if ($widget['id'] === $widgetId) {
                $layout[$index] = array_merge($widget, $updatedWidget);
                break;
            }
        }

        $this->update(['layout' => $layout]);

        return $this;
    }

    /**
     * Remove a widget from the layout.
     */
    public function removeWidget(string $widgetId): self
    {
        $layout = $this->layout ?? [];

        $layout = array_filter($layout, function ($widget) use ($widgetId) {
            return $widget['id'] !== $widgetId;
        });

        $this->update(['layout' => array_values($layout)]);

        return $this;
    }

    /**
     * Scope a query to only include the default dashboard.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to order by last viewed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByLastViewed($query)
    {
        return $query->orderBy('last_viewed_at', 'desc');
    }
}
