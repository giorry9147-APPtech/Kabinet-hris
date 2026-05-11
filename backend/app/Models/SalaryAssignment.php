<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalaryAssignment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'employee_id',
        'salary_grade_id',
        'base_amount',
        'allowances',
        'currency',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'allowances' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class);
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->base_amount + (float) $this->allowances;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
