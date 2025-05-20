<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Widget extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dashboard_id',
        'title',
        'type',
        'chart_type',
        'data_source',
        'size',
        'position',
        'config',
        'filters',
        'settings',
        'last_refreshed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'array',
        'position' => 'array',
        'config' => 'array',
        'filters' => 'array',
        'settings' => 'array',
        'last_refreshed_at' => 'datetime',
    ];

    /**
     * Get the dashboard that owns the widget.
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * Update the last refreshed timestamp.
     */
    public function updateLastRefreshed(): self
    {
        $this->update(['last_refreshed_at' => now()]);

        return $this;
    }

    /**
     * Update widget configuration.
     */
    public function updateConfig(array $config): self
    {
        $this->update([
            'config' => array_merge($this->config ?? [], $config),
        ]);

        return $this;
    }

    /**
     * Update widget filters.
     */
    public function updateFilters(array $filters): self
    {
        $this->update([
            'filters' => array_merge($this->filters ?? [], $filters),
        ]);

        return $this;
    }

    /**
     * Update widget position.
     */
    public function updatePosition(int $x, int $y): self
    {
        $this->update([
            'position' => [
                'x' => $x,
                'y' => $y,
            ],
        ]);

        return $this;
    }

    /**
     * Update widget size.
     */
    public function updateSize(int $width, int $height): self
    {
        $this->update([
            'size' => [
                'w' => $width,
                'h' => $height,
            ],
        ]);

        return $this;
    }

    /**
     * Scope a query to filter by widget type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by chart type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithChartType($query, string $chartType)
    {
        return $query->where('chart_type', $chartType);
    }

    /**
     * Scope a query to filter by data source.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithDataSource($query, string $dataSource)
    {
        return $query->where('data_source', $dataSource);
    }
}
