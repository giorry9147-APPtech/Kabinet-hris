<?php

namespace App\Filament\Concerns;

use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;

/**
 * Restricts a Filament resource's query to records linked to the
 * current user's accessible org units. Used by dept_head role.
 *
 * Implementing resource must define static method:
 *   protected static function applyOrgUnitScope(Builder $query, array $orgUnitIds): Builder
 */
trait ScopesToOrgUnit
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || ! $user->isDataScoped()) {
            return $query;
        }

        $orgUnitIds = $user->accessibleOrgUnitIds();

        if ($orgUnitIds === []) {
            return $query->whereRaw('1 = 0'); // no access
        }

        return static::applyOrgUnitScope($query, $orgUnitIds);
    }

    protected static function positionIdsForOrgUnits(array $orgUnitIds): array
    {
        return Position::whereIn('org_unit_id', $orgUnitIds)->pluck('id')->all();
    }
}
