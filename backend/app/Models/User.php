<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasApiTokens, HasRoles, LogsActivity, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasAnyRole([
            'super_admin',
            'hr_manager',
            'hr_admin',
            'dept_head',
            'finance',
        ]);
    }

    /**
     * Returns the org-unit IDs this user is allowed to view data for.
     * - super_admin / hr_manager / hr_admin / finance: all units
     * - dept_head: own unit + all descendants (recursive)
     * - other (employee): empty (no admin scope; portal uses /api/me)
     */
    public function accessibleOrgUnitIds(): array
    {
        if ($this->hasAnyRole(['super_admin', 'hr_manager', 'hr_admin', 'finance'])) {
            return OrgUnit::pluck('id')->all();
        }

        if ($this->hasRole('dept_head') && $this->employee?->currentPosition?->org_unit_id) {
            $rootId = $this->employee->currentPosition->org_unit_id;
            return self::collectDescendantOrgIds($rootId);
        }

        return [];
    }

    public function isDataScoped(): bool
    {
        return $this->hasRole('dept_head')
            && ! $this->hasAnyRole(['super_admin', 'hr_manager', 'hr_admin', 'finance']);
    }

    private static function collectDescendantOrgIds(int $rootId): array
    {
        $ids = [$rootId];
        $stack = [$rootId];
        while ($stack !== []) {
            $children = OrgUnit::whereIn('parent_id', $stack)->pluck('id')->all();
            if ($children === []) break;
            $ids = array_merge($ids, $children);
            $stack = $children;
        }
        return $ids;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'employee_id', 'is_active', 'email_verified_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
