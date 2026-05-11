<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\OrgUnit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class HeadcountByOrgUnitChart extends ChartWidget
{
    protected static ?string $heading = 'Personeel per afdeling';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $user = auth()->user();
        $allUnits = OrgUnit::all();
        $childrenByParent = $allUnits->groupBy('parent_id');

        $accessibleIds = $user?->isDataScoped() ? $user->accessibleOrgUnitIds() : null;

        $afdelingen = $allUnits
            ->where('type', 'afdeling')
            ->when($accessibleIds, fn ($c) => $c->whereIn('id', $accessibleIds))
            ->sortBy('name')
            ->values();

        $labels = [];
        $counts = [];
        $countedUnitIds = collect();

        foreach ($afdelingen as $unit) {
            $descendantIds = $this->collectDescendantIds($unit->id, $childrenByParent);
            $countedUnitIds = $countedUnitIds->merge($descendantIds);

            $count = Employee::where('status', 'active')
                ->whereHas('currentPosition', fn ($q) => $q->whereIn('org_unit_id', $descendantIds))
                ->count();

            $labels[] = str_replace('Afdeling ', '', $unit->name);
            $counts[] = $count;
        }

        // Directie-level positions (not under any afdeling)
        $directie = $allUnits->where('type', 'directie')
            ->when($accessibleIds, fn ($c) => $c->whereIn('id', $accessibleIds));
        if ($directie->isNotEmpty()) {
            $directieIds = $directie->pluck('id');
            $countedUnitIds = $countedUnitIds->merge($directieIds);
            $directieCount = Employee::where('status', 'active')
                ->whereHas('currentPosition', fn ($q) => $q->whereIn('org_unit_id', $directieIds))
                ->count();
            if ($directieCount > 0) {
                $labels[] = 'Directie';
                $counts[] = $directieCount;
            }
        }

        // Active employees without a current position (only show in unscoped admin view)
        if (! $accessibleIds) {
            $unassigned = Employee::where('status', 'active')
                ->whereNull('current_position_id')
                ->count();
            if ($unassigned > 0) {
                $labels[] = 'Niet toegewezen';
                $counts[] = $unassigned;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Aantal medewerkers',
                    'data' => $counts,
                    'backgroundColor' => '#1F5E3A',
                    'borderColor' => '#1F5E3A',
                ],
            ],
            'labels' => $labels,
        ];
    }

    private function collectDescendantIds(int $rootId, Collection $childrenByParent): Collection
    {
        $ids = collect([$rootId]);
        $queue = [$rootId];
        while ($queue) {
            $current = array_shift($queue);
            foreach ($childrenByParent->get($current, collect()) as $child) {
                $ids->push($child->id);
                $queue[] = $child->id;
            }
        }
        return $ids;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]]],
        ];
    }
}
