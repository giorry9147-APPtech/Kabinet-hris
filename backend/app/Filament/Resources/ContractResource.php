<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Models\Contract;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Contracten & Resoluties';

    protected static ?string $modelLabel = 'Contract';

    protected static ?string $pluralModelLabel = 'Contracten';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')->label('Medewerker')
                ->relationship('employee', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                ->searchable(['first_name', 'last_name', 'employee_number'])
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('contract_number')->label('Contractnummer')
                ->required()->unique(ignoreRecord: true)->maxLength(100),
            Forms\Components\Select::make('type')->label('Type contract')
                ->options(Contract::TYPES)->required()->native(false),
            Forms\Components\TextInput::make('title')->label('Omschrijving / functie')->maxLength(255),
            Forms\Components\DatePicker::make('start_date')->label('Ingangsdatum')->required()->native(false),
            Forms\Components\DatePicker::make('end_date')->label('Einddatum (looptijd)')
                ->native(false)
                ->helperText('Leeg = onbepaalde tijd. Vermelding van verloop verschijnt automatisch.'),
            Forms\Components\DatePicker::make('signed_at')->label('Datum ondertekening')->native(false),
            Forms\Components\TextInput::make('notice_period_days')->label('Opzegtermijn (dagen)')
                ->numeric()->minValue(0)->maxValue(365)
                ->helperText('Aantal dagen vóór einddatum dat opgezegd / verlengd moet worden.'),
            Forms\Components\TextInput::make('monthly_amount')->label('Maandbedrag')
                ->numeric()->step('0.01')->prefix('SRD'),
            Forms\Components\Select::make('currency')->label('Valuta')
                ->options(['SRD' => 'SRD', 'USD' => 'USD', 'EUR' => 'EUR'])->default('SRD'),
            Forms\Components\Select::make('status')->label('Status')
                ->options(Contract::STATUSES)->default('active')->required()->native(false),
            Forms\Components\Textarea::make('notes')->label('Bijzonderheden')->columnSpanFull(),
            Forms\Components\SpatieMediaLibraryFileUpload::make('file')->label('Contract (PDF/scan)')
                ->collection('file')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')->label('Nr.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')->label('Medewerker')
                    ->searchable(['employees.first_name', 'employees.last_name'])
                    ->sortable('employees.last_name'),
                Tables\Columns\TextColumn::make('type')->label('Type')
                    ->formatStateUsing(fn ($state) => Contract::TYPES[$state] ?? $state)
                    ->badge()->color('info'),
                Tables\Columns\TextColumn::make('start_date')->label('Vanaf')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label('Tot / einddatum')->date('d-m-Y')->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        Carbon::parse($state)->isPast() => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(30)) => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(90)) => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : 'onbepaald'),
                Tables\Columns\TextColumn::make('notice_deadline')->label('Opzegdeadline')
                    ->state(fn (Contract $r) => $r->noticeDeadlineDate()?->format('d-m-Y') ?? '—')
                    ->badge()
                    ->color(fn (Contract $r) => $r->isNoticeDeadlinePassed() ? 'danger' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Contract::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'expiring' => 'warning',
                        'expired' => 'danger',
                        'terminated' => 'gray',
                        'renewed' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Type')->options(Contract::TYPES),
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(Contract::STATUSES),
                Tables\Filters\Filter::make('expiring_soon')->label('Verloopt < 90 dagen')
                    ->query(fn ($query) => $query->whereBetween('end_date', [now(), now()->addDays(90)])),
                Tables\Filters\Filter::make('expired')->label('Verlopen')
                    ->query(fn ($query) => $query->where('end_date', '<', now())),
                Tables\Filters\Filter::make('indefinite')->label('Onbepaalde tijd')
                    ->query(fn ($query) => $query->whereNull('end_date')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('end_date', 'asc');
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

    public static function getNavigationBadge(): ?string
    {
        $count = Contract::query()
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays(90)])
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
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }
}
