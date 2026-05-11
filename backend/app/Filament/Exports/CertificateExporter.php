<?php

namespace App\Filament\Exports;

use App\Models\Certificate;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CertificateExporter extends Exporter
{
    protected static ?string $model = Certificate::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('employee.employee_number')->label('Personeelsnr'),
            ExportColumn::make('employee.last_name')->label('Achternaam'),
            ExportColumn::make('employee.first_name')->label('Voornaam'),
            ExportColumn::make('certificateType.name')->label('Type'),
            ExportColumn::make('certificateType.category')->label('Categorie'),
            ExportColumn::make('number')->label('Certificaatnummer'),
            ExportColumn::make('issuer')->label('Uitgever'),
            ExportColumn::make('issued_at')->label('Uitgegeven op'),
            ExportColumn::make('expires_at')->label('Vervalt op'),
            ExportColumn::make('expiry_status')->label('Status')
                ->state(function (Certificate $record): string {
                    if (! $record->expires_at) return 'geen vervaldatum';
                    $expires = Carbon::parse($record->expires_at);
                    if ($expires->isPast()) return 'VERLOPEN';
                    if ($expires->lte(now()->addDays(90))) return 'verloopt < 90d';
                    return 'geldig';
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Certificaten-export gereed: '.number_format($export->successful_rows).' regels.';
    }
}
