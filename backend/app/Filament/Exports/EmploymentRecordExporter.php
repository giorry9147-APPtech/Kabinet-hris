<?php

namespace App\Filament\Exports;

use App\Models\EmploymentRecord;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EmploymentRecordExporter extends Exporter
{
    protected static ?string $model = EmploymentRecord::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('employee.employee_number')->label('Personeelsnummer'),
            ExportColumn::make('employee.last_name')->label('Achternaam'),
            ExportColumn::make('employee.first_name')->label('Voornaam'),
            ExportColumn::make('position.title')->label('Functie'),
            ExportColumn::make('position.orgUnit.name')->label('Afdeling'),
            ExportColumn::make('start_date')->label('Vanaf'),
            ExportColumn::make('end_date')->label('T/m'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('reason')->label('Reden'),
            ExportColumn::make('notes')->label('Notities'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Mutatie-export gereed: '.number_format($export->successful_rows).' regels.';
    }
}
