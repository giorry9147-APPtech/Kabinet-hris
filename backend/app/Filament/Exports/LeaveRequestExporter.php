<?php

namespace App\Filament\Exports;

use App\Models\LeaveRequest;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LeaveRequestExporter extends Exporter
{
    protected static ?string $model = LeaveRequest::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('employee.employee_number')->label('Personeelsnr'),
            ExportColumn::make('employee.last_name')->label('Achternaam'),
            ExportColumn::make('employee.first_name')->label('Voornaam'),
            ExportColumn::make('type')->label('Soort verlof'),
            ExportColumn::make('start_date')->label('Vanaf'),
            ExportColumn::make('end_date')->label('T/m'),
            ExportColumn::make('days_count')->label('Dagen'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('reason')->label('Reden'),
            ExportColumn::make('approver.name')->label('Beslist door'),
            ExportColumn::make('decided_at')->label('Beslist op'),
            ExportColumn::make('decision_reason')->label('Beslissingsreden'),
            ExportColumn::make('created_at')->label('Ingediend op'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Verlof-export gereed: '.number_format($export->successful_rows).' regels.';
    }
}
