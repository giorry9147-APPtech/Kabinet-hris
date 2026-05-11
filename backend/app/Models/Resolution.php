<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Resolution extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    public const CATEGORIES = [
        'benoeming' => 'Benoeming',
        'ontheffing' => 'Ontheffing',
        'mandaat' => 'Mandaat / volmacht',
        'commissie' => 'Instelling commissie',
        'beleid' => 'Beleidsbeschikking',
        'detachering' => 'Detachering',
        'bezoldiging' => 'Bezoldiging / toelage',
        'overig' => 'Overig',
    ];

    public const STATUSES = [
        'active' => 'Van kracht',
        'expiring' => 'Verloopt binnenkort',
        'expired' => 'Vervallen',
        'revoked' => 'Ingetrokken',
        'superseded' => 'Vervangen',
    ];

    protected $fillable = [
        'resolution_number',
        'subject',
        'category',
        'employee_id',
        'org_unit_id',
        'signed_at',
        'effective_from',
        'expires_at',
        'status',
        'signed_by',
        'summary',
        'notes',
    ];

    protected $casts = [
        'signed_at' => 'date',
        'effective_from' => 'date',
        'expires_at' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function expiresWithinDays(int $days): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->lte(Carbon::now()->addDays($days));
    }

    public function isOpenEnded(): bool
    {
        return $this->expires_at === null;
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
