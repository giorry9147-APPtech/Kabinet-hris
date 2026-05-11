<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Models\Position;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Organisatie';

    protected static ?string $modelLabel = 'Functie';

    protected static ?string $pluralModelLabel = 'Functies';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('org_unit_id')
                ->label('Afdeling')
                ->relationship('orgUnit', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('title')
                ->label('Functietitel')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            Forms\Components\TextInput::make('vacancies_count')
                ->label('Aantal plaatsen')
                ->numeric()
                ->default(1)
                ->minValue(1),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'vacant' => 'Vacant',
                    'occupied' => 'Bezet',
                    'frozen' => 'Bevroren',
                    'abolished' => 'Opgeheven',
                ])
                ->required()
                ->default('vacant'),
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
                Tables\Columns\TextColumn::make('title')->label('Functie')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('orgUnit.name')->label('Afdeling')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('vacancies_count')->label('Plaatsen'),
                Tables\Columns\TextColumn::make('current_employees_count')->label('Bezet')->counts('currentEmployees'),
                Tables\Columns\BadgeColumn::make('status')->label('Status')
                    ->colors([
                        'warning' => 'vacant',
                        'success' => 'occupied',
                        'gray' => 'frozen',
                        'danger' => 'abolished',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('org_unit_id')
                    ->label('Afdeling')
                    ->relationship('orgUnit', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')->options([
                    'vacant' => 'Vacant',
                    'occupied' => 'Bezet',
                    'frozen' => 'Bevroren',
                    'abolished' => 'Opgeheven',
                ]),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if ($user?->isDataScoped()) {
            $query->whereIn('org_unit_id', $user->accessibleOrgUnitIds());
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPositions::route('/'),
            'create' => Pages\CreatePosition::route('/create'),
            'edit' => Pages\EditPosition::route('/{record}/edit'),
        ];
    }
}
