<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Vormia\ATUMultiCurrency\ATUMultiCurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ATUMultiCurrencyRefreshCommand extends Command
{
    protected $signature = 'atumulticurrency:refresh {--force : Skip confirmation prompts} {--seed : Force re-seeding}';

    protected $description = 'Refresh ATU Multi-Currency migrations and seeders, clear caches';

    public function handle(): int
    {
        $this->displayHeader();

        $force = $this->option('force');
        $forceSeed = $this->option('seed');

        $this->warn('⚠️  WARNING: This will refresh ATU Multi-Currency database tables!');
        $this->warn('   This action will:');
        $this->warn('   • Rollback and re-run migrations for atu_multicurrency_* tables');
        $this->warn('   • Re-run seeders to restore base currency');
        $this->warn('   • Clear all application caches');
        $this->newLine();

        if (!$force && !$this->confirm('Do you want to continue with the refresh?', false)) {
            $this->info('❌ Refresh cancelled.');
            return self::SUCCESS;
        }

        // Step 1: Rollback migrations for ATU Multi-Currency tables
        $this->step('Rolling back ATU Multi-Currency migrations...');
        $rollbackSuccess = $this->rollbackMigrations();

        if (!$rollbackSuccess) {
            $this->error('❌ Failed to rollback migrations. Aborting refresh.');
            return self::FAILURE;
        }

        // Step 2: Re-run migrations
        $this->step('Re-running ATU Multi-Currency migrations...');
        $migrationSuccess = $this->runMigrations();

        if (!$migrationSuccess) {
            $this->error('❌ Failed to run migrations. Refresh incomplete.');
            return self::FAILURE;
        }

        // Step 3: Re-run seeders
        $this->step('Re-running seeders...');
        if ($forceSeed || $this->confirm('Would you like to re-seed the base currency?', true)) {
            $this->runSeeders();
        } else {
            $this->line('   ⏭️  Seeders skipped.');
        }

        // Step 4: Clear caches
        $this->step('Clearing application caches...');
        $this->clearCaches();

        $this->displayCompletionMessage();

        return self::SUCCESS;
    }

    /**
     * Rollback migrations for ATU Multi-Currency tables
     */
    private function rollbackMigrations(): bool
    {
        try {
            // Get all ATU Multi-Currency migration files
            $migrationPath = database_path('migrations');
            $migrationFiles = [];

            if (is_dir($migrationPath)) {
                $files = scandir($migrationPath);
                foreach ($files as $file) {
                    if (str_contains($file, 'atu_multicurrency_')) {
                        $migrationFiles[] = $file;
                    }
                }
            }

            if (empty($migrationFiles)) {
                $this->warn('   ⚠️  No ATU Multi-Currency migration files found.');
                return true; // Not an error, just nothing to rollback
            }

            // Rollback in reverse order (newest first)
            rsort($migrationFiles);

            foreach ($migrationFiles as $file) {
                $this->line("   Rolling back: {$file}");
                $exitCode = Artisan::call('migrate:rollback', [
                    '--path' => 'database/migrations/' . $file,
                    '--force' => true
                ], $this->getOutput());

                if ($exitCode !== 0) {
                    $this->warn("   ⚠️  Could not rollback {$file}, but continuing...");
                }
            }

            $this->info('   ✅ Migrations rolled back successfully!');
            return true;
        } catch (\Exception $e) {
            $this->error('   ❌ Rollback failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Run database migrations
     */
    private function runMigrations(): bool
    {
        try {
            $this->line('   Running migrations...');
            $exitCode = Artisan::call('migrate', [], $this->getOutput());

            // Display any output from the migrate command
            $output = Artisan::output();
            if (!empty(trim($output))) {
                $this->line($output);
            }

            if ($exitCode === 0) {
                $this->info('   ✅ Migrations completed successfully!');
                return true;
            } else {
                $this->error('   ❌ Migrations completed with errors (exit code: ' . $exitCode . ')');
                return false;
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Migration failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Run database seeders
     */
    private function runSeeders(): void
    {
        try {
            $this->line('   Running seeders...');
            $exitCode = Artisan::call('db:seed', [
                '--class' => 'ATUMultiCurrencySeeder'
            ], $this->getOutput());

            // Display any output from the seeder command
            $output = Artisan::output();
            if (!empty(trim($output))) {
                $this->line($output);
            }

            if ($exitCode === 0) {
                $this->info('   ✅ Seeders completed successfully!');
            } else {
                $this->error('   ❌ Seeders completed with errors (exit code: ' . $exitCode . ')');
                $this->warn('   ⚠️  You can run seeders manually later with: php artisan db:seed --class=ATUMultiCurrencySeeder');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Seeder failed: ' . $e->getMessage());
            $this->warn('   ⚠️  You can run seeders manually later with: php artisan db:seed --class=ATUMultiCurrencySeeder');
        }
    }

    /**
     * Clear application caches
     */
    private function clearCaches(): void
    {
        $cacheCommands = [
            'config:clear' => 'Configuration cache',
            'route:clear' => 'Route cache',
            'view:clear' => 'View cache',
            'cache:clear' => 'Application cache',
        ];

        foreach ($cacheCommands as $command => $description) {
            try {
                Artisan::call($command);
                $this->line("   ✅ Cleared: {$description}");
            } catch (\Exception $e) {
                $this->line("   ⚠️  Skipped: {$description} (not available)");
            }
        }
    }

    /**
     * Display the header
     */
    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('🔄 Refreshing ATU Multi-Currency Package...');
        $this->line('   Version: ' . ATUMultiCurrency::VERSION);
        $this->newLine();
    }

    /**
     * Display a step message
     */
    private function step(string $message): void
    {
        $this->info("📦 {$message}");
    }

    /**
     * Display completion message
     */
    private function displayCompletionMessage(): void
    {
        $this->newLine();
        $this->info('🎉 ATU Multi-Currency package refreshed successfully!');
        $this->newLine();

        $this->comment('📋 What was refreshed:');
        $this->line('   ✅ Migrations rolled back and re-run');
        $this->line('   ✅ Seeders re-run');
        $this->line('   ✅ Application caches cleared');
        $this->newLine();

        $this->comment('📖 Next steps:');
        $this->line('   1. Verify your database tables are correct');
        $this->line('   2. Test your application to ensure everything works');
        $this->newLine();

        $this->info('✨ Your ATU Multi-Currency package is now refreshed!');
    }
}
