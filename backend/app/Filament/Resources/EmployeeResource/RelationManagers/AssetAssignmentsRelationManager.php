<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AssetAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assetAssignments';

    protected static ?string $title = 'Assets in gebruik';

    protected static ?string $modelLabel = 'Toewijzing';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('asset_id')->label('Asset')
                ->relationship('asset', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->asset_code} — {$record->name}")
                ->searchable()->preload()->required(),
            Forms\Components\DatePicker::make('assigned_at')->label('Vanaf')->required()->native(false)->default(now()),
            Forms\Components\DatePicker::make('returned_at')->label('Geretourneerd')->native(false),
            Forms\Components\TextInput::make('condition_at_assignment')->label('Staat bij toewijzing'),
            Forms\Components\TextInput::make('condition_at_return')->label('Staat bij retour'),
            Forms\Components\Textarea::make('notes')->label('Notities')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('asset.asset_code')->label('Code')->placeholder('—'),
                Tables\Columns\TextColumn::make('asset.name')->label('Asset')->placeholder('—'),
                Tables\Columns\TextColumn::make('assigned_at')->label('Vanaf')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('returned_at')->label('Geretourneerd')->date('d-m-Y')
                    ->placeholder('nog uit')->badge()->color(fn ($state) => $state ? 'gray' : 'success'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Nieuw')])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('assigned_at', 'desc');
    }
}
