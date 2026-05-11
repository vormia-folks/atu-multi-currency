<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Vormia\ATUMultiCurrency\ATUMultiCurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ATUMultiCurrencyRefreshCommand extends Command
{
    protected $signature = 'atumulticurrency:refresh {--force : Skip confirmation prompts} {--seed : Force re-seeding}';

    protected $description = 'Refresh ATU Multi-Currency migrations and seeders, clear caches';

    public function handle(): int
    {
        $this->displayHeader();

        $force = $this->option('force');
        $forceSeed = $this->option('seed');

        $this->warn('This will roll back and re-run ATU Multi-Currency migrations from the package.');
        $this->newLine();

        if (! $force && ! $this->confirm('Do you want to continue with the refresh?', false)) {
            $this->info('Refresh cancelled.');

            return self::SUCCESS;
        }

        $this->step('Rolling back ATU Multi-Currency migrations...');
        if (! $this->rollbackMigrations()) {
            $this->error('Failed to roll back migrations. Aborting refresh.');

            return self::FAILURE;
        }

        $this->step('Re-running ATU Multi-Currency migrations...');
        if (! $this->runMigrations()) {
            $this->error('Failed to run migrations. Refresh incomplete.');

            return self::FAILURE;
        }

        $this->step('Re-running seeders...');
        if ($forceSeed || $this->confirm('Would you like to re-seed the base currency?', true)) {
            $this->runSeeders();
        } else {
            $this->line('   Seeders skipped.');
        }

        $this->step('Clearing application caches...');
        $this->clearCaches();

        $this->displayCompletionMessage();

        return self::SUCCESS;
    }

    private function rollbackMigrations(): bool
    {
        try {
            $relativeDir = ATUMultiCurrency::migrationsPathRelativeToBase();
            $files = collect(File::files(ATUMultiCurrency::migrationsPath()))
                ->map(fn (\SplFileInfo $f) => $f->getFilename())
                ->sort()
                ->values()
                ->reverse();

            if ($files->isEmpty()) {
                $this->warn('   No migration files found in the package.');

                return true;
            }

            foreach ($files as $filename) {
                $pathArg = $relativeDir . '/' . $filename;
                $this->line('   Rolling back: ' . $filename);
                Artisan::call('migrate:rollback', [
                    '--path' => $pathArg,
                    '--force' => true,
                ], $this->getOutput());
            }

            $this->info('   Rollback pass completed.');

            return true;
        } catch (\Exception $e) {
            $this->error('   Rollback failed: ' . $e->getMessage());

            return false;
        }
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

            $this->error('   Migrations failed (exit code: ' . $exitCode . ')');

            return false;
        } catch (\Exception $e) {
            $this->error('   Migration failed: ' . $e->getMessage());

            return false;
        }
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
                $this->error('   Seeders failed (exit code: ' . $exitCode . ')');
            }
        } catch (\Exception $e) {
            $this->error('   Seeder failed: ' . $e->getMessage());
        }
    }

    private function clearCaches(): void
    {
        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
                $this->line('   Cleared: ' . $command);
            } catch (\Exception $e) {
                $this->line('   Skipped: ' . $command);
            }
        }
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('Refreshing ATU Multi-Currency...');
        $this->line('   Version: ' . ATUMultiCurrency::VERSION);
        $this->newLine();
    }

    private function step(string $message): void
    {
        $this->info($message);
    }

    private function displayCompletionMessage(): void
    {
        $this->newLine();
        $this->info('Refresh finished.');
        $this->newLine();
    }
}
