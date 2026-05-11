<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\OrgUnit;
use App\Models\Position;
use Filament\Pages\Page;

class Organogram extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationGroup = 'Organisatie';

    protected static string $view = 'filament.pages.organogram';

    protected static ?string $title = 'Organogram MAS';

    protected static ?string $navigationLabel = 'Organogram';

    protected static ?int $navigationSort = 0;

    public function getViewData(): array
    {
        $units = OrgUnit::query()
            ->withCount([
                'positions',
                'positions as occupied_employees_count' => function ($q) {
                    $q->join('employees', 'employees.current_position_id', '=', 'positions.id')
                        ->where('employees.status', 'active')
                        ->whereNull('employees.deleted_at');
                },
            ])
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        $headByUnit = $this->findHeadsByUnit($units->keys()->all());

        $root = $units->firstWhere('parent_id', null);
        $tree = $root ? $this->buildBranch($root, $units, $headByUnit) : null;

        return [
            'tree' => $tree,
            'totals' => [
                'units' => $units->count(),
                'positions' => $units->sum('positions_count'),
                'employees' => $units->sum('occupied_employees_count'),
            ],
        ];
    }

    /**
     * Returns [orgUnitId => ['name', 'title', 'avatar_url', 'initials']] for the head of each unit.
     */
    private function findHeadsByUnit(array $orgUnitIds): array
    {
        $headPositions = Position::query()
            ->whereIn('org_unit_id', $orgUnitIds)
            ->where(function ($q) {
                $q->where('title', 'ILIKE', 'Hoofd %')
                    ->orWhere('title', 'ILIKE', 'Directeur%')
                    ->orWhere('title', 'ILIKE', 'Coordinator%')
                    ->orWhere('title', 'ILIKE', 'Office Manager%');
            })
            ->orderByRaw("CASE WHEN title ILIKE 'Directeur%' THEN 1 WHEN title ILIKE 'Hoofd %' THEN 2 ELSE 3 END")
            ->get(['id', 'org_unit_id', 'title']);

        // pick first head per unit (priority via orderByRaw above)
        $headPosByUnit = [];
        foreach ($headPositions as $pos) {
            if (! isset($headPosByUnit[$pos->org_unit_id])) {
                $headPosByUnit[$pos->org_unit_id] = $pos;
            }
        }

        $employees = Employee::query()
            ->whereIn('current_position_id', collect($headPosByUnit)->pluck('id'))
            ->where('status', 'active')
            ->with('media')
            ->get(['id', 'first_name', 'last_name', 'current_position_id'])
            ->keyBy('current_position_id');

        $result = [];
        foreach ($headPosByUnit as $unitId => $position) {
            $employee = $employees->get($position->id);
            if (! $employee) continue;
            $result[$unitId] = [
                'name' => $employee->full_name,
                'title' => $position->title,
                'avatar_url' => $employee->getFirstMediaUrl('avatar', 'thumb') ?: ($employee->getFirstMediaUrl('avatar') ?: null),
                'initials' => $this->initials($employee->first_name, $employee->last_name),
            ];
        }
        return $result;
    }

    private function initials(string $first, string $last): string
    {
        return strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
    }

    private function buildBranch(OrgUnit $unit, $allUnits, array $headByUnit): array
    {
        $children = $allUnits->where('parent_id', $unit->id)->sortBy('code');

        return [
            'unit' => $unit,
            'head' => $headByUnit[$unit->id] ?? null,
            'children' => $children->map(fn ($child) => $this->buildBranch($child, $allUnits, $headByUnit))->values()->all(),
        ];
    }
}
