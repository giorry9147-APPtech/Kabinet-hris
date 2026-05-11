<?php

namespace App\Filament\Resources\AssetRequestResource\Pages;

use App\Filament\Resources\AssetRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssetRequest extends EditRecord
{
    protected static string $resource = AssetRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
