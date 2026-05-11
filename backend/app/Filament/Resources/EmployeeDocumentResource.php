<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeDocumentResource\Pages;
use App\Models\EmployeeDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmployeeDocumentResource extends Resource
{
    protected static ?string $model = EmployeeDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationGroup = 'Personeel';

    protected static ?string $modelLabel = 'Geüpload document';

    protected static ?string $pluralModelLabel = 'Geüploade documenten';

    protected static ?int $navigationSort = 5;

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
            Forms\Components\TextInput::make('title')->label('Titel')->required()->maxLength(255),
            Forms\Components\Select::make('category')->label('Categorie')
                ->options(EmployeeDocument::CATEGORIES)->required(),
            Forms\Components\Textarea::make('notes')->label('Toelichting medewerker')->columnSpanFull(),
            Forms\Components\SpatieMediaLibraryFileUpload::make('file')->label('Bestand')
                ->collection('file')->columnSpanFull()
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
            Forms\Components\Select::make('status')->label('Status')->options([
                'pending' => 'In review',
                'approved' => 'Goedgekeurd',
                'rejected' => 'Afgewezen',
            ])->required()->default('pending'),
            Forms\Components\Textarea::make('decision_notes')->label('Opmerking admin')->columnSpanFull()
                ->visible(fn (Forms\Get $get) => in_array($get('status'), ['approved', 'rejected'])),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')->label('Medewerker')
                    ->searchable(['employees.first_name', 'employees.last_name']),
                Tables\Columns\TextColumn::make('title')->label('Titel')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('category')->label('Categorie')
                    ->badge()
                    ->formatStateUsing(fn ($state) => EmployeeDocument::CATEGORIES[$state] ?? $state),
                Tables\Columns\SpatieMediaLibraryImageColumn::make('file_preview')
                    ->label('Voorbeeld')
                    ->collection('file')->square()->size(40)
                    ->defaultImageUrl(asset('images/document-placeholder.svg'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Wacht', 'approved' => 'Goedgekeurd', 'rejected' => 'Afgewezen',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('decider.name')->label('Beslist door')
                    ->state(function (EmployeeDocument $record) {
                        if ($record->decider?->name) {
                            return $record->decider->name;
                        }
                        if (in_array($record->status, ['approved', 'rejected'])) {
                            return 'MAS Administrator';
                        }
                        return null;
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Geüpload')->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'In review', 'approved' => 'Goedgekeurd', 'rejected' => 'Afgewezen',
                ])->default('pending'),
                Tables\Filters\SelectFilter::make('category')->options(EmployeeDocument::CATEGORIES),
            ])
            ->actions([
                Tables\Actions\Action::make('download')->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (EmployeeDocument $record) => $record->getFirstMediaUrl('file') ?: null, true)
                    ->visible(fn (EmployeeDocument $record) => (bool) $record->getFirstMedia('file')),
                Tables\Actions\Action::make('approve')->label('Goedkeuren')
                    ->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (EmployeeDocument $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (EmployeeDocument $record) {
                        $record->update([
                            'status' => 'approved',
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                        ]);
                        Notification::make()->title('Document goedgekeurd')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')->label('Afwijzen')
                    ->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (EmployeeDocument $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('decision_notes')->label('Reden afwijzing')->required(),
                    ])
                    ->action(function (EmployeeDocument $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'decided_by' => auth()->id(),
                            'decided_at' => now(),
                            'decision_notes' => $data['decision_notes'],
                        ]);
                        Notification::make()->title('Document afgewezen')->danger()->send();
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
            'index' => Pages\ListEmployeeDocuments::route('/'),
            'create' => Pages\CreateEmployeeDocument::route('/create'),
            'edit' => Pages\EditEmployeeDocument::route('/{record}/edit'),
        ];
    }
}
