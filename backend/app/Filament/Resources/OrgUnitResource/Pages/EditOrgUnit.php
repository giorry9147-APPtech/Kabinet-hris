<?php

namespace App\Filament\Resources\OrgUnitResource\Pages;

use App\Filament\Resources\OrgUnitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrgUnit extends EditRecord
{
    protected static string $resource = OrgUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
