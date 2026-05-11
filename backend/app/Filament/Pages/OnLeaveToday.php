<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EmployeesOnLeaveWidget;
use Filament\Pages\Page;

class OnLeaveToday extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sun';

    protected static ?string $navigationGroup = 'Personeel';

    protected static ?string $title = 'Wie is er met verlof?';

    protected static ?string $navigationLabel = 'Met verlof vandaag';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.on-leave-today';

    public function getWidgets(): array
    {
        return [
            EmployeesOnLeaveWidget::class,
        ];
    }
}
