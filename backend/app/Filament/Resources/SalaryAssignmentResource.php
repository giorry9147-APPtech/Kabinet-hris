<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryAssignmentResource\Pages;
use App\Models\SalaryAssignment;
use App\Models\SalaryGrade;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SalaryAssignmentResource extends Resource
{
    protected static ?string $model = SalaryAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Salaris';

    protected static ?string $modelLabel = 'Salaristoekenning';

    protected static ?string $pluralModelLabel = 'Salaristoekenningen';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')->label('Medewerker')
                ->relationship('employee', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                ->searchable(['first_name', 'last_name', 'employee_number'])
                ->preload()
                ->required(),
            Forms\Components\Select::make('salary_grade_id')->label('Schaal & trede')
                ->relationship('salaryGrade', 'code')
                ->getOptionLabelFromRecordUsing(fn ($record) => "Schaal {$record->schaal} - Trede {$record->trede} (SRD ".number_format((float) $record->base_amount, 2, ',', '.').')')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if ($state) {
                        $grade = SalaryGrade::find($state);
                        if ($grade) $set('base_amount', $grade->base_amount);
                    }
                }),
            Forms\Components\TextInput::make('base_amount')->label('Basisbedrag')->required()->numeric()->prefix('SRD'),
            Forms\Components\TextInput::make('allowances')->label('Toelagen')->numeric()->default(0)->prefix('SRD'),
            Forms\Components\DatePicker::make('start_date')->label('Ingangsdatum')->required()->native(false),
            Forms\Components\DatePicker::make('end_date')->label('Einddatum')->native(false)->helperText('Leeg laten als nog actief'),
            Forms\Components\Textarea::make('notes')->label('Notities')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')->label('Medewerker')->searchable(['employees.first_name', 'employees.last_name'])->sortable('employees.last_name'),
                Tables\Columns\TextColumn::make('salaryGrade.code')->label('Schaal')->sortable(),
                Tables\Columns\TextColumn::make('base_amount')->label('Basis')->money('SRD')->sortable(),
                Tables\Columns\TextColumn::make('allowances')->label('Toelagen')->money('SRD'),
                Tables\Columns\TextColumn::make('total')->label('Totaal')->money('SRD')
                    ->getStateUsing(fn ($record) => $record->base_amount + $record->allowances),
                Tables\Columns\TextColumn::make('start_date')->label('Vanaf')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label('T/m')->date('d-m-Y')
                    ->placeholder('actief')
                    ->badge()->color(fn ($state) => $state ? 'gray' : 'success'),
            ])
            ->filters([
                Tables\Filters\Filter::make('active_only')->label('Alleen actief')
                    ->query(fn ($query) => $query->whereNull('end_date'))
                    ->default(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if ($user?->isDataScoped()) {
            $orgIds = $user->accessibleOrgUnitIds();
            $query->whereHas('employee.currentPosition', fn ($q) => $q->whereIn('org_unit_id', $orgIds));
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalaryAssignments::route('/'),
            'create' => Pages\CreateSalaryAssignment::route('/create'),
            'edit' => Pages\EditSalaryAssignment::route('/{record}/edit'),
        ];
    }
}
