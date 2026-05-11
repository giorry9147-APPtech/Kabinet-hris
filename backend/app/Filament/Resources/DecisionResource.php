<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DecisionResource\Pages;
use App\Models\Decision;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DecisionResource extends Resource
{
    protected static ?string $model = Decision::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Kabinetschef';

    protected static ?string $modelLabel = 'Besluit';

    protected static ?string $pluralModelLabel = 'Besluiten';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Besluit')
                ->description('Werkbesluit van het Kabinet (niet hetzelfde als een presidentiële resolutie/beschikking).')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('decision_number')->label('Besluitnummer')
                        ->required()->unique(ignoreRecord: true)->maxLength(60)
                        ->default(fn () => 'KAB-BES-'.now()->format('Ymd-His')),
                    Forms\Components\Select::make('meeting_id')->label('Genomen in vergadering (optioneel)')
                        ->relationship('meeting', 'title')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->meeting_number.' — '.$record->title.' ('.$record->scheduled_at?->format('d-m-Y').')')
                        ->searchable(['meeting_number', 'title'])->preload(),
                    Forms\Components\TextInput::make('subject')->label('Onderwerp')
                        ->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\Textarea::make('decision_text')->label('Dictum / besluittekst')
                        ->required()->rows(4)->columnSpanFull(),
                    Forms\Components\DatePicker::make('decided_at')->label('Datum besluit')
                        ->required()->native(false)->default(now()),
                    Forms\Components\Select::make('responsible_employee_id')->label('Verantwoordelijke')
                        ->relationship('responsible', 'last_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                        ->searchable(['first_name', 'last_name'])->preload(),
                    Forms\Components\DatePicker::make('deadline')->label('Uitvoerdeadline')
                        ->native(false)
                        ->helperText('Leeg = open. Bij invulling wordt deze gevolgd in de deadline-radar.'),
                    Forms\Components\Select::make('priority')->label('Prioriteit')
                        ->options(Decision::PRIORITIES)->default('normal')->required()->native(false),
                    Forms\Components\Select::make('status')->label('Status')
                        ->options(Decision::STATUSES)->default('open')->required()->native(false),
                    Forms\Components\Textarea::make('notes')->label('Toelichting / overweging')
                        ->rows(3)->columnSpanFull(),
                    Forms\Components\SpatieMediaLibraryFileUpload::make('attachments')->label('Bijlagen')
                        ->collection('attachments')->multiple()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('decision_number')->label('Nr.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subject')->label('Onderwerp')->searchable()->limit(50)
                    ->tooltip(fn ($record) => $record->subject),
                Tables\Columns\TextColumn::make('decided_at')->label('Genomen op')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('responsible.full_name')->label('Verantwoordelijke')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('meeting.meeting_number')->label('Vergadering')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('deadline')->label('Deadline')->sortable()
                    ->badge()
                    ->color(fn ($state, Decision $r) => match (true) {
                        $state === null => 'gray',
                        in_array($r->status, ['completed', 'cancelled']) => 'success',
                        Carbon::parse($state)->isPast() => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(7)) => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(30)) => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : 'geen'),
                Tables\Columns\TextColumn::make('priority')->label('Prio')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Decision::PRIORITIES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'low' => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('status')->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Decision::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'open' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'postponed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(Decision::STATUSES),
                Tables\Filters\SelectFilter::make('priority')->options(Decision::PRIORITIES),
                Tables\Filters\Filter::make('overdue')->label('Deadline verstreken')
                    ->query(fn ($query) => $query->where('deadline', '<', now())
                        ->whereNotIn('status', ['completed', 'cancelled'])),
                Tables\Filters\Filter::make('due_soon')->label('Deadline < 30 dagen')
                    ->query(fn ($query) => $query->whereBetween('deadline', [now(), now()->addDays(30)])
                        ->whereNotIn('status', ['completed', 'cancelled'])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('decided_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Decision::query()
            ->where('deadline', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
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
            'index' => Pages\ListDecisions::route('/'),
            'create' => Pages\CreateDecision::route('/create'),
            'edit' => Pages\EditDecision::route('/{record}/edit'),
        ];
    }
}
