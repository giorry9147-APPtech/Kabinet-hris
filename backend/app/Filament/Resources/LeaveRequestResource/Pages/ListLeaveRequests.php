<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Exports\LeaveRequestExporter;
use App\Filament\Resources\LeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveRequests extends ListRecords
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->label('Exporteren')
                ->exporter(LeaveRequestExporter::class)
                ->color('gray'),
            Actions\CreateAction::make(),
        ];
    }
}
