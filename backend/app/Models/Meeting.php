<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Meeting extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    public const TYPES = [
        'presidentieel' => 'Overleg met de President',
        'staf' => 'Stafvergadering Kabinet',
        'werkoverleg' => 'Werkoverleg',
        'strategisch' => 'Strategisch overleg',
        'extern' => 'Extern overleg',
        'crisis' => 'Crisisberaad',
        'sollicitatie' => 'Sollicitatie / kennismaking',
        'ander' => 'Overig',
    ];

    public const STATUSES = [
        'planned' => 'Gepland',
        'in_progress' => 'In uitvoering',
        'held' => 'Gehouden',
        'cancelled' => 'Geannuleerd',
        'postponed' => 'Verzet',
    ];

    public const MINUTES_STATUSES = [
        'none' => 'Nog geen notulen',
        'concept' => 'Concept-notulen',
        'final' => 'Definitief vastgesteld',
        'published' => 'Gepubliceerd',
    ];

    public const ATTENDEE_ROLES = [
        'chair' => 'Voorzitter',
        'note_taker' => 'Notulist',
        'participant' => 'Deelnemer',
        'observer' => 'Toehoorder',
    ];

    protected $fillable = [
        'meeting_number',
        'title',
        'type',
        'scheduled_at',
        'duration_minutes',
        'location',
        'chair_employee_id',
        'status',
        'agenda',
        'external_attendees',
        'notes',
        'minutes_status',
        'minutes_content',
        'minutes_signed_by',
        'minutes_finalized_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'minutes_finalized_at' => 'date',
        'duration_minutes' => 'integer',
    ];

    public function chair(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'chair_employee_id');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'meeting_attendees')
            ->withPivot(['role', 'attended'])
            ->withTimestamps();
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class);
    }

    public function isUpcoming(): bool
    {
        return $this->scheduled_at !== null && $this->scheduled_at->isFuture();
    }

    public function isPresidential(): bool
    {
        return $this->type === 'presidentieel';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('minutes_file')->singleFile();
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
