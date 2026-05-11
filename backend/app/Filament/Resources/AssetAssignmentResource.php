<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetAssignmentResource\Pages;
use App\Models\Asset;
use App\Models\AssetAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AssetAssignmentResource extends Resource
{
    protected static ?string $model = AssetAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static ?string $navigationGroup = 'Assets';

    protected static ?string $modelLabel = 'Asset-toewijzing';

    protected static ?string $pluralModelLabel = 'Asset-toewijzingen';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('asset_id')->label('Asset')
                ->relationship('asset', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->asset_code} — {$record->name}")
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('employee_id')->label('Medewerker')
                ->relationship('employee', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                ->searchable(['first_name', 'last_name', 'employee_number'])
                ->preload()->required(),
            Forms\Components\DatePicker::make('assigned_at')->label('Toegewezen op')->required()->native(false)->default(now()),
            Forms\Components\DatePicker::make('returned_at')->label('Geretourneerd op')->native(false),
            Forms\Components\TextInput::make('condition_at_assignment')->label('Staat bij toewijzing')->maxLength(100)
                ->placeholder('bijv. nieuw, goed, krasje op deksel'),
            Forms\Components\TextInput::make('condition_at_return')->label('Staat bij retour')->maxLength(100),
            Forms\Components\Textarea::make('notes')->label('Notities')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset.asset_code')->label('Code')->searchable(),
                Tables\Columns\TextColumn::make('asset.name')->label('Asset')->searchable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Medewerker')->searchable(['employees.first_name', 'employees.last_name']),
                Tables\Columns\TextColumn::make('assigned_at')->label('Vanaf')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('returned_at')->label('Geretourneerd')->date('d-m-Y')
                    ->placeholder('nog uit')
                    ->badge()->color(fn ($state) => $state ? 'gray' : 'success'),
            ])
            ->filters([
                Tables\Filters\Filter::make('still_out')->label('Nog uitstaand')
                    ->query(fn ($query) => $query->whereNull('returned_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('return')->label('Retour')
                    ->icon('heroicon-o-arrow-uturn-left')->color('warning')
                    ->visible(fn (AssetAssignment $record) => $record->returned_at === null)
                    ->form([
                        Forms\Components\DatePicker::make('returned_at')->label('Retourdatum')->required()->default(now()),
                        Forms\Components\TextInput::make('condition_at_return')->label('Staat bij retour'),
                    ])
                    ->action(function (AssetAssignment $record, array $data) {
                        $record->update($data);
                        $record->asset?->update(['status' => 'available']);
                        Notification::make()->title('Asset geretourneerd')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('assigned_at', 'desc');
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
            'index' => Pages\ListAssetAssignments::route('/'),
            'create' => Pages\CreateAssetAssignment::route('/create'),
            'edit' => Pages\EditAssetAssignment::route('/{record}/edit'),
        ];
    }
}
