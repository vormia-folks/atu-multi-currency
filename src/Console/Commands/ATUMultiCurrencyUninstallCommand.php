<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Vormia\ATUMultiCurrency\ATUMultiCurrency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vormia\ATUMultiCurrency\Support\Installer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class ATUMultiCurrencyUninstallCommand extends Command
{
    protected $signature = 'atumulticurrency:uninstall {--keep-env : Leave env keys untouched} {--force : Skip confirmation prompts}';

    protected $description = 'Remove all ATU Multi-Currency package files and configurations';

    public function handle(Installer $installer): int
    {
        $this->displayHeader();

        $force = $this->option('force');
        $keepEnv = $this->option('keep-env');

        // Warning message
        $this->error('⚠️  DANGER: This will remove ATU Multi-Currency from your application!');
        $this->warn('   This action will:');
        $this->warn('   • Remove all ATU Multi-Currency copied files and stubs (only files originally installed by the package)');
        $this->warn('   • Remove currency routes from routes/api.php');
        $this->warn('   • Note: Composer packages are NOT uninstalled');
        $this->newLine();

        if (!$force && !$this->confirm('Are you absolutely sure you want to uninstall ATU Multi-Currency?', false)) {
            $this->info('❌ Uninstall cancelled.');
            return self::SUCCESS;
        }

        // Ask about migrations
        $undoMigrations = false;
        if (!$force) {
            $this->newLine();
            $this->error('⚠️  WARNING: Rolling back migrations will DELETE ALL DATA in ATU Multi-Currency database tables!');
            $this->warn('   This includes: currencies, rates logs, and conversion logs.');
            $undoMigrations = $this->confirm('Do you wish to undo migrations? (This will rollback and delete migration files)', false);
        } else {
            // In force mode, default to not rolling back migrations for safety
            $undoMigrations = false;
        }

        // Ask about .env variables
        $removeEnvVars = false;
        if (!$keepEnv && !$force) {
            $this->newLine();
            $removeEnvVars = $this->confirm('Do you wish to remove ATU Multi-Currency environment variables from .env and .env.example?', false);
        } elseif ($keepEnv) {
            $removeEnvVars = false;
        } else {
            // In force mode without --keep-env, default to removing env vars
            $removeEnvVars = true;
        }

        // Step 1: Create backup of existing ATU Multi-Currency-related files
        $this->step('Creating final backup...');
        $this->createFinalBackup();

        // Step 2: Let the Installer remove only files that were installed from stubs
        $this->step('Removing ATU Multi-Currency files and stubs (file-by-file)...');
        $touchEnv = $removeEnvVars;
        $results = $installer->uninstall($touchEnv);

        $removedFiles = $results['removed'] ?? [];
        $removedCount = count($removedFiles);

        if ($removedCount > 0) {
            foreach ($removedFiles as $file) {
                $this->line("   ✅ Removed: " . $this->getRelativePath($file));
            }
            $this->info("   ✅ {$removedCount} installed file(s) removed successfully.");
        } else {
            $this->warn('   ⚠️  No installed files found to remove.');
            $this->line('   This could mean files were already deleted or the package was never installed.');
        }

        // Step 3: Environment variables
        $this->step('Cleaning up environment files...');
        if ($removeEnvVars) {
            $this->handleEnvResults($results['env'] ?? []);
        } else {
            $this->line('   ⏭️  Environment keys preserved (skipped by user choice).');
        }

        // Step 4: Routes
        $this->step('Removing API routes...');
        $this->handleRoutes($results['routes'] ?? []);

        // Step 5: Remove migrations for ATU Multi-Currency
        if ($undoMigrations) {
            $this->step('Rolling back and removing ATU Multi-Currency migrations...');
            $this->removeMigrations();
        } else {
            $this->step('Skipping migration rollback...');
            $this->line('   ⏭️  Migrations preserved (skipped by user choice).');
            $this->line('   ⚠️  Note: Migration files and database tables remain. You may need to drop tables manually.');
        }

        // Step 6: Clear caches
        $this->step('Clearing application caches...');
        $this->clearCaches();

        $this->displayCompletionMessage($removeEnvVars, $undoMigrations);

        return self::SUCCESS;
    }

    /**
     * Get relative path from base path for display
     */
    private function getRelativePath(string $absolutePath): string
    {
        $basePath = base_path();
        if (str_starts_with($absolutePath, $basePath)) {
            return ltrim(str_replace($basePath, '', $absolutePath), '/\\');
        }
        return $absolutePath;
    }

    /**
     * Handle environment file results
     */
    private function handleEnvResults(array $envResults): void
    {
        $envCleaned = false;
        $filesChecked = [];

        foreach ($envResults as $file => $keys) {
            $filesChecked[] = basename($file);

            if ($keys !== []) {
                $this->info("   ✅ Removed from " . basename($file) . ": " . implode(', ', $keys));
                $envCleaned = true;
            } else {
                $this->line("   ℹ️  " . basename($file) . " does not contain ATU Multi-Currency keys");
            }
        }

        if (empty($filesChecked)) {
            $this->warn('   ⚠️  No .env or .env.example files found.');
        } elseif (!$envCleaned) {
            $this->info('   ✅ No ATU Multi-Currency environment keys found to remove.');
        }
    }

    /**
     * Handle routes results
     */
    private function handleRoutes(array $routes): void
    {
        if ($routes !== []) {
            if ($routes['removed'] ?? false) {
                $this->info('   ✅ Currency routes removed from routes/api.php');
            } else {
                $this->info('   ✅ No route block found to remove.');
            }
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
                \Illuminate\Support\Facades\Artisan::call($command);
                $this->line("   ✅ Cleared: {$description}");
            } catch (\Exception $e) {
                $this->line("   ⚠️  Skipped: {$description} (not available)");
            }
        }
    }

    /**
     * Create final backup before uninstallation
     */
    private function createFinalBackup(): void
    {
        $backupDir = storage_path('app/atumulticurrency-final-backup-' . date('Y-m-d-H-i-s'));

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filesToBackup = [
            config_path('atu-multi-currency.php') => $backupDir . '/config/atu-multi-currency.php',
            database_path('seeders/ATUMultiCurrencySeeder.php') => $backupDir . '/seeders/ATUMultiCurrencySeeder.php',
            base_path('routes/api.php') => $backupDir . '/routes/api.php',
            base_path('.env') => $backupDir . '/.env',
        ];

        foreach ($filesToBackup as $source => $destination) {
            if (File::exists($source)) {
                if (File::isDirectory($source)) {
                    File::copyDirectory($source, $destination);
                } else {
                    File::ensureDirectoryExists(dirname($destination));
                    File::copy($source, $destination);
                }
            }
        }

        $this->info("   ✅ Final backup created in: " . $this->getRelativePath($backupDir));
    }

    /**
     * Display the header
     */
    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('🗑️  Uninstalling ATU Multi-Currency Package...');
        $this->line('   Version: ' . ATUMultiCurrency::VERSION);
        $this->newLine();
    }

    /**
     * Display a step message
     */
    private function step(string $message): void
    {
        $this->info("🗂️  {$message}");
    }

    /**
     * Remove database tables
     */
    private function removeDatabaseTables(): void
    {
        try {
            $prefix = 'atu_multicurrency_';

            // Get all tables with ATU Multi-Currency prefix
            $tables = DB::select("SHOW TABLES LIKE '{$prefix}%'");

            if (empty($tables)) {
                $this->line('   ℹ️  No ATU Multi-Currency tables found.');
                return;
            }

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
                $this->line("   ✅ Dropped table: {$tableName}");
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('   ✅ Database tables removed successfully.');
        } catch (\Exception $e) {
            $this->error("   ❌ Error removing database tables: " . $e->getMessage());
            $this->warn('   ⚠️  You may need to manually remove the tables.');
        }
    }

    /**
     * Remove migration files
     */
    private function removeMigrations(): void
    {
        // Step 1: Drop database tables directly using SQL (most reliable method)
        $this->removeDatabaseTables();

        // Step 2: Attempt to rollback migrations (for cleanup/verification)
        $migrationPath = database_path('migrations');
        if (!File::isDirectory($migrationPath)) {
            $this->line("   ℹ️  Migrations directory does not exist");
            return;
        }

        $removed = 0;
        $rolledBack = false;

        foreach (File::files($migrationPath) as $file) {
            if (str_contains($file->getFilename(), 'atu_multicurrency_')) {
                try {
                    Artisan::call('migrate:rollback', ['--path' => 'database/migrations/' . $file->getFilename(), '--force' => true]);
                    $this->line('   Rolled back migration: ' . $file->getFilename());
                    $rolledBack = true;
                } catch (\Exception $e) {
                    $this->warn('   Could not rollback migration: ' . $file->getFilename() . ' (' . $e->getMessage() . ')');
                }

                // Step 3: Delete migration files
                File::delete($file->getPathname());
                $removed++;
            }
        }

        if ($removed === 0) {
            $this->line("   ℹ️  No ATU Multi-Currency migrations found to remove");
            return;
        }

        if (! $rolledBack && $removed > 0) {
            $this->line('   ℹ️  Note: Some migrations could not be rolled back, but tables were dropped directly.');
        }
    }

    /**
     * Display completion message
     */
    private function displayCompletionMessage(bool $envRemoved, bool $migrationsUndone): void
    {
        $this->newLine();
        $this->info('🎉 ATU Multi-Currency package uninstalled successfully!');
        $this->newLine();

        $this->comment('📋 What was removed:');
        $this->line('   ✅ All ATU Multi-Currency copied files and stubs');
        $this->line('   ✅ Currency routes from routes/api.php');
        if ($envRemoved) {
            $this->line('   ✅ ATU Multi-Currency environment variables');
        } else {
            $this->line('   ⏭️  Environment variables preserved (skipped by user choice)');
        }
        if ($migrationsUndone) {
            $this->line('   ✅ ATU Multi-Currency migrations rolled back and migration files deleted');
        } else {
            $this->line('   ⏭️  Migrations preserved (skipped by user choice)');
        }
        $this->line('   ✅ Application caches cleared');
        $this->line('   ✅ Final backup created in storage/app/');
        $this->newLine();

        $this->comment('📖 Final steps:');
        $this->line('   1. Remove "vormia-folks/atu-multi-currency" from your composer.json');
        $this->line('   2. Run: composer remove vormia-folks/atu-multi-currency');
        if (!$migrationsUndone) {
            $this->line('   3. Manually remove database tables if needed (migrations were not rolled back)');
            $this->line('   4. Review your application for any remaining ATU Multi-Currency references');
        } else {
            $this->line('   3. Review your application for any remaining ATU Multi-Currency references');
        }
        $this->newLine();

        if (!$envRemoved) {
            $this->warn('⚠️  Note: Environment variables were preserved. Remove them manually if needed.');
            $this->newLine();
        }

        if (!$migrationsUndone) {
            $this->warn('⚠️  Note: Migration files and database tables remain. Remove them manually if needed.');
            $this->newLine();
        }

        $this->info('✨ Thank you for using ATU Multi-Currency!');
    }
}
