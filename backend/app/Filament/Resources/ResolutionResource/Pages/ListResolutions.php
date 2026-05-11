<?php

namespace App\Filament\Resources\ResolutionResource\Pages;

use App\Filament\Exports\ResolutionExporter;
use App\Filament\Resources\ResolutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResolutions extends ListRecords
{
    protected static string $resource = ResolutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->label('Exporteren')
                ->exporter(ResolutionExporter::class)
                ->color('gray'),
            Actions\CreateAction::make(),
        ];
    }
}
