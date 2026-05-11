<?php

namespace App\Filament\Widgets;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class EmployeesOnLeaveWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Medewerkers met verlof (vandaag)';

    public function table(Table $table): Table
    {
        $today = Carbon::today();

        return $table
            ->query(
                LeaveRequest::query()
                    ->with(['employee.currentPosition.orgUnit'])
                    ->where('status', 'approved')
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->orderBy('end_date')
            )
            ->emptyStateHeading('Niemand met verlof vandaag')
            ->emptyStateIcon('heroicon-o-sun')
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Naam')
                    ->searchable(['employees.first_name', 'employees.last_name']),
                Tables\Columns\TextColumn::make('employee.currentPosition.orgUnit.name')
                    ->label('Afdeling')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->state(fn (LeaveRequest $record) => sprintf(
                        '%s t/m %s',
                        $record->start_date?->format('d-m-Y') ?? '—',
                        $record->end_date?->format('d-m-Y') ?? '—',
                    )),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Soort')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'vacation' => 'Vakantie', 'sick' => 'Ziekte', 'special' => 'Bijzonder',
                        'unpaid' => 'Onbetaald', 'maternity' => 'Zwangerschap', 'study' => 'Studie',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('days_count')->label('Dagen'),
            ]);
    }
}
