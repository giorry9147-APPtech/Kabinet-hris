<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResolutionResource\Pages;
use App\Models\Resolution;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ResolutionResource extends Resource
{
    protected static ?string $model = Resolution::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Contracten & Resoluties';

    protected static ?string $modelLabel = 'Resolutie (beschikking)';

    protected static ?string $pluralModelLabel = 'Resoluties (beschikkingen)';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Beschikking van de President')
                ->description('Resolutie / beschikking uitgevaardigd door de President van de Republiek Suriname.')
                ->schema([
                    Forms\Components\TextInput::make('resolution_number')->label('Resolutienummer')
                        ->required()->unique(ignoreRecord: true)->maxLength(100)
                        ->placeholder('bv. PB-2026-0142'),
                    Forms\Components\Select::make('category')->label('Categorie')
                        ->options(Resolution::CATEGORIES)->native(false)->required(),
                    Forms\Components\TextInput::make('subject')->label('Onderwerp')->required()->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('signed_by')->label('Ondertekend door')
                        ->default('President van de Republiek Suriname')->maxLength(255),
                    Forms\Components\Select::make('status')->label('Status')
                        ->options(Resolution::STATUSES)->default('active')->required()->native(false),
                ])->columns(2),

            Forms\Components\Section::make('Looptijd')
                ->schema([
                    Forms\Components\DatePicker::make('signed_at')->label('Datum ondertekening')->required()->native(false),
                    Forms\Components\DatePicker::make('effective_from')->label('Ingangsdatum')->native(false),
                    Forms\Components\DatePicker::make('expires_at')->label('Vervaldatum')
                        ->native(false)
                        ->helperText('Leeg = open-ended. Bij invulling verschijnt automatisch een vermelding bij naderend vervallen.'),
                ])->columns(3),

            Forms\Components\Section::make('Betrokken')
                ->schema([
                    Forms\Components\Select::make('employee_id')->label('Betreft medewerker (optioneel)')
                        ->relationship('employee', 'last_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                        ->searchable(['first_name', 'last_name', 'employee_number'])
                        ->preload(),
                    Forms\Components\Select::make('org_unit_id')->label('Betreft organisatie-onderdeel (optioneel)')
                        ->relationship('orgUnit', 'name')->searchable()->preload(),
                ])->columns(2),

            Forms\Components\Section::make('Inhoud')
                ->schema([
                    Forms\Components\Textarea::make('summary')->label('Samenvatting / dictum')
                        ->rows(4)->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->label('Interne notities')
                        ->columnSpanFull(),
                    Forms\Components\SpatieMediaLibraryFileUpload::make('file')
                        ->label('Beschikking (PDF/scan)')->collection('file')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resolution_number')->label('Nr.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subject')->label('Onderwerp')->searchable()->limit(50)
                    ->tooltip(fn ($record) => $record->subject),
                Tables\Columns\TextColumn::make('category')->label('Categorie')
                    ->formatStateUsing(fn ($state) => Resolution::CATEGORIES[$state] ?? $state)
                    ->badge()->color('info'),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Medewerker')
                    ->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('signed_at')->label('Getekend')->date('d-m-Y')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('expires_at')->label('Vervalt op')->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        Carbon::parse($state)->isPast() => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(30)) => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(90)) => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : 'open-ended'),
                Tables\Columns\TextColumn::make('status')->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Resolution::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'expiring' => 'warning',
                        'expired' => 'danger',
                        'revoked' => 'danger',
                        'superseded' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label('Categorie')->options(Resolution::CATEGORIES),
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(Resolution::STATUSES),
                Tables\Filters\Filter::make('expiring_soon')->label('Vervalt < 90 dagen')
                    ->query(fn ($query) => $query->whereBetween('expires_at', [now(), now()->addDays(90)])),
                Tables\Filters\Filter::make('expired')->label('Reeds vervallen')
                    ->query(fn ($query) => $query->where('expires_at', '<', now())),
                Tables\Filters\Filter::make('open_ended')->label('Open-ended')
                    ->query(fn ($query) => $query->whereNull('expires_at')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('signed_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Resolution::query()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(90)])
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResolutions::route('/'),
            'create' => Pages\CreateResolution::route('/create'),
            'edit' => Pages\EditResolution::route('/{record}/edit'),
        ];
    }
}
