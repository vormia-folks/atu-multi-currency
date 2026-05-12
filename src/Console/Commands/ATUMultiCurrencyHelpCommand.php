<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Vormia\ATUMultiCurrency\ATUMultiCurrency;
use Vormia\ATUMultiCurrency\Database\Seeders\ATUMultiCurrencySeeder;
use Illuminate\Console\Command;

class ATUMultiCurrencyHelpCommand extends Command
{
    protected $signature = 'atumulticurrency:help';

    protected $description = 'Display help for ATU Multi-Currency package commands';

    public function handle(): int
    {
        $this->newLine();
        $this->info('ATU Multi-Currency ' . ATUMultiCurrency::VERSION);
        $this->newLine();

        $this->line('Migrations, API routes, merged config, and admin Livewire UI routes load from the package.');
        $this->line('No stub files are copied into your app during atumulticurrency:install.');
        $this->newLine();

        $this->comment('Commands');
        $rows = [
            ['atumulticurrency:install', 'Optional .env keys; prompt migrate/seed', '--skip-env'],
            ['atumulticurrency:refresh', 'Rollback and re-run package migrations; optional seed', '--force, --seed'],
            ['atumulticurrency:uninstall', 'Remove .env keys; optional DB rollback', '--keep-env, --force'],
            ['atumulticurrency:ui-install', 'Verify UI deps; optional Flux sidebar', '--inject-sidebar'],
            ['atumulticurrency:ui-update', 'Clear caches after composer update', ''],
            ['atumulticurrency:ui-uninstall', 'Remove legacy copied views + marked route/sidebar snippets', '--force'],
            ['atumulticurrency:help', 'This screen', ''],
        ];
        foreach ($rows as $r) {
            $this->line('  ' . $r[0]);
            $this->line('    ' . $r[1]);
            if ($r[2] !== '') {
                $this->line('    Options: ' . $r[2]);
            }
            $this->newLine();
        }

        $this->comment('Environment');
        $this->line('  ATU_CURRENCY_API_KEY=');
        $this->line('  ATU_CURRENCY_UPDATE_FREQUENCY=daily');
        $this->line('  ATU_CURRENCY_SETTINGS_SOURCE=database');
        $this->newLine();

        $this->comment('HTTP');
        $this->line('  API (package): prefix /api/atu/currency — names like api.atu.currency.index');
        $this->line('  Admin Livewire (package): /admin/atu/currencies — names like admin.atu.currencies.index');
        $this->newLine();

        $this->comment('Seeder');
        $this->line('  php artisan db:seed --class=' . ATUMultiCurrencySeeder::class);
        $this->newLine();

        $this->comment('Publish config (optional)');
        $this->line('  php artisan vendor:publish --tag=atumulticurrency-config');
        $this->newLine();

        return self::SUCCESS;
    }
}
