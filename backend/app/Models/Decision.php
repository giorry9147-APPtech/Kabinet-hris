<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Decision extends Model implements \Spatie\MediaLibrary\HasMedia
{
    use \Spatie\MediaLibrary\InteractsWithMedia, LogsActivity;

    public const PRIORITIES = [
        'low' => 'Laag',
        'normal' => 'Normaal',
        'high' => 'Hoog',
        'urgent' => 'Urgent',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In uitvoering',
        'completed' => 'Uitgevoerd',
        'postponed' => 'Uitgesteld',
        'cancelled' => 'Vervallen',
    ];

    protected $fillable = [
        'decision_number',
        'meeting_id',
        'subject',
        'decision_text',
        'decided_at',
        'responsible_employee_id',
        'deadline',
        'priority',
        'status',
        'notes',
    ];

    protected $casts = [
        'decided_at' => 'date',
        'deadline' => 'date',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class);
    }

    public function isOverdue(): bool
    {
        return $this->deadline !== null
            && $this->deadline->isPast()
            && ! in_array($this->status, ['completed', 'cancelled']);
    }

    public function dueWithinDays(int $days): bool
    {
        if ($this->deadline === null) {
            return false;
        }

        return $this->deadline->lte(Carbon::now()->addDays($days));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
