<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Position extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'org_unit_id',
        'title',
        'code',
        'description',
        'vacancies_count',
        'status',
    ];

    protected $casts = [
        'vacancies_count' => 'integer',
    ];

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function currentEmployees(): HasMany
    {
        return $this->hasMany(Employee::class, 'current_position_id');
    }

    public function employmentRecords(): HasMany
    {
        return $this->hasMany(EmploymentRecord::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['org_unit_id', 'title', 'code', 'status', 'vacancies_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
