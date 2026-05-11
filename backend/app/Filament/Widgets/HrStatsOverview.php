<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\OnLeaveToday;
use App\Models\ActionItem;
use App\Models\Certificate;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Meeting;
use App\Models\Resolution;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $in90 = Carbon::now()->addDays(90);

        $onLeaveToday = LeaveRequest::query()
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->count();

        $activeEmployees = Employee::where('status', 'active')->count();

        $certsExpiringSoon = Certificate::query()
            ->whereNotNull('expires_at')->whereBetween('expires_at', [Carbon::now(), $in90])->count();
        $certsExpired = Certificate::query()
            ->whereNotNull('expires_at')->where('expires_at', '<', Carbon::now())->count();

        $contractsExpiringSoon = Contract::query()
            ->whereNotNull('end_date')->whereNotIn('status', ['expired', 'terminated', 'renewed'])
            ->whereBetween('end_date', [Carbon::now(), $in90])->count();
        $contractsExpired = Contract::query()
            ->whereNotNull('end_date')->whereNotIn('status', ['expired', 'terminated', 'renewed'])
            ->where('end_date', '<', Carbon::now())->count();

        $resolutionsExpiringSoon = Resolution::query()
            ->whereNotNull('expires_at')->whereNotIn('status', ['expired', 'revoked', 'superseded'])
            ->whereBetween('expires_at', [Carbon::now(), $in90])->count();
        $resolutionsExpired = Resolution::query()
            ->whereNotNull('expires_at')->whereNotIn('status', ['expired', 'revoked', 'superseded'])
            ->where('expires_at', '<', Carbon::now())->count();

        $upcomingMeetings = Meeting::query()
            ->where('scheduled_at', '>=', Carbon::now())
            ->where('scheduled_at', '<=', Carbon::now()->addDays(7))
            ->whereNotIn('status', ['cancelled'])->count();

        $presidentialThisMonth = Meeting::query()
            ->where('type', 'presidentieel')
            ->whereBetween('scheduled_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();

        $openActions = ActionItem::query()
            ->whereNotIn('status', ['done', 'cancelled'])->count();
        $overdueActions = ActionItem::query()
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_date')->where('due_date', '<', Carbon::now())->count();

        return [
            Stat::make('Actieve medewerkers', $activeEmployees)
                ->description('totaal in dienst')
                ->color('success')
                ->icon('heroicon-o-users'),

            Stat::make('Met verlof vandaag', $onLeaveToday)
                ->description($onLeaveToday === 0 ? 'iedereen aan het werk' : 'klik voor details')
                ->color($onLeaveToday > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-sun')
                ->url(OnLeaveToday::getUrl()),

            Stat::make('Certificaten < 90 dagen', $certsExpiringSoon)
                ->description($certsExpired > 0 ? "{$certsExpired} reeds verlopen" : 'geen verlopen')
                ->color($certsExpired > 0 ? 'danger' : ($certsExpiringSoon > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-academic-cap'),

            Stat::make('Contracten < 90 dagen', $contractsExpiringSoon)
                ->description($contractsExpired > 0 ? "{$contractsExpired} reeds verlopen" : 'looptijd binnen norm')
                ->color($contractsExpired > 0 ? 'danger' : ($contractsExpiringSoon > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-document-text'),

            Stat::make('Resoluties < 90 dagen', $resolutionsExpiringSoon)
                ->description($resolutionsExpired > 0 ? "{$resolutionsExpired} reeds vervallen" : 'alle beschikkingen geldig')
                ->color($resolutionsExpired > 0 ? 'danger' : ($resolutionsExpiringSoon > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-scale'),

            Stat::make('Vergaderingen < 7 dagen', $upcomingMeetings)
                ->description($presidentialThisMonth > 0 ? "{$presidentialThisMonth} met President deze maand" : 'geen presidentieel overleg gepland')
                ->color($upcomingMeetings > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Open werkafspraken', $openActions)
                ->description($overdueActions > 0 ? "{$overdueActions} te laat" : 'alle binnen deadline')
                ->color($overdueActions > 0 ? 'danger' : ($openActions > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-clipboard-document-check'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
