<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Contract extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    public const TYPES = [
        'vast' => 'Vast (onbepaalde tijd)',
        'bepaald' => 'Bepaalde tijd',
        'detachering' => 'Detachering',
        'tijdelijk' => 'Tijdelijke aanstelling',
        'stage' => 'Stage',
        'consultancy' => 'Consultancy',
    ];

    public const STATUSES = [
        'active' => 'Actief',
        'expiring' => 'Verloopt binnenkort',
        'expired' => 'Verlopen',
        'terminated' => 'Beëindigd',
        'renewed' => 'Verlengd',
    ];

    protected $fillable = [
        'employee_id',
        'contract_number',
        'type',
        'title',
        'start_date',
        'end_date',
        'signed_at',
        'monthly_amount',
        'currency',
        'status',
        'notice_period_days',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'date',
        'monthly_amount' => 'decimal:2',
        'notice_period_days' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isExpired(): bool
    {
        return $this->end_date !== null && $this->end_date->isPast();
    }

    public function expiresWithinDays(int $days): bool
    {
        if ($this->end_date === null) {
            return false;
        }

        return $this->end_date->lte(Carbon::now()->addDays($days));
    }

    public function noticeDeadlineDate(): ?Carbon
    {
        if (! $this->end_date || ! $this->notice_period_days) {
            return null;
        }

        return $this->end_date->copy()->subDays((int) $this->notice_period_days);
    }

    public function isNoticeDeadlinePassed(): bool
    {
        $deadline = $this->noticeDeadlineDate();
        return $deadline !== null && $deadline->isPast() && ! $this->isExpired();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
