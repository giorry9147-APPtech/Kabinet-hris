<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Employee extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'marital_status',
        'nationality',
        'national_id',
        'passport_number',
        'email',
        'phone',
        'address',
        'current_position_id',
        'status',
        'joined_at',
        'exited_at',
        'exit_reason',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joined_at' => 'date',
        'exited_at' => 'date',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} ".($this->middle_name ? "{$this->middle_name} " : '')."{$this->last_name}");
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function currentPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'current_position_id');
    }

    public function employmentRecords(): HasMany
    {
        return $this->hasMany(EmploymentRecord::class)->latest('start_date');
    }

    public function currentEmployment(): HasOne
    {
        return $this->hasOne(EmploymentRecord::class)
            ->where('status', 'active')
            ->latestOfMany('start_date');
    }

    public function salaryAssignments(): HasMany
    {
        return $this->hasMany(SalaryAssignment::class)->latest('start_date');
    }

    public function currentSalary(): HasOne
    {
        return $this->hasOne(SalaryAssignment::class)
            ->whereNull('end_date')
            ->latestOfMany('start_date');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class)->latest('issued_at');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class)->latest('start_date');
    }

    public function assetAssignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class)->latest('assigned_at');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->latest('created_at');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
        $this->addMediaCollection('documents');
        $this->addMediaCollection('contracts');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 200, 200)
            ->nonQueued()
            ->performOnCollections('avatar');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'employee_number', 'first_name', 'last_name', 'email', 'phone',
                'current_position_id', 'status', 'joined_at', 'exited_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
