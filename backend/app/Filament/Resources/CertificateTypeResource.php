<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateTypeResource\Pages;
use App\Models\CertificateType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateTypeResource extends Resource
{
    protected static ?string $model = CertificateType::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Certificering';

    protected static ?string $modelLabel = 'Certificaattype';

    protected static ?string $pluralModelLabel = 'Certificaattypes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('Code')->required()->unique(ignoreRecord: true)->maxLength(50),
            Forms\Components\TextInput::make('name')->label('Naam')->required()->maxLength(255),
            Forms\Components\TextInput::make('category')->label('Categorie')->maxLength(100)
                ->placeholder('bijv. STCW, IMO, ISPS'),
            Forms\Components\Toggle::make('requires_expiry')->label('Heeft vervaldatum')->default(true),
            Forms\Components\TextInput::make('default_validity_months')->label('Standaard geldigheid (maanden)')->numeric(),
            Forms\Components\Toggle::make('is_active')->label('Actief')->default(true),
            Forms\Components\Textarea::make('description')->label('Omschrijving')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Naam')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->label('Categorie')->searchable(),
                Tables\Columns\IconColumn::make('requires_expiry')->label('Verloopt')->boolean(),
                Tables\Columns\TextColumn::make('default_validity_months')->label('Geldigheid')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} mnd" : '—'),
                Tables\Columns\IconColumn::make('is_active')->label('Actief')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Actief'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateTypes::route('/'),
            'create' => Pages\CreateCertificateType::route('/create'),
            'edit' => Pages\EditCertificateType::route('/{record}/edit'),
        ];
    }
}
