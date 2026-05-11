<?php

namespace App\Filament\Exports;

use App\Models\SalaryAssignment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SalaryAssignmentExporter extends Exporter
{
    protected static ?string $model = SalaryAssignment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('employee.employee_number')->label('Personeelsnummer'),
            ExportColumn::make('employee.last_name')->label('Achternaam'),
            ExportColumn::make('employee.first_name')->label('Voornaam'),
            ExportColumn::make('employee.currentPosition.orgUnit.name')->label('Afdeling'),
            ExportColumn::make('employee.currentPosition.title')->label('Functie'),
            ExportColumn::make('salaryGrade.schaal')->label('Schaal'),
            ExportColumn::make('salaryGrade.trede')->label('Trede'),
            ExportColumn::make('base_amount')->label('Basis (SRD)'),
            ExportColumn::make('allowances')->label('Toelagen (SRD)'),
            ExportColumn::make('currency')->label('Valuta'),
            ExportColumn::make('start_date')->label('Vanaf'),
            ExportColumn::make('end_date')->label('T/m'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Salarisexport gereed: '.number_format($export->successful_rows).' regels.';
    }
}
