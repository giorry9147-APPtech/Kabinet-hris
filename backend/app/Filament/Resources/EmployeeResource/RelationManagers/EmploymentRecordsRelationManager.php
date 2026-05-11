<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EmploymentRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'employmentRecords';

    protected static ?string $title = 'Dienstverband-historiek';

    protected static ?string $modelLabel = 'Dienstverband';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('position_id')->label('Functie')
                ->relationship('position', 'title')->searchable()->preload()->required(),
            Forms\Components\DatePicker::make('start_date')->label('Begindatum')->required()->native(false),
            Forms\Components\DatePicker::make('end_date')->label('Einddatum')->native(false),
            Forms\Components\Select::make('status')->options([
                'active' => 'Actief', 'ended' => 'Beëindigd',
            ])->required()->default('active'),
            Forms\Components\Select::make('reason')->label('Reden')->options([
                'hire' => 'Indiensttreding', 'transfer' => 'Overplaatsing', 'promotion' => 'Promotie',
                'demotion' => 'Demotie', 'reorganization' => 'Reorganisatie', 'exit' => 'Uitdienst',
            ])->required()->default('hire'),
            Forms\Components\Textarea::make('notes')->label('Notities')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('position.title')
            ->columns([
                Tables\Columns\TextColumn::make('position.title')->label('Functie'),
                Tables\Columns\TextColumn::make('start_date')->label('Vanaf')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label('T/m')->date('d-m-Y')->placeholder('—'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reden')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'hire' => 'Indienst', 'transfer' => 'Overplaatsing', 'promotion' => 'Promotie',
                        'demotion' => 'Demotie', 'reorganization' => 'Reorg', 'exit' => 'Uitdienst',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success', 'ended' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Nieuw')])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('start_date', 'desc');
    }
}
