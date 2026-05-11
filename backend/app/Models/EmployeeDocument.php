<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EmployeeDocument extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'employee_id',
        'title',
        'category',
        'notes',
        'status',
        'decided_by',
        'decided_at',
        'decision_notes',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public const CATEGORIES = [
        'id_copy' => 'ID-kopie',
        'diploma' => 'Diploma',
        'contract' => 'Contract',
        'medical' => 'Medisch attest',
        'tax' => 'Belasting/loonbelasting',
        'other' => 'Overig',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'category', 'status', 'decided_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
