<?php

namespace App\Filament\Exports;

use App\Models\Contract;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ContractExporter extends Exporter
{
    protected static ?string $model = Contract::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('contract_number')->label('Contractnr'),
            ExportColumn::make('employee.employee_number')->label('Personeelsnr'),
            ExportColumn::make('employee.last_name')->label('Achternaam'),
            ExportColumn::make('employee.first_name')->label('Voornaam'),
            ExportColumn::make('type')->label('Type')
                ->state(fn (Contract $r) => Contract::TYPES[$r->type] ?? $r->type),
            ExportColumn::make('title')->label('Omschrijving'),
            ExportColumn::make('start_date')->label('Vanaf'),
            ExportColumn::make('end_date')->label('Tot'),
            ExportColumn::make('signed_at')->label('Getekend'),
            ExportColumn::make('notice_period_days')->label('Opzegtermijn (dagen)'),
            ExportColumn::make('monthly_amount')->label('Maandbedrag'),
            ExportColumn::make('currency')->label('Valuta'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('expiry_status')->label('Verloopstatus')
                ->state(function (Contract $record): string {
                    if (! $record->end_date) return 'onbepaalde tijd';
                    $end = Carbon::parse($record->end_date);
                    if ($end->isPast()) return 'VERLOPEN';
                    if ($end->lte(now()->addDays(30))) return 'verloopt < 30d';
                    if ($end->lte(now()->addDays(90))) return 'verloopt < 90d';
                    return 'geldig';
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Contracten-export gereed: '.number_format($export->successful_rows).' regels.';
    }
}
