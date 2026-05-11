<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CertificateType extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'category',
        'requires_expiry',
        'default_validity_months',
        'is_active',
        'description',
    ];

    protected $casts = [
        'requires_expiry' => 'boolean',
        'is_active' => 'boolean',
        'default_validity_months' => 'integer',
    ];

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
