<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActionItemResource\Pages;
use App\Models\ActionItem;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActionItemResource extends Resource
{
    protected static ?string $model = ActionItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Kabinetschef';

    protected static ?string $modelLabel = 'Werkafspraak';

    protected static ?string $pluralModelLabel = 'Werkafspraken';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Werkafspraak / actiepunt')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')->label('Titel')
                        ->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\Select::make('meeting_id')->label('Uit vergadering (optioneel)')
                        ->relationship('meeting', 'title')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->meeting_number.' — '.$record->title.' ('.$record->scheduled_at?->format('d-m-Y').')')
                        ->searchable(['meeting_number', 'title'])->preload(),
                    Forms\Components\Select::make('decision_id')->label('Op basis van besluit (optioneel)')
                        ->relationship('decision', 'subject')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->decision_number.' — '.$record->subject)
                        ->searchable(['decision_number', 'subject'])->preload(),
                    Forms\Components\Select::make('assignee_employee_id')->label('Toegewezen aan')
                        ->relationship('assignee', 'last_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                        ->searchable(['first_name', 'last_name'])->preload(),
                    Forms\Components\DatePicker::make('due_date')->label('Deadline')
                        ->native(false),
                    Forms\Components\Select::make('priority')->label('Prioriteit')
                        ->options(ActionItem::PRIORITIES)->default('normal')->required()->native(false),
                    Forms\Components\Select::make('status')->label('Status')
                        ->options(ActionItem::STATUSES)->default('open')->required()->native(false)->live(),
                    Forms\Components\Textarea::make('description')->label('Omschrijving')
                        ->rows(3)->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('completed_at')->label('Afgerond op')
                        ->native(false)->seconds(false)
                        ->visible(fn (Forms\Get $get) => $get('status') === 'done'),
                    Forms\Components\Textarea::make('completion_note')->label('Afsluitnotitie')
                        ->rows(2)->columnSpanFull()
                        ->visible(fn (Forms\Get $get) => in_array($get('status'), ['done', 'cancelled'])),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titel')->searchable()->limit(50)
                    ->tooltip(fn ($record) => $record->title),
                Tables\Columns\TextColumn::make('assignee.full_name')->label('Verantwoordelijke')->placeholder('—')
                    ->searchable(['employees.first_name', 'employees.last_name']),
                Tables\Columns\TextColumn::make('due_date')->label('Deadline')->sortable()
                    ->badge()
                    ->color(fn ($state, ActionItem $r) => match (true) {
                        $state === null => 'gray',
                        in_array($r->status, ['done', 'cancelled']) => 'success',
                        Carbon::parse($state)->isPast() => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(3)) => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(14)) => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : '—'),
                Tables\Columns\TextColumn::make('priority')->label('Prio')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ActionItem::PRIORITIES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'low' => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('status')->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ActionItem::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'open' => 'info',
                        'in_progress' => 'warning',
                        'blocked' => 'danger',
                        'done' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('meeting.meeting_number')->label('Vergadering')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('decision.decision_number')->label('Besluit')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(ActionItem::STATUSES),
                Tables\Filters\SelectFilter::make('priority')->options(ActionItem::PRIORITIES),
                Tables\Filters\SelectFilter::make('assignee_employee_id')->label('Verantwoordelijke')
                    ->relationship('assignee', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()->preload(),
                Tables\Filters\Filter::make('overdue')->label('Te laat')
                    ->query(fn ($query) => $query->where('due_date', '<', now())
                        ->whereNotIn('status', ['done', 'cancelled'])),
                Tables\Filters\Filter::make('open_only')->label('Nog open')->default()
                    ->query(fn ($query) => $query->whereNotIn('status', ['done', 'cancelled'])),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_done')->label('Afronden')
                    ->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (ActionItem $r) => ! in_array($r->status, ['done', 'cancelled']))
                    ->action(function (ActionItem $record) {
                        $record->update(['status' => 'done', 'completed_at' => now()]);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ActionItem::query()
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActionItems::route('/'),
            'create' => Pages\CreateActionItem::route('/create'),
            'edit' => Pages\EditActionItem::route('/{record}/edit'),
        ];
    }
}
