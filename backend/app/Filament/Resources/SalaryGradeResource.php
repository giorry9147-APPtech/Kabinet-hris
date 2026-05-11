<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryGradeResource\Pages;
use App\Models\SalaryGrade;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SalaryGradeResource extends Resource
{
    protected static ?string $model = SalaryGrade::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Salaris';

    protected static ?string $modelLabel = 'Salarisschaal';

    protected static ?string $pluralModelLabel = 'Salarisschalen';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('schaal')->label('Schaal')->required()->numeric()->minValue(1)->maxValue(50),
            Forms\Components\TextInput::make('trede')->label('Trede')->required()->numeric()->minValue(0)->maxValue(50),
            Forms\Components\TextInput::make('code')->label('Code')->required()->unique(ignoreRecord: true)->maxLength(20),
            Forms\Components\TextInput::make('base_amount')->label('Basisbedrag')->required()->numeric()->prefix('SRD'),
            Forms\Components\TextInput::make('currency')->label('Valuta')->required()->maxLength(3)->default('SRD'),
            Forms\Components\Toggle::make('is_active')->label('Actief')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('schaal')->label('Schaal')->sortable(),
                Tables\Columns\TextColumn::make('trede')->label('Trede')->sortable(),
                Tables\Columns\TextColumn::make('code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('base_amount')->label('Bedrag')->money('SRD')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Actief')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Actief'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSalaryGrades::route('/'),
            'create' => Pages\CreateSalaryGrade::route('/create'),
            'edit' => Pages\EditSalaryGrade::route('/{record}/edit'),
        ];
    }
}
