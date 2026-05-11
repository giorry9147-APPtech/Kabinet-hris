<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CertificateResource;
use App\Filament\Resources\ContractResource;
use App\Filament\Resources\ResolutionResource;
use App\Models\Certificate;
use App\Models\Contract;
use App\Models\Resolution;
use Carbon\Carbon;
use Filament\Widgets\Widget;

/**
 * Eén-blik overzicht van álle deadlines binnen het Kabinet:
 * certificaten, contracten en resoluties die binnenkort verlopen of al verlopen zijn.
 */
class DeadlineRadarWidget extends Widget
{
    protected static string $view = 'filament.widgets.deadline-radar';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $horizon = Carbon::now()->addDays(180);
        $now = Carbon::now();

        $certs = Certificate::query()
            ->with(['employee:id,first_name,last_name', 'certificateType:id,name'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $horizon)
            ->get()
            ->map(fn (Certificate $c) => [
                'kind' => 'Certificaat',
                'kind_color' => 'bg-emerald-100 text-emerald-800',
                'reference' => $c->number ?: ('#'.$c->id),
                'subject' => trim(($c->employee?->first_name.' '.$c->employee?->last_name).' — '.($c->certificateType?->name ?? '')),
                'deadline' => $c->expires_at,
                'url' => CertificateResource::getUrl('edit', ['record' => $c->id]),
            ]);

        $contracts = Contract::query()
            ->with(['employee:id,first_name,last_name'])
            ->whereNotNull('end_date')
            ->whereNotIn('status', ['expired', 'terminated', 'renewed'])
            ->where('end_date', '<=', $horizon)
            ->get()
            ->map(fn (Contract $c) => [
                'kind' => 'Contract',
                'kind_color' => 'bg-sky-100 text-sky-800',
                'reference' => $c->contract_number,
                'subject' => trim(($c->employee?->first_name.' '.$c->employee?->last_name).' — '.($c->title ?: (Contract::TYPES[$c->type] ?? $c->type))),
                'deadline' => $c->end_date,
                'url' => ContractResource::getUrl('edit', ['record' => $c->id]),
            ]);

        $resolutions = Resolution::query()
            ->whereNotNull('expires_at')
            ->whereNotIn('status', ['expired', 'revoked', 'superseded'])
            ->where('expires_at', '<=', $horizon)
            ->get()
            ->map(fn (Resolution $r) => [
                'kind' => 'Resolutie',
                'kind_color' => 'bg-amber-100 text-amber-800',
                'reference' => $r->resolution_number,
                'subject' => $r->subject,
                'deadline' => $r->expires_at,
                'url' => ResolutionResource::getUrl('edit', ['record' => $r->id]),
            ]);

        $rows = $certs->concat($contracts)->concat($resolutions)
            ->sortBy('deadline')
            ->values()
            ->take(25)
            ->map(function (array $row) use ($now): array {
                $deadline = Carbon::parse($row['deadline']);
                $isPast = $deadline->isPast();
                $diffDays = (int) $now->diffInDays($deadline, false);
                $absDays = abs($diffDays);

                $row['deadline_formatted'] = $deadline->format('d-m-Y');
                $row['days_label'] = $isPast ? "{$absDays} dgn verstreken" : "nog {$absDays} dgn";

                $row['badge_color'] = match (true) {
                    $isPast => 'bg-red-100 text-red-800 ring-red-300',
                    $absDays <= 30 => 'bg-red-50 text-red-700 ring-red-200',
                    $absDays <= 90 => 'bg-amber-50 text-amber-700 ring-amber-200',
                    default => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                };

                return $row;
            });

        return [
            'rows' => $rows,
            'totalCount' => $certs->count() + $contracts->count() + $resolutions->count(),
        ];
    }
}
