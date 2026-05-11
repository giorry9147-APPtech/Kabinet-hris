<?php

namespace App\Console\Commands;

use Database\Seeders\DemoFillSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Vult de overige modules (certificaten, asset-requests, employee documents)
 * met realistische demo-data zodat alle navigatie-items in /admin gevulde
 * lijsten tonen. Idempotent — veilig om meerdere keren te draaien.
 *
 * Productie:
 *   fly ssh console --app kabinet-hris-backend \
 *     -C "php /var/www/html/artisan kabinet:install-demo-fill"
 */
class InstallDemoFill extends Command
{
    protected $signature = 'kabinet:install-demo-fill';

    protected $description = 'Installeert idempotent extra demo-data (certificaten, asset-requests, employee documents) op de bestaande DB.';

    public function handle(DemoFillSeeder $seeder): int
    {
        $this->info('=== Demo-data fill installer ===');
        $this->newLine();

        DB::transaction(function () use ($seeder) {
            $seeder->setCommand($this);
            $seeder->run();
        });

        $this->newLine();
        $this->info('Klaar — alle modules tonen nu gevulde lijsten in /admin.');
        $this->line('  • Kabinet-specifieke certificaat-types + ~25 certificaten met diverse vervalstatus');
        $this->line('  • 6 asset-requests (3 pending / 2 approved / 1 rejected)');
        $this->line('  • ~45 employee documents (CV, ID, contract, medisch, belasting)');

        return self::SUCCESS;
    }
}
