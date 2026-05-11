<?php

namespace App\Filament\Resources\SalaryAssignmentResource\Pages;

use App\Filament\Resources\SalaryAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalaryAssignment extends EditRecord
{
    protected static string $resource = SalaryAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
