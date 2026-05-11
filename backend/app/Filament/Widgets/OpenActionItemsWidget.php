<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ActionItemResource;
use App\Models\ActionItem;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OpenActionItemsWidget extends BaseWidget
{
    protected static ?string $heading = 'Open werkafspraken — sortering op deadline';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ActionItem::query()
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->orderByRaw('due_date IS NULL, due_date ASC')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titel')->limit(60)
                    ->tooltip(fn ($record) => $record->title),
                Tables\Columns\TextColumn::make('assignee.full_name')->label('Verantwoordelijke')->placeholder('—'),
                Tables\Columns\TextColumn::make('due_date')->label('Deadline')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        Carbon::parse($state)->isPast() => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(3)) => 'danger',
                        Carbon::parse($state)->lte(now()->addDays(14)) => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d-m-Y') : 'geen deadline'),
                Tables\Columns\TextColumn::make('priority')->label('Prio')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ActionItem::PRIORITIES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'low' => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('status')->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ActionItem::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'open' => 'info',
                        'in_progress' => 'warning',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open')->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ActionItem $record) => ActionItemResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
