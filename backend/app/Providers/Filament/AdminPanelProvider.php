<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Resources\PositionResource;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandName('HR-portaal Kabinet President Republiek Suriname')
            ->brandLogo(asset('kabinetlogo.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('kabinetlogo.png'))
            ->colors([
                'primary' => Color::hex('#1F5E3A'),
                'danger' => Color::hex('#D4A017'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->databaseNotifications()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\HrStatsOverview::class,
                \App\Filament\Widgets\DeadlineRadarWidget::class,
                \App\Filament\Widgets\UpcomingMeetingsWidget::class,
                \App\Filament\Widgets\OpenActionItemsWidget::class,
                \App\Filament\Widgets\PendingLeaveRequestsWidget::class,
                \App\Filament\Widgets\EmployeesOnLeaveWidget::class,
                \App\Filament\Widgets\HeadcountByOrgUnitChart::class,
                \App\Filament\Widgets\CertificateExpiryChart::class,
                Widgets\AccountWidget::class,
            ])
            ->navigationGroups([
                'Kabinetschef',
                'Personeel',
                'Organisatie',
                'Salaris',
                'Certificering',
                'Contracten & Resoluties',
                'Assets',
                'Systeem',
            ])
            ->navigationItems([
                NavigationItem::make('Vacante posities')
                    ->group('Organisatie')
                    ->icon('heroicon-o-megaphone')
                    ->sort(3)
                    ->url(fn (): string => PositionResource::getUrl('index', [
                        'tableFilters' => ['status' => ['value' => 'vacant']],
                    ]))
                    ->badge(fn () => \App\Models\Position::where('status', 'vacant')->sum('vacancies_count') ?: null),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
