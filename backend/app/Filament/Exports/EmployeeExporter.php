<?php

namespace App\Filament\Exports;

use App\Models\Employee;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EmployeeExporter extends Exporter
{
    protected static ?string $model = Employee::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('employee_number')->label('Personeelsnummer'),
            ExportColumn::make('last_name')->label('Achternaam'),
            ExportColumn::make('first_name')->label('Voornaam'),
            ExportColumn::make('middle_name')->label('Tussenvoegsel'),
            ExportColumn::make('email')->label('E-mail'),
            ExportColumn::make('phone')->label('Telefoon'),
            ExportColumn::make('gender')->label('Geslacht'),
            ExportColumn::make('date_of_birth')->label('Geboortedatum'),
            ExportColumn::make('nationality')->label('Nationaliteit'),
            ExportColumn::make('national_id')->label('ID-nummer'),
            ExportColumn::make('currentPosition.title')->label('Functie'),
            ExportColumn::make('currentPosition.orgUnit.name')->label('Afdeling'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('joined_at')->label('In dienst sinds'),
            ExportColumn::make('exited_at')->label('Uit dienst per'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export gereed: '.number_format($export->successful_rows).' regels geëxporteerd.';
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' regels mislukt.';
        }
        return $body;
    }
}
