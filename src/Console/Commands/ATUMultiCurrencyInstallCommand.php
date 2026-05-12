<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Vormia\ATUMultiCurrency\ATUMultiCurrency;
use Vormia\ATUMultiCurrency\Support\Installer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ATUMultiCurrencyInstallCommand extends Command
{
    protected $signature = 'atumulticurrency:install {--skip-env : Do not modify .env files}';

    protected $description = 'Install ATU Multi-Currency: optional .env keys, then migrate/seed (code loads from the package)';

    public function handle(Installer $installer): int
    {
        $this->displayHeader();

        $touchEnv = ! $this->option('skip-env');

        $this->step('Registering package (no stub files are copied; routes, config, migrations, and Livewire views load from vendor).');
        $results = $installer->install($touchEnv);

        $this->step('Updating environment files...');
        if ($touchEnv) {
            $this->reportEnvResults($results['env'] ?? []);
        } else {
            $this->line('   Skipped (--skip-env).');
        }

        $migrationsRun = $this->handleMigrations();

        if ($migrationsRun) {
            $this->handleSeeders();
        }

        $this->displayCompletionMessage($touchEnv, $migrationsRun);

        return self::SUCCESS;
    }

    private function reportEnvResults(array $envResults): void
    {
        $touched = false;
        foreach ($envResults as $file => $keys) {
            if ($keys !== []) {
                $this->info('   Added to ' . basename($file) . ': ' . implode(', ', $keys));
                $touched = true;
            }
        }
        if (! $touched) {
            $this->info('   ATU Multi-Currency env keys already present (or env files missing).');
        }
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('Installing ATU Multi-Currency Package...');
        $this->line('   Version: ' . ATUMultiCurrency::VERSION);
        $this->newLine();
    }

    private function step(string $message): void
    {
        $this->info($message);
    }

    private function handleMigrations(): bool
    {
        $this->step('Running database migrations...');
        $this->line('   ATU Multi-Currency migrations load from the package (vendor); `php artisan migrate` records them like any other migration.');

        if (! $this->confirm('Would you like to run migrations now?', true)) {
            $this->line('   Migrations skipped. Run later with: php artisan migrate');

            return false;
        }

        return $this->runMigrations();
    }

    private function runMigrations(): bool
    {
        try {
            $this->line('   Running migrations...');
            $exitCode = Artisan::call('migrate', [], $this->getOutput());
            $output = Artisan::output();
            if (! empty(trim($output))) {
                $this->line($output);
            }

            if ($exitCode === 0) {
                $this->info('   Migrations completed successfully.');

                return true;
            }

            $this->error('   Migrations completed with errors (exit code: ' . $exitCode . ')');
            $this->warn('   You can run migrations manually later with: php artisan migrate');

            return false;
        } catch (\Exception $e) {
            $this->error('   Migration failed: ' . $e->getMessage());
            $this->warn('   You can run migrations manually later with: php artisan migrate');

            return false;
        }
    }

    private function handleSeeders(): void
    {
        $this->step('Running database seeders...');

        if (! $this->confirm('Would you like to seed the base currency now?', true)) {
            $this->line('   Seeders skipped. Run later with: php artisan db:seed --class=' . \Vormia\ATUMultiCurrency\Database\Seeders\ATUMultiCurrencySeeder::class);

            return;
        }

        $this->runSeeders();
    }

    private function runSeeders(): void
    {
        try {
            $this->line('   Running seeders...');
            $exitCode = Artisan::call('db:seed', [
                '--class' => \Vormia\ATUMultiCurrency\Database\Seeders\ATUMultiCurrencySeeder::class,
            ], $this->getOutput());

            $output = Artisan::output();
            if (! empty(trim($output))) {
                $this->line($output);
            }

            if ($exitCode === 0) {
                $this->info('   Seeders completed successfully.');
            } else {
                $this->error('   Seeders completed with errors (exit code: ' . $exitCode . ')');
                $this->warn('   Run manually: php artisan db:seed --class=' . \Vormia\ATUMultiCurrency\Database\Seeders\ATUMultiCurrencySeeder::class);
            }
        } catch (\Exception $e) {
            $this->error('   Seeder failed: ' . $e->getMessage());
            $this->warn('   Run manually: php artisan db:seed --class=' . \Vormia\ATUMultiCurrency\Database\Seeders\ATUMultiCurrencySeeder::class);
        }
    }

    private function displayCompletionMessage(bool $envTouched, bool $migrationsRun): void
    {
        $this->newLine();
        $this->info('ATU Multi-Currency is ready.');
        $this->newLine();

        $this->comment('Next steps:');
        $this->line('   1. Configure .env for currency API if you use automatic rates.');
        if (! $migrationsRun) {
            $this->line('   2. Run: php artisan migrate');
            $this->line('   3. Optional seed: php artisan db:seed --class=' . \Vormia\ATUMultiCurrency\Database\Seeders\ATUMultiCurrencySeeder::class);
        }
        $this->line('   API routes are registered by the package under /api/atu/currency');
        if (class_exists(\Livewire\Livewire::class)) {
            $this->line('   Admin Livewire UI is registered at /admin/atu/currencies.');
        } else {
            $this->line('   Run composer install (this package requires livewire/livewire ^4 for the admin UI), or merge stubs from src/stubs/reference/.');
        }
        $this->newLine();

        if (! $envTouched) {
            $this->warn('Note: Environment keys were not modified (--skip-env).');
            $this->line('   Run: php artisan atumulticurrency:help for required env keys.');
            $this->newLine();
        }

        $this->comment('Help: php artisan atumulticurrency:help');
        $this->newLine();
    }
}
