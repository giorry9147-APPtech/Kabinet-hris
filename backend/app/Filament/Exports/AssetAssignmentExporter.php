<?php

namespace App\Filament\Exports;

use App\Models\AssetAssignment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AssetAssignmentExporter extends Exporter
{
    protected static ?string $model = AssetAssignment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('asset.asset_code')->label('Asset-code'),
            ExportColumn::make('asset.name')->label('Asset'),
            ExportColumn::make('asset.category')->label('Categorie'),
            ExportColumn::make('asset.serial_number')->label('Serienummer'),
            ExportColumn::make('employee.employee_number')->label('Personeelsnummer'),
            ExportColumn::make('employee.last_name')->label('Achternaam medewerker'),
            ExportColumn::make('employee.first_name')->label('Voornaam medewerker'),
            ExportColumn::make('employee.currentPosition.orgUnit.name')->label('Afdeling'),
            ExportColumn::make('assigned_at')->label('Vanaf'),
            ExportColumn::make('returned_at')->label('Geretourneerd'),
            ExportColumn::make('condition_at_assignment')->label('Staat bij toewijzing'),
            ExportColumn::make('condition_at_return')->label('Staat bij retour'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Asset-export gereed: '.number_format($export->successful_rows).' regels.';
    }
}
