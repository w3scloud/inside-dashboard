<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WidgetTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'description',
        'icon',
        'default_size',
        'min_size',
        'max_size',
        'default_config',
        'available_data_sources',
        'supported_chart_types',
        'is_active',
    ];

    protected $casts = [
        'default_size' => 'array',
        'min_size' => 'array',
        'max_size' => 'array',
        'default_config' => 'array',
        'available_data_sources' => 'array',
        'supported_chart_types' => 'array',
        'is_active' => 'boolean',
    ];
}
