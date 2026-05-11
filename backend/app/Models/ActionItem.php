<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ActionItem extends Model
{
    use LogsActivity;

    public const PRIORITIES = [
        'low' => 'Laag',
        'normal' => 'Normaal',
        'high' => 'Hoog',
        'urgent' => 'Urgent',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In uitvoering',
        'blocked' => 'Geblokkeerd',
        'done' => 'Afgerond',
        'cancelled' => 'Geannuleerd',
    ];

    protected $fillable = [
        'meeting_id',
        'decision_id',
        'title',
        'description',
        'assignee_employee_id',
        'due_date',
        'priority',
        'status',
        'completed_at',
        'completion_note',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_employee_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! in_array($this->status, ['done', 'cancelled']);
    }

    public function dueWithinDays(int $days): bool
    {
        if ($this->due_date === null) {
            return false;
        }

        return $this->due_date->lte(Carbon::now()->addDays($days));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
