<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Exports\ContractExporter;
use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContracts extends ListRecords
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->label('Exporteren')
                ->exporter(ContractExporter::class)
                ->color('gray'),
            Actions\CreateAction::make(),
        ];
    }
}
