<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\Pages;
use App\Models\Asset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationGroup = 'Assets';

    protected static ?string $modelLabel = 'Asset';

    protected static ?string $pluralModelLabel = 'Assets';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('asset_code')->label('Asset code')->required()->unique(ignoreRecord: true)->maxLength(50),
            Forms\Components\TextInput::make('name')->label('Naam')->required()->maxLength(255),
            Forms\Components\TextInput::make('category')->label('Categorie')->maxLength(100)
                ->placeholder('bijv. Laptop, Voertuig, Mobiel'),
            Forms\Components\TextInput::make('serial_number')->label('Serienummer')->maxLength(100),
            Forms\Components\DatePicker::make('purchased_at')->label('Aangeschaft op')->native(false),
            Forms\Components\TextInput::make('purchase_value')->label('Aanschafwaarde')->numeric()->prefix('SRD'),
            Forms\Components\Select::make('status')->label('Status')->options([
                'available' => 'Beschikbaar',
                'assigned' => 'Toegewezen',
                'under_maintenance' => 'In onderhoud',
                'retired' => 'Afgevoerd',
                'lost' => 'Vermist',
            ])->required()->default('available'),
            Forms\Components\Textarea::make('notes')->label('Notities')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Naam')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->label('Categorie')->searchable(),
                Tables\Columns\TextColumn::make('currentAssignment.employee.full_name')->label('Toegewezen aan')->placeholder('—'),
                Tables\Columns\BadgeColumn::make('status')->label('Status')
                    ->colors([
                        'success' => 'available',
                        'primary' => 'assigned',
                        'warning' => 'under_maintenance',
                        'gray' => 'retired',
                        'danger' => 'lost',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'available' => 'Beschikbaar', 'assigned' => 'Toegewezen',
                        'under_maintenance' => 'Onderhoud', 'retired' => 'Afgevoerd', 'lost' => 'Vermist',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'available' => 'Beschikbaar', 'assigned' => 'Toegewezen',
                    'under_maintenance' => 'In onderhoud', 'retired' => 'Afgevoerd', 'lost' => 'Vermist',
                ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('asset_code');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([\Illuminate\Database\Eloquent\SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
