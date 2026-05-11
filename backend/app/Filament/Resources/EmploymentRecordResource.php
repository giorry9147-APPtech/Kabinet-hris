<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmploymentRecordResource\Pages;
use App\Models\EmploymentRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmploymentRecordResource extends Resource
{
    protected static ?string $model = EmploymentRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Personeel';

    protected static ?string $modelLabel = 'Dienstverband';

    protected static ?string $pluralModelLabel = 'Dienstverbanden';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')->label('Medewerker')
                ->relationship('employee', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                ->searchable(['first_name', 'last_name', 'employee_number'])
                ->preload()->required(),
            Forms\Components\Select::make('position_id')->label('Functie')
                ->relationship('position', 'title')
                ->searchable()->preload()->required(),
            Forms\Components\DatePicker::make('start_date')->label('Begindatum')->required()->native(false),
            Forms\Components\DatePicker::make('end_date')->label('Einddatum')->native(false),
            Forms\Components\Select::make('status')->label('Status')->options([
                'active' => 'Actief', 'ended' => 'Beëindigd',
            ])->required()->default('active'),
            Forms\Components\Select::make('reason')->label('Reden')->options([
                'hire' => 'Indiensttreding',
                'transfer' => 'Overplaatsing',
                'promotion' => 'Promotie',
                'demotion' => 'Demotie',
                'reorganization' => 'Reorganisatie',
                'exit' => 'Uitdiensttreding',
            ])->required()->default('hire'),
            Forms\Components\Textarea::make('notes')->label('Notities')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->header(view('filament.resources.employment-records.header', [
                'total' => EmploymentRecord::count(),
                'active' => EmploymentRecord::where('status', 'active')->count(),
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')->label('Medewerker')->searchable(['employees.first_name', 'employees.last_name']),
                Tables\Columns\TextColumn::make('position.title')->label('Functie')->searchable(),
                Tables\Columns\TextColumn::make('start_date')->label('Vanaf')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label('T/m')->date('d-m-Y')->placeholder('—'),
                Tables\Columns\BadgeColumn::make('reason')->label('Reden')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'hire' => 'Indienst', 'transfer' => 'Overplaatsing', 'promotion' => 'Promotie',
                        'demotion' => 'Demotie', 'reorganization' => 'Reorg', 'exit' => 'Uitdienst',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('status')->label('Status')
                    ->colors(['success' => 'active', 'gray' => 'ended']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['active' => 'Actief', 'ended' => 'Beëindigd']),
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
            $query->whereHas('position', fn ($q) => $q->whereIn('org_unit_id', $orgIds));
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmploymentRecords::route('/'),
            'create' => Pages\CreateEmploymentRecord::route('/create'),
            'edit' => Pages\EditEmploymentRecord::route('/{record}/edit'),
        ];
    }
}
