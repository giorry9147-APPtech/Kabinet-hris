<?php

namespace App\Filament\Widgets;

use App\Models\Certificate;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class CertificateExpiryChart extends ChartWidget
{
    protected static ?string $heading = 'Certificaten — vervalkalender (komende 12 maanden)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $user = auth()->user();
        $query = Certificate::query()->whereNotNull('expires_at');

        if ($user?->isDataScoped()) {
            $orgIds = $user->accessibleOrgUnitIds();
            $query->whereHas('employee.currentPosition', fn ($q) => $q->whereIn('org_unit_id', $orgIds));
        }

        $start = Carbon::now()->startOfMonth();
        $labels = [];
        $counts = [];
        $colors = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $labels[] = $month->translatedFormat('M Y');
            $count = (clone $query)
                ->whereBetween('expires_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count();
            $counts[] = $count;
            $colors[] = $i < 3 ? '#D4A017' : ($i < 6 ? '#f59e0b' : '#1F5E3A');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Aantal vervallend',
                    'data' => $counts,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
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
