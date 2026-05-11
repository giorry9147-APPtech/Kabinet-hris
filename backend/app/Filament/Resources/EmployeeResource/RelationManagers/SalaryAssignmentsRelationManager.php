<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Models\SalaryGrade;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SalaryAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'salaryAssignments';

    protected static ?string $title = 'Salaris-historiek';

    protected static ?string $modelLabel = 'Salaris';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('salary_grade_id')->label('Schaal & trede')
                ->relationship('salaryGrade', 'code')
                ->getOptionLabelFromRecordUsing(fn ($record) => "Schaal {$record->schaal} - Trede {$record->trede}")
                ->searchable()->preload()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if ($state && ($g = SalaryGrade::find($state))) {
                        $set('base_amount', $g->base_amount);
                    }
                }),
            Forms\Components\TextInput::make('base_amount')->label('Basis')->required()->numeric()->prefix('SRD'),
            Forms\Components\TextInput::make('allowances')->label('Toelagen')->numeric()->default(0)->prefix('SRD'),
            Forms\Components\DatePicker::make('start_date')->label('Vanaf')->required()->native(false),
            Forms\Components\DatePicker::make('end_date')->label('T/m')->native(false),
            Forms\Components\Textarea::make('notes')->label('Notities')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('salaryGrade.code')->label('Schaal'),
                Tables\Columns\TextColumn::make('base_amount')->label('Basis')->money('SRD'),
                Tables\Columns\TextColumn::make('allowances')->label('Toelagen')->money('SRD'),
                Tables\Columns\TextColumn::make('start_date')->label('Vanaf')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label('T/m')->date('d-m-Y')
                    ->placeholder('actief')->badge()->color(fn ($state) => $state ? 'gray' : 'success'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Nieuw')])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('start_date', 'desc');
    }
}
