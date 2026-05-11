<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Exports\EmployeeExporter;
use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->label('Exporteren')
                ->exporter(EmployeeExporter::class)
                ->color('gray'),
            Actions\CreateAction::make(),
        ];
    }
}
