<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Systeem';

    protected static ?string $modelLabel = 'Audit-regel';

    protected static ?string $pluralModelLabel = 'Audit-log';

    protected static ?string $slug = 'audit-log';

    protected static ?int $navigationSort = 99;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'hr_manager']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]); // read-only
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Wanneer')->dateTime('d-m-Y H:i:s')->sortable(),
                Tables\Columns\TextColumn::make('causer.name')->label('Door')->placeholder('systeem')->searchable(),
                Tables\Columns\BadgeColumn::make('event')->label('Actie')
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                        'gray' => fn ($state) => ! in_array($state, ['created', 'updated', 'deleted']),
                    ]),
                Tables\Columns\TextColumn::make('subject_type')->label('Object')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '—'),
                Tables\Columns\TextColumn::make('subject_id')->label('ID'),
                Tables\Columns\TextColumn::make('description')->label('Beschrijving')->limit(50)->tooltip(fn ($record) => $record->description),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')->options([
                    'created' => 'Aangemaakt',
                    'updated' => 'Gewijzigd',
                    'deleted' => 'Verwijderd',
                ]),
                Tables\Filters\SelectFilter::make('subject_type')->label('Type object')
                    ->options(fn () => Activity::query()->whereNotNull('subject_type')
                        ->distinct()->pluck('subject_type', 'subject_type')
                        ->mapWithKeys(fn ($v, $k) => [$k => class_basename($v)])->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Audit-regel')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Sluiten')
                    ->modalContent(fn ($record) => view('filament.audit-log-detail', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
