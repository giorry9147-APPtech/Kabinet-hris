<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MeetingResource;
use App\Models\Meeting;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingMeetingsWidget extends BaseWidget
{
    protected static ?string $heading = 'Aankomende vergaderingen — komende 14 dagen';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Meeting::query()
                    ->where('scheduled_at', '>=', now())
                    ->where('scheduled_at', '<=', now()->addDays(14))
                    ->whereNotIn('status', ['cancelled'])
                    ->orderBy('scheduled_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')->label('Wanneer')
                    ->dateTime('d-m-Y H:i')
                    ->description(fn (Meeting $r) => $r->duration_minutes ? $r->duration_minutes.' min' : null),
                Tables\Columns\TextColumn::make('type')->label('Type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'presidentieel' => 'danger',
                        'strategisch' => 'warning',
                        'crisis' => 'danger',
                        default => 'info',
                    })
                    ->formatStateUsing(fn ($state) => Meeting::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('title')->label('Onderwerp')->limit(50)
                    ->tooltip(fn ($record) => $record->title),
                Tables\Columns\TextColumn::make('chair.full_name')->label('Voorzitter')->placeholder('—'),
                Tables\Columns\TextColumn::make('location')->label('Locatie')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('attendees_count')->label('Deelnemers')
                    ->counts('attendees')->badge()->color('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('open')->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Meeting $record) => MeetingResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
