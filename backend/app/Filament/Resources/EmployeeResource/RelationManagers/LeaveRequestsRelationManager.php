<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'leaveRequests';

    protected static ?string $title = 'Verlof';

    protected static ?string $modelLabel = 'Verlofaanvraag';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')->label('Soort')->options([
                'vacation' => 'Vakantie', 'sick' => 'Ziekte', 'special' => 'Bijzonder',
                'unpaid' => 'Onbetaald', 'maternity' => 'Zwangerschap', 'study' => 'Studie',
            ])->required()->default('vacation'),
            Forms\Components\DatePicker::make('start_date')->label('Vanaf')->required()->native(false)->live()
                ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalcDays($get, $set)),
            Forms\Components\DatePicker::make('end_date')->label('T/m')->required()->native(false)->live()
                ->after('start_date')
                ->validationMessages(['after' => 'T/m datum moet ná de Vanaf datum liggen.'])
                ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalcDays($get, $set)),
            Forms\Components\TextInput::make('days_count')->label('Dagen')->numeric()->step(0.5)->minValue(0.5)
                ->required()->live(onBlur: true)
                ->validationMessages(['min' => 'Het aantal dagen moet groter zijn dan 0.'])
                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                    $start = $get('start_date');
                    if (! $start || ! is_numeric($state) || (float) $state <= 0) {
                        return;
                    }
                    $remaining = (int) ceil((float) $state);
                    $cursor = \Carbon\Carbon::parse($start);
                    while ($cursor->isWeekend()) {
                        $cursor->addDay();
                    }
                    $end = $cursor->copy();
                    while (--$remaining > 0) {
                        $end->addDay();
                        while ($end->isWeekend()) {
                            $end->addDay();
                        }
                    }
                    $set('end_date', $end->toDateString());
                }),
            Forms\Components\Select::make('status')->label('Status')->options([
                'pending' => 'In behandeling', 'approved' => 'Goedgekeurd',
                'rejected' => 'Afgewezen', 'cancelled' => 'Ingetrokken',
            ])->required()->default('pending'),
            Forms\Components\Textarea::make('reason')->label('Reden')->columnSpanFull(),
        ])->columns(2);
    }

    protected static function recalcDays(Forms\Get $get, Forms\Set $set): void
    {
        $start = $get('start_date');
        $end = $get('end_date');
        if (! $start || ! $end) {
            return;
        }
        $startDate = \Carbon\Carbon::parse($start);
        $endDate = \Carbon\Carbon::parse($end);
        if ($endDate->lt($startDate)) {
            return;
        }
        $days = $startDate->diffInDaysFiltered(
            fn (\Carbon\Carbon $d) => ! $d->isWeekend(),
            $endDate
        ) + ($endDate->isWeekend() ? 0 : 1);
        $set('days_count', max($days, 1));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Soort')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'vacation' => 'Vakantie', 'sick' => 'Ziekte', 'special' => 'Bijzonder',
                        'unpaid' => 'Onbetaald', 'maternity' => 'Zwangerschap', 'study' => 'Studie',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('start_date')->label('Vanaf')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label('T/m')->date('d-m-Y'),
                Tables\Columns\TextColumn::make('days_count')->label('Dagen'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning', 'approved' => 'success',
                        'rejected' => 'danger', 'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Wacht', 'approved' => 'OK',
                        'rejected' => 'Afgewezen', 'cancelled' => 'Ingetrokken',
                        default => $state ?? '—',
                    }),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Nieuw')])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->defaultSort('start_date', 'desc');
    }
}
