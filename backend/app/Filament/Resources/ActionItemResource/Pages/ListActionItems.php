<?php

namespace App\Filament\Resources\ActionItemResource\Pages;

use App\Filament\Resources\ActionItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActionItems extends ListRecords
{
    protected static string $resource = ActionItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nieuwe werkafspraak'),
        ];
    }
}
