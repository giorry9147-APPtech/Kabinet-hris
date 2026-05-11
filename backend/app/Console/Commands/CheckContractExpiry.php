<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class CheckContractExpiry extends Command
{
    protected $signature = 'contracts:check-expiry
                            {--days=60 : Threshold in days for "expiring soon" alerts}';

    protected $description = 'Vindt contracten waarvan de looptijd binnen N dagen verstrijkt of reeds verstreken is, en stuurt notificaties naar HR (super_admin + hr_manager + hr_admin).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $now = Carbon::now()->startOfDay();
        $threshold = $now->copy()->addDays($days);

        $expiringSoon = Contract::query()
            ->whereNotNull('end_date')
            ->whereNotIn('status', ['expired', 'terminated', 'renewed'])
            ->whereBetween('end_date', [$now, $threshold])
            ->with(['employee:id,first_name,last_name,employee_number'])
            ->get();

        $expired = Contract::query()
            ->whereNotNull('end_date')
            ->whereNotIn('status', ['expired', 'terminated', 'renewed'])
            ->where('end_date', '<', $now)
            ->with(['employee:id,first_name,last_name,employee_number'])
            ->get();

        $this->info("Contracten verlopend < {$days} dagen: ".$expiringSoon->count());
        $this->info('Contracten reeds verlopen: '.$expired->count());

        if ($expiringSoon->isEmpty() && $expired->isEmpty()) {
            $this->info('Geen actie nodig.');
            return self::SUCCESS;
        }

        $hrUsers = User::role(['super_admin', 'hr_manager', 'hr_admin'])->get();
        if ($hrUsers->isEmpty()) {
            $this->warn('Geen HR-gebruikers gevonden om te notificeren.');
            return self::SUCCESS;
        }

        foreach ($hrUsers as $user) {
            if ($expired->isNotEmpty()) {
                Notification::make()
                    ->title('Verlopen contracten: '.$expired->count())
                    ->body($this->formatList($expired, 'verlopen op'))
                    ->danger()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->sendToDatabase($user);
            }

            if ($expiringSoon->isNotEmpty()) {
                Notification::make()
                    ->title("Contracten verlopen binnen {$days} dagen: ".$expiringSoon->count())
                    ->body($this->formatList($expiringSoon, 'verloopt op'))
                    ->warning()
                    ->icon('heroicon-o-clock')
                    ->sendToDatabase($user);
            }
        }

        $this->info('Notificaties verzonden naar '.$hrUsers->count().' HR-gebruiker(s).');
        return self::SUCCESS;
    }

    private function formatList($contracts, string $verb): string
    {
        return $contracts->take(5)->map(function (Contract $c) use ($verb) {
            $emp = $c->employee?->last_name.', '.$c->employee?->first_name;
            $type = Contract::TYPES[$c->type] ?? $c->type;
            $date = $c->end_date?->format('d-m-Y');
            return "• {$emp} — {$type} (#{$c->contract_number}, {$verb} {$date})";
        })->implode("\n").($contracts->count() > 5 ? "\n...en ".($contracts->count() - 5).' meer' : '');
    }
}
