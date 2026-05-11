<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Employee;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Toegang & beheer';

    protected static ?string $modelLabel = 'Gebruikersaccount';

    protected static ?string $pluralModelLabel = 'Gebruikersaccounts';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'email';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'hr_manager', 'hr_admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Accountgegevens')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Volledige naam')
                    ->required()
                    ->maxLength(120),

                Forms\Components\TextInput::make('email')
                    ->label('E-mailadres')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(180)
                    ->helperText('De gebruiker logt in met dit e-mailadres.'),

                Forms\Components\TextInput::make('password')
                    ->label('Wachtwoord')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->maxLength(180)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->helperText(fn (string $operation): string => $operation === 'create'
                        ? 'Minimaal 8 tekens. Communiceer dit veilig met de medewerker.'
                        : 'Laat leeg om het huidige wachtwoord ongewijzigd te laten.'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Account actief')
                    ->default(true)
                    ->inline(false)
                    ->helperText('Inactieve accounts kunnen niet inloggen.'),
            ])->columns(2),

            Forms\Components\Section::make('Toegang')->schema([
                Forms\Components\Select::make('roles')
                    ->label('Rollen')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->required()
                    ->helperText('super_admin / hr_manager / hr_admin / dept_head / finance → toegang tot dit admin-paneel. employee → alleen het medewerker-portaal.'),

                Forms\Components\Select::make('employee_id')
                    ->label('Gekoppelde medewerker')
                    ->relationship('employee', 'last_name')
                    ->searchable(['first_name', 'last_name', 'employee_number', 'email'])
                    ->getOptionLabelFromRecordUsing(fn (Employee $record): string =>
                        "{$record->employee_number} — {$record->first_name} {$record->last_name}"
                    )
                    ->preload()
                    ->nullable()
                    ->helperText('Optioneel maar nodig voor portal-zelfservice (eigen dossier / verlof / certificaten).'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('E-mail gekopieerd'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rollen')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'hr_manager', 'hr_admin' => 'warning',
                        'finance' => 'success',
                        'dept_head' => 'info',
                        'employee' => 'gray',
                        default => 'gray',
                    })
                    ->separator(','),

                Tables\Columns\TextColumn::make('employee.employee_number')
                    ->label('Personeelsnr.')
                    ->placeholder('— geen —')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actief')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangemaakt')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Alleen actief')
                    ->falseLabel('Alleen inactief')
                    ->placeholder('Alle accounts'),

                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\Action::make('reset_password')
                    ->label('Wachtwoord resetten')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Nieuw wachtwoord')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->maxLength(180),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'password' => Hash::make($data['password']),
                        ]);
                    })
                    ->successNotificationTitle('Wachtwoord opnieuw ingesteld')
                    ->modalHeading(fn (User $record): string => "Wachtwoord resetten — {$record->name}")
                    ->modalDescription('Communiceer het nieuwe wachtwoord veilig met de medewerker.')
                    ->modalSubmitActionLabel('Opslaan'),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record): bool =>
                        ! $record->hasRole('super_admin')
                        || User::role('super_admin')->count() > 1
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
