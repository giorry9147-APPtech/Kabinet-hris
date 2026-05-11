<?php

namespace App\Filament\Exports;

use App\Models\Resolution;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ResolutionExporter extends Exporter
{
    protected static ?string $model = Resolution::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('resolution_number')->label('Resolutienr'),
            ExportColumn::make('subject')->label('Onderwerp'),
            ExportColumn::make('category')->label('Categorie')
                ->state(fn (Resolution $r) => Resolution::CATEGORIES[$r->category] ?? $r->category),
            ExportColumn::make('signed_by')->label('Ondertekend door'),
            ExportColumn::make('signed_at')->label('Getekend op'),
            ExportColumn::make('effective_from')->label('Van kracht vanaf'),
            ExportColumn::make('expires_at')->label('Vervalt op'),
            ExportColumn::make('employee.employee_number')->label('Personeelsnr'),
            ExportColumn::make('employee.last_name')->label('Achternaam medewerker'),
            ExportColumn::make('orgUnit.name')->label('Organisatieonderdeel'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('expiry_status')->label('Verloopstatus')
                ->state(function (Resolution $record): string {
                    if (! $record->expires_at) return 'open-ended';
                    $end = Carbon::parse($record->expires_at);
                    if ($end->isPast()) return 'VERVALLEN';
                    if ($end->lte(now()->addDays(30))) return 'vervalt < 30d';
                    if ($end->lte(now()->addDays(90))) return 'vervalt < 90d';
                    return 'geldig';
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Resoluties-export gereed: '.number_format($export->successful_rows).' regels.';
    }
}
