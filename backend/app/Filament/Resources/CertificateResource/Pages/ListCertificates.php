<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Filament\Exports\CertificateExporter;
use App\Filament\Resources\CertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->label('Exporteren')
                ->exporter(CertificateExporter::class)
                ->color('gray'),
            Actions\CreateAction::make(),
        ];
    }
}
