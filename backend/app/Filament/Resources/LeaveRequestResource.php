<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Personeel';

    protected static ?string $modelLabel = 'Verlofaanvraag';

    protected static ?string $pluralModelLabel = 'Verlofaanvragen';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')->label('Medewerker')
                ->relationship('employee', 'last_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' ('.$record->employee_number.')')
                ->searchable(['first_name', 'last_name', 'employee_number'])
                ->preload()->required(),
            Forms\Components\Select::make('type')->label('Soort verlof')->options([
                'vacation' => 'Vakantie',
                'sick' => 'Ziekte',
                'special' => 'Bijzonder verlof',
                'unpaid' => 'Onbetaald',
                'maternity' => 'Zwangerschap/bevalling',
                'study' => 'Studieverlof',
            ])->required()->default('vacation'),
            Forms\Components\DatePicker::make('start_date')->label('Vanaf')->required()->native(false)->live()
                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                    static::recalcDays($get, $set);
                }),
            Forms\Components\DatePicker::make('end_date')->label('T/m')->required()->native(false)->live()
                ->after('start_date')
                ->validationMessages(['after' => 'T/m datum moet ná de Vanaf datum liggen.'])
                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                    static::recalcDays($get, $set);
                }),
            Forms\Components\TextInput::make('days_count')->label('Aantal dagen')->numeric()->step(0.5)->minValue(0.5)
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
                'pending' => 'In behandeling',
                'approved' => 'Goedgekeurd',
                'rejected' => 'Afgewezen',
                'cancelled' => 'Ingetrokken',
            ])->required()->default('pending'),
            Forms\Components\Textarea::make('reason')->label('Reden / toelichting')->columnSpanFull(),
            Forms\Components\Textarea::make('decision_reason')->label('Beslissingsreden')->columnSpanFull()
                ->visible(fn (Forms\Get $get) => in_array($get('status'), ['approved', 'rejected'])),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')->label('Medewerker')->searchable(['employees.first_name', 'employees.last_name']),
                Tables\Columns\BadgeColumn::make('type')->label('Soort')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'vacation' => 'Vakantie', 'sick' => 'Ziekte', 'special' => 'Bijzonder',
                        'unpaid' => 'Onbetaald', 'maternity' => 'Zwangerschap', 'study' => 'Studie',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('start_date')->label('Vanaf')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label('T/m')->date('d-m-Y'),
                Tables\Columns\TextColumn::make('days_count')->label('Dagen'),
                Tables\Columns\BadgeColumn::make('status')->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Wacht', 'approved' => 'Goedgekeurd',
                        'rejected' => 'Afgewezen', 'cancelled' => 'Ingetrokken',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('approver.name')->label('Beslist door')
                    ->state(function (LeaveRequest $record) {
                        if ($record->approver?->name) {
                            return $record->approver->name;
                        }
                        if (in_array($record->status, ['approved', 'rejected', 'cancelled'])) {
                            return 'MAS Administrator';
                        }
                        return null;
                    })
                    ->placeholder('—')
                    ->description(fn (LeaveRequest $record) => $record->decided_at?->format('d-m-Y H:i')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'In behandeling', 'approved' => 'Goedgekeurd',
                    'rejected' => 'Afgewezen', 'cancelled' => 'Ingetrokken',
                ])->default('pending'),
                Tables\Filters\SelectFilter::make('type')->options([
                    'vacation' => 'Vakantie', 'sick' => 'Ziekte', 'special' => 'Bijzonder',
                    'unpaid' => 'Onbetaald', 'maternity' => 'Zwangerschap', 'study' => 'Studie',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label('Goedkeuren')
                    ->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (LeaveRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'approver_id' => auth()->id(),
                            'decided_at' => now(),
                        ]);
                        Notification::make()->title('Verlof goedgekeurd')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')->label('Afwijzen')
                    ->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (LeaveRequest $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('decision_reason')->label('Reden afwijzing')->required(),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'approver_id' => auth()->id(),
                            'decided_at' => now(),
                            'decision_reason' => $data['decision_reason'],
                        ]);
                        Notification::make()->title('Verlof afgewezen')->danger()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
