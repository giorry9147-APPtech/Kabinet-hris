<?php

namespace App\Filament\Resources\SalaryAssignmentResource\Pages;

use App\Filament\Resources\SalaryAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalaryAssignments extends ListRecords
{
    protected static string $resource = SalaryAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
