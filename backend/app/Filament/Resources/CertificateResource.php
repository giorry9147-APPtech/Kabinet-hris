<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use App\Models\CertificateType;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Certificering';

    protected static ?string $modelLabel = 'Certificaat';

    protected static ?string $pluralModelLabel = 'Certificaten';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')->label('Medewerker')
                ->relationship('employee', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                ->searchable(['first_name', 'last_name', 'employee_number'])
                ->preload()
                ->required(),
            Forms\Components\Select::make('certificate_type_id')->label('Type')
                ->relationship('certificateType', 'name')
                ->searchable()->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                    if (! $state || $get('expires_at')) return;
                    $type = CertificateType::find($state);
                    if ($type?->default_validity_months && $get('issued_at')) {
                        $set('expires_at', Carbon::parse($get('issued_at'))->addMonths($type->default_validity_months)->toDateString());
                    }
                }),
            Forms\Components\TextInput::make('number')->label('Certificaatnummer')->maxLength(100),
            Forms\Components\TextInput::make('issuer')->label('Uitgever')->maxLength(255),
            Forms\Components\DatePicker::make('issued_at')->label('Uitgegeven op')->required()->native(false),
            Forms\Components\DatePicker::make('expires_at')->label('Vervalt op')->native(false),
            Forms\Components\Textarea::make('notes')->label('Notities')->columnSpanFull(),
            Forms\Components\SpatieMediaLibraryFileUpload::make('file')->label('Bestand (PDF/scan)')
                ->collection('file')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')->label('Medewerker')->searchable(['employees.first_name', 'employees.last_name'])->sortable('employees.last_name'),
                Tables\Columns\TextColumn::make('certificateType.name')->label('Type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('number')->label('Nummer')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('issued_at')->label('Uitgegeven')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('expires_at')->label('Vervalt')->date('d-m-Y')->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        Carbon::parse($state)->isPast() => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(90)) => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : '—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('certificate_type_id')->label('Type')
                    ->relationship('certificateType', 'name')->preload(),
                Tables\Filters\Filter::make('expiring_soon')->label('Verloopt < 90 dagen')
                    ->query(fn ($query) => $query->whereBetween('expires_at', [now(), now()->addDays(90)])),
                Tables\Filters\Filter::make('expired')->label('Verlopen')
                    ->query(fn ($query) => $query->where('expires_at', '<', now())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('expires_at', 'asc');
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
            'index' => Pages\ListCertificates::route('/'),
            'create' => Pages\CreateCertificate::route('/create'),
            'edit' => Pages\EditCertificate::route('/{record}/edit'),
        ];
    }
}
