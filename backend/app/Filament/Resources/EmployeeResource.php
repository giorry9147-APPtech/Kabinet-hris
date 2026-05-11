<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use App\Models\Position;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Personeel';

    protected static ?string $modelLabel = 'Medewerker';

    protected static ?string $pluralModelLabel = 'Medewerkers';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'last_name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->tabs([
                Forms\Components\Tabs\Tab::make('Persoonlijk')->schema([
                    Forms\Components\Section::make()->schema([
                        Forms\Components\TextInput::make('employee_number')->label('Personeelsnummer')
                            ->required()->unique(ignoreRecord: true)->maxLength(50)->columnSpan(1),
                        Forms\Components\Select::make('status')->label('Status')->options([
                            'active' => 'Actief',
                            'inactive' => 'Inactief',
                            'on_leave' => 'Met verlof',
                            'suspended' => 'Geschorst',
                            'exited' => 'Uit dienst',
                        ])->required()->default('active')->columnSpan(1),
                        Forms\Components\TextInput::make('first_name')->label('Voornaam')->required()->maxLength(120),
                        Forms\Components\TextInput::make('middle_name')->label('Tussenvoegsel')->maxLength(120),
                        Forms\Components\TextInput::make('last_name')->label('Achternaam')->required()->maxLength(120),
                        Forms\Components\DatePicker::make('date_of_birth')->label('Geboortedatum')->native(false)
                            ->maxDate(now())
                            ->beforeOrEqual('today')
                            ->validationMessages([
                                'before_or_equal' => 'Geboortedatum kan niet in de toekomst liggen.',
                                'max_date' => 'Geboortedatum kan niet in de toekomst liggen.',
                            ]),
                        Forms\Components\Select::make('gender')->label('Geslacht')->options([
                            'm' => 'Man', 'v' => 'Vrouw', 'x' => 'Anders/onbekend',
                        ]),
                        Forms\Components\Select::make('marital_status')->label('Burgerlijke staat')->options([
                            'single' => 'Ongehuwd',
                            'married' => 'Gehuwd',
                            'divorced' => 'Gescheiden',
                            'widowed' => 'Weduw(e)naar',
                            'partner' => 'Samenwonend',
                        ]),
                        Forms\Components\Select::make('nationality')->label('Nationaliteit')
                            ->options([
                                'Surinaams' => 'Surinaams',
                                'Nederlands' => 'Nederlands',
                                'Guyanees' => 'Guyanees',
                                'Braziliaans' => 'Braziliaans',
                                'Frans' => 'Frans',
                                'Amerikaans' => 'Amerikaans',
                                'Chinees' => 'Chinees',
                                'Indiaas' => 'Indiaas',
                                'overig' => 'Overig',
                            ])
                            ->searchable()
                            ->live()
                            ->default('Surinaams'),
                        Forms\Components\TextInput::make('national_id')->label('ID-nummer (Surinaams)')->maxLength(50)
                            ->visible(fn (Forms\Get $get) => $get('nationality') === 'Surinaams' || empty($get('nationality'))),
                        Forms\Components\TextInput::make('passport_number')->label('Paspoortnummer')->maxLength(50)
                            ->visible(fn (Forms\Get $get) => ! empty($get('nationality')) && $get('nationality') !== 'Surinaams')
                            ->required(fn (Forms\Get $get) => ! empty($get('nationality')) && $get('nationality') !== 'Surinaams')
                            ->validationMessages([
                                'required' => 'Paspoortnummer is verplicht voor niet-Surinaamse nationaliteit.',
                            ]),
                    ])->columns(2),
                ]),

                Forms\Components\Tabs\Tab::make('Contact')->schema([
                    Forms\Components\TextInput::make('email')->label('E-mail')->email()->maxLength(255),
                    Forms\Components\TextInput::make('phone')->label('Telefoon')->maxLength(50),
                    Forms\Components\Textarea::make('address')->label('Adres')->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('Dienstverband')->schema([
                    Forms\Components\Select::make('current_position_id')->label('Huidige functie')
                        ->relationship('currentPosition', 'title')->searchable()->preload(),
                    Forms\Components\DatePicker::make('joined_at')->label('In dienst sinds')->native(false),
                    Forms\Components\DatePicker::make('exited_at')->label('Uit dienst per')->native(false),
                    Forms\Components\Textarea::make('exit_reason')->label('Reden uitdiensttreding')->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make('Foto & Documenten')->schema([
                    Forms\Components\SpatieMediaLibraryFileUpload::make('avatar')
                        ->label('Pasfoto')
                        ->collection('avatar')
                        ->image()
                        ->avatar()
                        ->columnSpanFull(),
                    Forms\Components\SpatieMediaLibraryFileUpload::make('documents')
                        ->label('Documenten (paspoort, diploma\'s, etc.)')
                        ->collection('documents')
                        ->multiple()
                        ->columnSpanFull(),
                    Forms\Components\SpatieMediaLibraryFileUpload::make('contracts')
                        ->label('Contracten')
                        ->collection('contracts')
                        ->multiple()
                        ->columnSpanFull(),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('')
                    ->collection('avatar')
                    ->conversion('thumb')
                    ->circular(),
                Tables\Columns\TextColumn::make('employee_number')->label('Nr.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('last_name')->label('Achternaam')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('first_name')->label('Voornaam')->searchable(),
                Tables\Columns\TextColumn::make('currentPosition.title')->label('Functie')->searchable(),
                Tables\Columns\TextColumn::make('currentPosition.orgUnit.name')->label('Afdeling')->searchable(),
                Tables\Columns\BadgeColumn::make('status')->label('Status')
                    ->colors([
                        'success' => 'active',
                        'gray' => 'inactive',
                        'warning' => 'on_leave',
                        'danger' => fn ($state) => in_array($state, ['suspended', 'exited']),
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Actief',
                        'inactive' => 'Inactief',
                        'on_leave' => 'Verlof',
                        'suspended' => 'Geschorst',
                        'exited' => 'Uit dienst',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('joined_at')->label('In dienst')->date('d-m-Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Actief',
                    'inactive' => 'Inactief',
                    'on_leave' => 'Met verlof',
                    'suspended' => 'Geschorst',
                    'exited' => 'Uit dienst',
                ]),
                Tables\Filters\SelectFilter::make('current_position_id')
                    ->label('Functie')
                    ->relationship('currentPosition', 'title')
                    ->searchable()->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_name');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([\Illuminate\Database\Eloquent\SoftDeletingScope::class]);

        $user = auth()->user();
        if ($user?->isDataScoped()) {
            $orgIds = $user->accessibleOrgUnitIds();
            $positionIds = Position::whereIn('org_unit_id', $orgIds)->pluck('id');
            $query->whereIn('current_position_id', $positionIds);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            EmployeeResource\RelationManagers\EmploymentRecordsRelationManager::class,
            EmployeeResource\RelationManagers\SalaryAssignmentsRelationManager::class,
            EmployeeResource\RelationManagers\CertificatesRelationManager::class,
            EmployeeResource\RelationManagers\LeaveRequestsRelationManager::class,
            EmployeeResource\RelationManagers\AssetAssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
