<?php

namespace App\Filament\Resources\EmploymentRecordResource\Pages;

use App\Filament\Resources\EmploymentRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmploymentRecord extends EditRecord
{
    protected static string $resource = EmploymentRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
