<?php

namespace App\Filament\Widgets;

use App\Models\LeaveRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingLeaveRequestsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Verlofaanvragen (in behandeling)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LeaveRequest::query()
                    ->with(['employee.currentPosition.orgUnit', 'approver'])
                    ->where('status', 'pending')
                    ->orderBy('start_date')
            )
            ->emptyStateHeading('Geen openstaande aanvragen')
            ->emptyStateDescription('Alle verlofaanvragen zijn afgehandeld.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Medewerker')
                    ->searchable(['employees.first_name', 'employees.last_name']),
                Tables\Columns\TextColumn::make('employee.currentPosition.orgUnit.name')
                    ->label('Afdeling')
                    ->placeholder('—'),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Soort')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'vacation' => 'Vakantie', 'sick' => 'Ziekte', 'special' => 'Bijzonder',
                        'unpaid' => 'Onbetaald', 'maternity' => 'Zwangerschap', 'study' => 'Studie',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('start_date')->label('Vanaf')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label('T/m')->date('d-m-Y'),
                Tables\Columns\TextColumn::make('days_count')->label('Dagen'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Aangevraagd')
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (LeaveRequest $record) => \App\Filament\Resources\LeaveRequestResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
