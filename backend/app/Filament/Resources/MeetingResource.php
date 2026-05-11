<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingResource\Pages;
use App\Models\Meeting;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Kabinetschef';

    protected static ?string $modelLabel = 'Vergadering';

    protected static ?string $pluralModelLabel = 'Vergaderingen';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Vergadering')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('meeting_number')->label('Vergadernummer')
                        ->required()->unique(ignoreRecord: true)->maxLength(60)
                        ->default(fn () => 'KAB-VERG-'.now()->format('Ymd-His')),
                    Forms\Components\Select::make('type')->label('Type')
                        ->options(Meeting::TYPES)->required()->native(false)
                        ->default('staf'),
                    Forms\Components\TextInput::make('title')->label('Onderwerp / titel')
                        ->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('scheduled_at')->label('Gepland op')
                        ->required()->native(false)->seconds(false),
                    Forms\Components\TextInput::make('duration_minutes')->label('Duur (minuten)')
                        ->numeric()->minValue(0)->maxValue(1440),
                    Forms\Components\TextInput::make('location')->label('Locatie')
                        ->maxLength(255)->placeholder('Bureau van de President / Conferentiezaal / Online'),
                    Forms\Components\Select::make('status')->label('Status')
                        ->options(Meeting::STATUSES)->default('planned')->required()->native(false),
                ]),

            Forms\Components\Section::make('Deelnemers')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('chair_employee_id')->label('Voorzitter')
                        ->relationship('chair', 'last_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                        ->searchable(['first_name', 'last_name'])->preload(),
                    Forms\Components\Select::make('attendees')->label('Aanwezige medewerkers')
                        ->multiple()
                        ->relationship('attendees', 'last_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                        ->searchable(['first_name', 'last_name', 'employee_number'])->preload()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('external_attendees')->label('Externe deelnemers (vrije tekst)')
                        ->rows(3)->columnSpanFull()
                        ->placeholder("Bijv.:\nMevr. J. Karijomenawi — Ministerie van Buitenlandse Zaken\nDhr. R. Patel — sollicitant adviseur economische zaken"),
                ]),

            Forms\Components\Section::make('Agenda & aantekeningen')
                ->schema([
                    Forms\Components\Textarea::make('agenda')->label('Agenda')
                        ->rows(5)->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->label('Interne notities')
                        ->rows(3)->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Notulen')
                ->description('Notulen worden hier vastgelegd. PDF/scan kan los geüpload worden.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('minutes_status')->label('Notulen-status')
                        ->options(Meeting::MINUTES_STATUSES)->default('none')->required()->native(false),
                    Forms\Components\TextInput::make('minutes_signed_by')->label('Vastgesteld door')
                        ->maxLength(255)
                        ->placeholder('Bijv. Kabinetschef S. Akiemboto'),
                    Forms\Components\DatePicker::make('minutes_finalized_at')->label('Datum vaststelling')
                        ->native(false),
                    Forms\Components\Textarea::make('minutes_content')->label('Notulen-tekst')
                        ->rows(8)->columnSpanFull(),
                    Forms\Components\SpatieMediaLibraryFileUpload::make('minutes_file')->label('Notulen (PDF/scan)')
                        ->collection('minutes_file')->columnSpanFull(),
                    Forms\Components\SpatieMediaLibraryFileUpload::make('attachments')->label('Bijlagen')
                        ->collection('attachments')->multiple()->columnSpanFull(),
                ])->collapsed(fn ($record) => $record === null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')->label('Wanneer')
                    ->dateTime('d-m-Y H:i')->sortable()
                    ->description(fn (Meeting $r) => $r->duration_minutes ? $r->duration_minutes.' min' : null),
                Tables\Columns\TextColumn::make('type')->label('Type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'presidentieel' => 'danger',
                        'strategisch' => 'warning',
                        'crisis' => 'danger',
                        'sollicitatie' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => Meeting::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('title')->label('Onderwerp')
                    ->searchable()->limit(50)->tooltip(fn ($record) => $record->title),
                Tables\Columns\TextColumn::make('chair.full_name')->label('Voorzitter')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('attendees_count')->label('Deelnemers')
                    ->counts('attendees')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('status')->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Meeting::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'planned' => 'info',
                        'in_progress' => 'warning',
                        'held' => 'success',
                        'cancelled' => 'danger',
                        'postponed' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('minutes_status')->label('Notulen')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Meeting::MINUTES_STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'none' => 'gray',
                        'concept' => 'warning',
                        'final' => 'success',
                        'published' => 'success',
                        default => 'gray',
                    })->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(Meeting::TYPES),
                Tables\Filters\SelectFilter::make('status')->options(Meeting::STATUSES),
                Tables\Filters\Filter::make('upcoming')->label('Aankomend')
                    ->query(fn ($query) => $query->where('scheduled_at', '>=', now())->whereNotIn('status', ['cancelled'])),
                Tables\Filters\Filter::make('presidential')->label('Met de President')
                    ->query(fn ($query) => $query->where('type', 'presidentieel')),
                Tables\Filters\Filter::make('missing_minutes')->label('Notulen ontbreken')
                    ->query(fn ($query) => $query->where('status', 'held')->whereIn('minutes_status', ['none', 'concept'])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('scheduled_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Meeting::query()
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->whereNotIn('status', ['cancelled'])
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetings::route('/'),
            'create' => Pages\CreateMeeting::route('/create'),
            'edit' => Pages\EditMeeting::route('/{record}/edit'),
        ];
    }
}
