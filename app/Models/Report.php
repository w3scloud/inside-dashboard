<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
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
        'type',
        'config',
        'schedule_enabled',
        'schedule_frequency',
        'schedule_time',
        'schedule_day',
        'schedule_recipients',
        'last_generated_at',
        'output_format',
        'filters',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'schedule_enabled' => 'boolean',
        'schedule_recipients' => 'array',
        'last_generated_at' => 'datetime',
        'filters' => 'array',
    ];

    /**
     * Get the store that owns the report.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Check if the report is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->schedule_enabled;
    }

    /**
     * Update the last generated timestamp.
     */
    public function updateLastGenerated(): self
    {
        $this->update(['last_generated_at' => now()]);

        return $this;
    }

    /**
     * Enable scheduling for this report.
     *
     * @param  string  $frequency  daily|weekly|monthly
     * @param  string  $time  HH:MM format
     * @param  int|null  $day  Day of week (0-6) or day of month (1-31)
     * @param  array  $recipients  Array of email addresses
     */
    public function enableSchedule(
        string $frequency,
        string $time,
        ?int $day = null,
        array $recipients = []
    ): self {
        $this->update([
            'schedule_enabled' => true,
            'schedule_frequency' => $frequency,
            'schedule_time' => $time,
            'schedule_day' => $day,
            'schedule_recipients' => $recipients,
        ]);

        return $this;
    }

    /**
     * Disable scheduling for this report.
     */
    public function disableSchedule(): self
    {
        $this->update(['schedule_enabled' => false]);

        return $this;
    }

    /**
     * Add recipients to the schedule.
     */
    public function addRecipients(array $emails): self
    {
        $recipients = $this->schedule_recipients ?? [];
        $newRecipients = array_unique(array_merge($recipients, $emails));

        $this->update(['schedule_recipients' => $newRecipients]);

        return $this;
    }

    /**
     * Remove a recipient from the schedule.
     */
    public function removeRecipient(string $email): self
    {
        $recipients = $this->schedule_recipients ?? [];
        $filteredRecipients = array_filter($recipients, function ($recipient) use ($email) {
            return $recipient !== $email;
        });

        $this->update(['schedule_recipients' => array_values($filteredRecipients)]);

        return $this;
    }

    /**
     * Get the cron expression for the schedule.
     */
    public function getCronExpression(): ?string
    {
        if (! $this->schedule_enabled) {
            return null;
        }

        $time = $this->schedule_time ?? '00:00';
        [$hour, $minute] = explode(':', $time);

        switch ($this->schedule_frequency) {
            case 'daily':
                return "{$minute} {$hour} * * *";
            case 'weekly':
                $day = $this->schedule_day ?? 1;

                return "{$minute} {$hour} * * {$day}";
            case 'monthly':
                $day = $this->schedule_day ?? 1;

                return "{$minute} {$hour} {$day} * *";
            default:
                return null;
        }
    }

    /**
     * Update report configuration.
     */
    public function updateConfig(array $config): self
    {
        $this->update([
            'config' => array_merge($this->config ?? [], $config),
        ]);

        return $this;
    }

    /**
     * Update report filters.
     */
    public function updateFilters(array $filters): self
    {
        $this->update([
            'filters' => array_merge($this->filters ?? [], $filters),
        ]);

        return $this;
    }

    /**
     * Scope a query to only include scheduled reports.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled($query)
    {
        return $query->where('schedule_enabled', true);
    }

    /**
     * Scope a query to filter by report type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by generation time.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGeneratedWithin($query, int $days)
    {
        return $query->where('last_generated_at', '>=', now()->subDays($days));
    }
}
