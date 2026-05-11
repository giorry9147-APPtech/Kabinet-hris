<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalaryGrade extends Model
{
    use LogsActivity;

    protected $fillable = [
        'schaal',
        'trede',
        'code',
        'base_amount',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function salaryAssignments(): HasMany
    {
        return $this->hasMany(SalaryAssignment::class);
    }

    public function getLabelAttribute(): string
    {
        return "Schaal {$this->schaal} - Trede {$this->trede}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
