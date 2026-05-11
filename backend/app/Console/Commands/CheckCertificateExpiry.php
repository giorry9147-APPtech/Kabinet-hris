<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class CheckCertificateExpiry extends Command
{
    protected $signature = 'certs:check-expiry
                            {--days=60 : Threshold in days for "expiring soon" alerts}';

    protected $description = 'Vindt certificaten die binnen N dagen verlopen of al verlopen zijn, en stuurt notificaties naar HR (super_admin + hr_manager + hr_admin).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $now = Carbon::now()->startOfDay();
        $threshold = $now->copy()->addDays($days);

        $expiringSoon = Certificate::query()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$now, $threshold])
            ->with(['employee:id,first_name,last_name,employee_number', 'certificateType:id,name,code'])
            ->get();

        $expired = Certificate::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->with(['employee:id,first_name,last_name,employee_number', 'certificateType:id,name,code'])
            ->get();

        $this->info("Certificaten verlopend < {$days} dagen: ".$expiringSoon->count());
        $this->info('Certificaten reeds verlopen: '.$expired->count());

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
                    ->title('Verlopen certificaten: '.$expired->count())
                    ->body($this->formatList($expired, 'verlopen op'))
                    ->danger()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->sendToDatabase($user);
            }

            if ($expiringSoon->isNotEmpty()) {
                Notification::make()
                    ->title("Certificaten verlopen binnen {$days} dagen: ".$expiringSoon->count())
                    ->body($this->formatList($expiringSoon, 'verloopt op'))
                    ->warning()
                    ->icon('heroicon-o-clock')
                    ->sendToDatabase($user);
            }
        }

        $this->info('Notificaties verzonden naar '.$hrUsers->count().' HR-gebruiker(s).');
        return self::SUCCESS;
    }

    private function formatList($certs, string $verb): string
    {
        return $certs->take(5)->map(function (Certificate $c) use ($verb) {
            $emp = $c->employee?->last_name.', '.$c->employee?->first_name;
            $type = $c->certificateType?->name ?? 'onbekend';
            $date = $c->expires_at?->format('d-m-Y');
            return "• {$emp} — {$type} ({$verb} {$date})";
        })->implode("\n").($certs->count() > 5 ? "\n...en ".($certs->count() - 5).' meer' : '');
    }
}
