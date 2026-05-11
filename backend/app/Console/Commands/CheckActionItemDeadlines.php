<?php

namespace App\Console\Commands;

use App\Models\ActionItem;
use App\Models\Decision;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class CheckActionItemDeadlines extends Command
{
    protected $signature = 'kabinet:check-deadlines
                            {--days=14 : Threshold in days for "due soon" alerts}';

    protected $description = 'Vindt werkafspraken en besluiten met een deadline binnen N dagen (of reeds verstreken), en notificeert de Kabinetschef + HR.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $now = Carbon::now()->startOfDay();
        $threshold = $now->copy()->addDays($days);

        // ---- Action items ----
        $actionsDueSoon = ActionItem::query()
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereBetween('due_date', [$now, $threshold])
            ->with('assignee:id,first_name,last_name')->get();

        $actionsOverdue = ActionItem::query()
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['done', 'cancelled'])
            ->where('due_date', '<', $now)
            ->with('assignee:id,first_name,last_name')->get();

        // ---- Decisions with deadline ----
        $decisionsDueSoon = Decision::query()
            ->whereNotNull('deadline')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereBetween('deadline', [$now, $threshold])
            ->with('responsible:id,first_name,last_name')->get();

        $decisionsOverdue = Decision::query()
            ->whereNotNull('deadline')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('deadline', '<', $now)
            ->with('responsible:id,first_name,last_name')->get();

        $this->info("Werkafspraken te laat: {$actionsOverdue->count()} | < {$days}d: {$actionsDueSoon->count()}");
        $this->info("Besluiten te laat: {$decisionsOverdue->count()} | < {$days}d: {$decisionsDueSoon->count()}");

        $hasAny = $actionsDueSoon->isNotEmpty() || $actionsOverdue->isNotEmpty()
            || $decisionsDueSoon->isNotEmpty() || $decisionsOverdue->isNotEmpty();

        if (! $hasAny) {
            $this->info('Geen actie nodig.');
            return self::SUCCESS;
        }

        $recipients = User::role(['super_admin', 'hr_manager', 'hr_admin'])->get();
        if ($recipients->isEmpty()) {
            $this->warn('Geen ontvangers gevonden om te notificeren.');
            return self::SUCCESS;
        }

        foreach ($recipients as $user) {
            if ($actionsOverdue->isNotEmpty()) {
                Notification::make()
                    ->title('Werkafspraken te laat: '.$actionsOverdue->count())
                    ->body($this->formatActions($actionsOverdue, 'deadline'))
                    ->danger()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->sendToDatabase($user);
            }

            if ($actionsDueSoon->isNotEmpty()) {
                Notification::make()
                    ->title("Werkafspraken deadline < {$days}d: ".$actionsDueSoon->count())
                    ->body($this->formatActions($actionsDueSoon, 'deadline'))
                    ->warning()
                    ->icon('heroicon-o-clock')
                    ->sendToDatabase($user);
            }

            if ($decisionsOverdue->isNotEmpty()) {
                Notification::make()
                    ->title('Besluiten met verstreken deadline: '.$decisionsOverdue->count())
                    ->body($this->formatDecisions($decisionsOverdue))
                    ->danger()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->sendToDatabase($user);
            }

            if ($decisionsDueSoon->isNotEmpty()) {
                Notification::make()
                    ->title("Besluiten met deadline < {$days}d: ".$decisionsDueSoon->count())
                    ->body($this->formatDecisions($decisionsDueSoon))
                    ->warning()
                    ->icon('heroicon-o-clock')
                    ->sendToDatabase($user);
            }
        }

        $this->info('Notificaties verzonden naar '.$recipients->count().' ontvanger(s).');
        return self::SUCCESS;
    }

    private function formatActions($items, string $label): string
    {
        return $items->take(5)->map(function (ActionItem $a) use ($label) {
            $who = $a->assignee ? $a->assignee->last_name.', '.$a->assignee->first_name : 'niet toegewezen';
            $date = $a->due_date?->format('d-m-Y');
            return "• {$a->title} — {$who} ({$label} {$date})";
        })->implode("\n").($items->count() > 5 ? "\n...en ".($items->count() - 5).' meer' : '');
    }

    private function formatDecisions($items): string
    {
        return $items->take(5)->map(function (Decision $d) {
            $who = $d->responsible ? $d->responsible->last_name.', '.$d->responsible->first_name : 'niet toegewezen';
            $date = $d->deadline?->format('d-m-Y');
            return "• #{$d->decision_number} {$d->subject} — {$who} (deadline {$date})";
        })->implode("\n").($items->count() > 5 ? "\n...en ".($items->count() - 5).' meer' : '');
    }
}
