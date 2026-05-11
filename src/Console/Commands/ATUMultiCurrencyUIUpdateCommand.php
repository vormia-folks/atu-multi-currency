<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ATUMultiCurrencyUIUpdateCommand extends Command
{
    protected $signature = 'atumulticurrency:ui-update';

    protected $description = 'Clear caches after updating the package (UI lives in vendor; run composer update to pull changes)';

    public function handle(): int
    {
        $this->info('Clearing caches for ATU Multi-Currency UI...');

        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
                $this->line('  ' . $command);
            } catch (\Exception $e) {
                $this->line('  skipped: ' . $command);
            }
        }

        $this->newLine();
        $this->info('Done. Update the package with: composer update vormia-folks/atu-multi-currency');

        return self::SUCCESS;
    }
}
