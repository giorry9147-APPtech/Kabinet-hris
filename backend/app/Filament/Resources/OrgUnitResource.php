<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrgUnitResource\Pages;
use App\Models\OrgUnit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrgUnitResource extends Resource
{
    protected static ?string $model = OrgUnit::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Organisatie';

    protected static ?string $modelLabel = 'Organisatie-eenheid';

    protected static ?string $pluralModelLabel = 'Organisatie';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('parent_id')
                ->label('Bovenliggende eenheid')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->label('Naam')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('type')
                ->label('Type')
                ->options([
                    'directie' => 'Directie',
                    'afdeling' => 'Afdeling',
                    'dienst' => 'Dienst',
                    'sectie' => 'Sectie',
                ])
                ->required()
                ->default('afdeling'),
            Forms\Components\Toggle::make('is_active')
                ->label('Actief')
                ->default(true),
            Forms\Components\Textarea::make('description')
                ->label('Omschrijving')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Naam')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parent.name')->label('Onder')->sortable(),
                Tables\Columns\BadgeColumn::make('type')->label('Type')
                    ->colors([
                        'primary' => 'directie',
                        'success' => 'afdeling',
                        'warning' => 'dienst',
                        'gray' => 'sectie',
                    ]),
                Tables\Columns\TextColumn::make('positions_count')->label('Posities')->counts('positions'),
                Tables\Columns\IconColumn::make('is_active')->label('Actief')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'directie' => 'Directie',
                    'afdeling' => 'Afdeling',
                    'dienst' => 'Dienst',
                    'sectie' => 'Sectie',
                ]),
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
            'index' => Pages\ListOrgUnits::route('/'),
            'create' => Pages\CreateOrgUnit::route('/create'),
            'edit' => Pages\EditOrgUnit::route('/{record}/edit'),
        ];
    }
}
