<?php

namespace App\Console\Commands;

use App\Models\Resolution;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class CheckResolutionExpiry extends Command
{
    protected $signature = 'resolutions:check-expiry
                            {--days=60 : Threshold in days for "expiring soon" alerts}';

    protected $description = 'Vindt resoluties (presidentiële beschikkingen) die binnen N dagen vervallen of reeds vervallen zijn, en stuurt notificaties naar de Kabinetschef en HR.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $now = Carbon::now()->startOfDay();
        $threshold = $now->copy()->addDays($days);

        $expiringSoon = Resolution::query()
            ->whereNotNull('expires_at')
            ->whereNotIn('status', ['expired', 'revoked', 'superseded'])
            ->whereBetween('expires_at', [$now, $threshold])
            ->with(['employee:id,first_name,last_name,employee_number', 'orgUnit:id,name,code'])
            ->get();

        $expired = Resolution::query()
            ->whereNotNull('expires_at')
            ->whereNotIn('status', ['expired', 'revoked', 'superseded'])
            ->where('expires_at', '<', $now)
            ->with(['employee:id,first_name,last_name,employee_number', 'orgUnit:id,name,code'])
            ->get();

        $this->info("Resoluties vervallend < {$days} dagen: ".$expiringSoon->count());
        $this->info('Resoluties reeds vervallen: '.$expired->count());

        if ($expiringSoon->isEmpty() && $expired->isEmpty()) {
            $this->info('Geen actie nodig.');
            return self::SUCCESS;
        }

        $recipients = User::role(['super_admin', 'hr_manager', 'hr_admin'])->get();
        if ($recipients->isEmpty()) {
            $this->warn('Geen ontvangers gevonden om te notificeren.');
            return self::SUCCESS;
        }

        foreach ($recipients as $user) {
            if ($expired->isNotEmpty()) {
                Notification::make()
                    ->title('Vervallen resoluties: '.$expired->count())
                    ->body($this->formatList($expired, 'vervallen op'))
                    ->danger()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->sendToDatabase($user);
            }

            if ($expiringSoon->isNotEmpty()) {
                Notification::make()
                    ->title("Resoluties vervallen binnen {$days} dagen: ".$expiringSoon->count())
                    ->body($this->formatList($expiringSoon, 'vervalt op'))
                    ->warning()
                    ->icon('heroicon-o-scale')
                    ->sendToDatabase($user);
            }
        }

        $this->info('Notificaties verzonden naar '.$recipients->count().' ontvanger(s).');
        return self::SUCCESS;
    }

    private function formatList($resolutions, string $verb): string
    {
        return $resolutions->take(5)->map(function (Resolution $r) use ($verb) {
            $cat = Resolution::CATEGORIES[$r->category] ?? $r->category;
            $date = $r->expires_at?->format('d-m-Y');
            $who = $r->employee ? " — {$r->employee->last_name}, {$r->employee->first_name}" : '';
            return "• #{$r->resolution_number} ({$cat}){$who}: {$r->subject} ({$verb} {$date})";
        })->implode("\n").($resolutions->count() > 5 ? "\n...en ".($resolutions->count() - 5).' meer' : '');
    }
}
