<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    protected static ?string $title = 'Certificaten';

    protected static ?string $modelLabel = 'Certificaat';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('certificate_type_id')->label('Type')
                ->relationship('certificateType', 'name')->searchable()->preload()->required(),
            Forms\Components\TextInput::make('number')->label('Nummer')->maxLength(100),
            Forms\Components\TextInput::make('issuer')->label('Uitgever')->maxLength(255),
            Forms\Components\DatePicker::make('issued_at')->label('Uitgegeven')->required()->native(false),
            Forms\Components\DatePicker::make('expires_at')->label('Vervalt')->native(false),
            Forms\Components\Textarea::make('notes')->label('Notities')->columnSpanFull(),
            Forms\Components\SpatieMediaLibraryFileUpload::make('file')->label('Bestand')
                ->collection('file')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                Tables\Columns\TextColumn::make('certificateType.name')->label('Type'),
                Tables\Columns\TextColumn::make('number')->label('Nummer'),
                Tables\Columns\TextColumn::make('issued_at')->label('Uitgegeven')->date('d-m-Y'),
                Tables\Columns\TextColumn::make('expires_at')->label('Vervalt')->date('d-m-Y')
                    ->badge()->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        Carbon::parse($state)->isPast() => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(90)) => 'warning',
                        default => 'success',
                    })
                    ->placeholder('—'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Nieuw')])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('expires_at', 'asc');
    }
}
