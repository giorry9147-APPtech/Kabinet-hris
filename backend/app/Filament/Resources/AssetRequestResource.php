<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetRequestResource\Pages;
use App\Models\AssetRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AssetRequestResource extends Resource
{
    protected static ?string $model = AssetRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Assets';

    protected static ?string $modelLabel = 'Asset-aanvraag';

    protected static ?string $pluralModelLabel = 'Asset-aanvragen';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')->label('Medewerker')
                ->relationship('employee', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                ->searchable(['first_name', 'last_name', 'employee_number'])
                ->preload()->required(),
            Forms\Components\Select::make('category')->label('Categorie')->options([
                'Laptop' => 'Laptop',
                'Mobiel' => 'Mobiel',
                'Tablet' => 'Tablet',
                'Voertuig' => 'Voertuig',
                'Radio' => 'Radio',
                'Werkkleding' => 'Werkkleding',
                'Overig' => 'Overig',
            ])->required(),
            Forms\Components\TextInput::make('subject')->label('Onderwerp')->required()->maxLength(255),
            Forms\Components\DatePicker::make('needed_by')->label('Nodig per')->native(false),
            Forms\Components\Textarea::make('reason')->label('Toelichting')->columnSpanFull(),
            Forms\Components\Select::make('status')->label('Status')->options([
                'pending' => 'In behandeling',
                'approved' => 'Goedgekeurd',
                'rejected' => 'Afgewezen',
                'fulfilled' => 'Geleverd',
                'cancelled' => 'Ingetrokken',
            ])->required()->default('pending'),
            Forms\Components\Textarea::make('decision_reason')->label('Beslissingsreden')->columnSpanFull()
                ->visible(fn (Forms\Get $get) => in_array($get('status'), ['approved', 'rejected'])),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')->label('Medewerker')->searchable(['employees.first_name', 'employees.last_name']),
                Tables\Columns\BadgeColumn::make('category')->label('Categorie'),
                Tables\Columns\TextColumn::make('subject')->label('Onderwerp')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('needed_by')->label('Nodig per')->date('d-m-Y')->placeholder('—'),
                Tables\Columns\BadgeColumn::make('status')->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => fn ($state) => in_array($state, ['approved', 'fulfilled']),
                        'danger' => 'rejected',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Wacht', 'approved' => 'Goedgekeurd', 'rejected' => 'Afgewezen',
                        'fulfilled' => 'Geleverd', 'cancelled' => 'Ingetrokken',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('decider.name')->label('Beslist door')
                    ->state(function (AssetRequest $record) {
                        if ($record->decider?->name) {
                            return $record->decider->name;
                        }
                        if (in_array($record->status, ['approved', 'rejected', 'fulfilled', 'cancelled'])) {
                            return 'MAS Administrator';
                        }
                        return null;
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Aangevraagd')->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'In behandeling', 'approved' => 'Goedgekeurd',
                    'rejected' => 'Afgewezen', 'fulfilled' => 'Geleverd', 'cancelled' => 'Ingetrokken',
                ])->default('pending'),
                Tables\Filters\SelectFilter::make('category')->options([
                    'Laptop' => 'Laptop', 'Mobiel' => 'Mobiel', 'Tablet' => 'Tablet',
                    'Voertuig' => 'Voertuig', 'Radio' => 'Radio',
                    'Werkkleding' => 'Werkkleding', 'Overig' => 'Overig',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label('Goedkeuren')
                    ->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (AssetRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (AssetRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);
                        Notification::make()->title('Aanvraag goedgekeurd')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')->label('Afwijzen')
                    ->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (AssetRequest $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('decision_reason')->label('Reden afwijzing')->required(),
                    ])
                    ->action(function (AssetRequest $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                            'decision_reason' => $data['decision_reason'],
                        ]);
                        Notification::make()->title('Aanvraag afgewezen')->danger()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetRequests::route('/'),
            'create' => Pages\CreateAssetRequest::route('/create'),
            'edit' => Pages\EditAssetRequest::route('/{record}/edit'),
        ];
    }
}
