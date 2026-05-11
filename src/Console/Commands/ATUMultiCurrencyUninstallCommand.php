<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Vormia\ATUMultiCurrency\ATUMultiCurrency;
use Vormia\ATUMultiCurrency\Support\Installer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ATUMultiCurrencyUninstallCommand extends Command
{
    protected $signature = 'atumulticurrency:uninstall {--keep-env : Leave env keys untouched} {--force : Skip confirmation prompts}';

    protected $description = 'Remove optional ATU Multi-Currency .env keys and optionally roll back database tables (package code stays in vendor)';

    public function handle(Installer $installer): int
    {
        $this->displayHeader();

        $force = $this->option('force');
        $keepEnv = $this->option('keep-env');

        $this->error('This will remove ATU Multi-Currency configuration from your application.');
        $this->warn('The Composer package is not removed. Routes and views are registered by the package until you remove it.');
        $this->newLine();

        if (! $force && ! $this->confirm('Are you absolutely sure you want to continue?', false)) {
            $this->info('Uninstall cancelled.');

            return self::SUCCESS;
        }

        $undoMigrations = false;
        if (! $force) {
            $this->newLine();
            $this->error('Rolling back migrations will delete all data in ATU Multi-Currency tables.');
            $undoMigrations = $this->confirm('Do you wish to roll back ATU Multi-Currency migrations?', false);
        }

        $removeEnvVars = false;
        if (! $keepEnv && ! $force) {
            $this->newLine();
            $removeEnvVars = $this->confirm('Remove ATU Multi-Currency environment variables from .env and .env.example?', false);
        } elseif ($keepEnv) {
            $removeEnvVars = false;
        } else {
            $removeEnvVars = true;
        }

        $this->step('Creating backup snapshot...');
        $this->createFinalBackup();

        $this->step('Environment cleanup...');
        $touchEnv = $removeEnvVars;
        $results = $installer->uninstall($touchEnv);

        if ($removeEnvVars) {
            $this->handleEnvResults($results['env'] ?? []);
        } else {
            $this->line('   Environment keys preserved.');
        }

        if ($undoMigrations) {
            $this->step('Rolling back ATU Multi-Currency migrations...');
            $this->removeMigrations();
        } else {
            $this->step('Skipping migration rollback...');
            $this->line('   Database tables and migration history were left unchanged.');
        }

        $this->step('Clearing application caches...');
        $this->clearCaches();

        $this->displayCompletionMessage($removeEnvVars, $undoMigrations);

        return self::SUCCESS;
    }

    private function getRelativePath(string $absolutePath): string
    {
        $basePath = base_path();
        if (str_starts_with($absolutePath, $basePath)) {
            return ltrim(str_replace($basePath, '', $absolutePath), '/\\');
        }

        return $absolutePath;
    }

    private function handleEnvResults(array $envResults): void
    {
        $envCleaned = false;
        foreach ($envResults as $file => $keys) {
            if ($keys !== []) {
                $this->info('   Removed from ' . basename($file) . ': ' . implode(', ', $keys));
                $envCleaned = true;
            } else {
                $this->line('   ' . basename($file) . ' had no ATU Multi-Currency keys to remove.');
            }
        }
        if (! $envCleaned) {
            $this->info('   No ATU Multi-Currency environment keys found to remove.');
        }
    }

    private function clearCaches(): void
    {
        $cacheCommands = ['config:clear', 'route:clear', 'view:clear', 'cache:clear'];

        foreach ($cacheCommands as $command) {
            try {
                Artisan::call($command);
                $this->line('   Cleared: ' . $command);
            } catch (\Exception $e) {
                $this->line('   Skipped: ' . $command);
            }
        }
    }

    private function createFinalBackup(): void
    {
        $backupDir = storage_path('app/atumulticurrency-final-backup-' . date('Y-m-d-H-i-s'));

        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filesToBackup = [
            config_path('atu-multi-currency.php') => $backupDir . '/config/atu-multi-currency.php',
            base_path('.env') => $backupDir . '/.env',
        ];

        foreach ($filesToBackup as $source => $destination) {
            if (File::exists($source) && File::isFile($source)) {
                File::ensureDirectoryExists(dirname($destination));
                File::copy($source, $destination);
            }
        }

        $this->info('   Backup directory: ' . $this->getRelativePath($backupDir));
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('Uninstalling ATU Multi-Currency (host configuration only)...');
        $this->line('   Version: ' . ATUMultiCurrency::VERSION);
        $this->newLine();
    }

    private function step(string $message): void
    {
        $this->info($message);
    }

    private function removeDatabaseTables(): void
    {
        $tables = [
            'atu_multicurrency_currency_conversion_log',
            'atu_multicurrency_currency_rates_log',
            'atu_multicurrency_settings',
            'atu_multicurrency_currencies',
        ];

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
                $this->line('   Dropped table: ' . $table);
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function forgetMigrationRows(): void
    {
        foreach (File::files(ATUMultiCurrency::migrationsPath()) as $file) {
            $migration = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            DB::table('migrations')->where('migration', $migration)->delete();
        }
    }

    private function removeMigrations(): void
    {
        $relativeDir = ATUMultiCurrency::migrationsPathRelativeToBase();
        $files = collect(File::files(ATUMultiCurrency::migrationsPath()))
            ->map(fn (\SplFileInfo $f) => $f->getFilename())
            ->sort()
            ->values()
            ->reverse();

        foreach ($files as $filename) {
            $pathArg = $relativeDir . '/' . $filename;
            try {
                Artisan::call('migrate:rollback', [
                    '--path' => $pathArg,
                    '--force' => true,
                ], $this->getOutput());
                $this->line('   Rolled back: ' . $filename);
            } catch (\Exception $e) {
                $this->warn('   Could not rollback ' . $filename . ': ' . $e->getMessage());
            }
        }

        $this->removeDatabaseTables();
        $this->forgetMigrationRows();
        $this->info('   Package migration rows cleared from the migrations table.');
    }

    private function displayCompletionMessage(bool $envRemoved, bool $migrationsUndone): void
    {
        $this->newLine();
        $this->info('Uninstall step finished.');
        $this->newLine();

        $this->comment('Summary:');
        if ($envRemoved) {
            $this->line('   Environment variables removed from .env / .env.example (where present).');
        } else {
            $this->line('   Environment variables were preserved.');
        }
        if ($migrationsUndone) {
            $this->line('   ATU Multi-Currency tables were dropped and migration history for this package was cleared.');
        } else {
            $this->line('   Database was not modified.');
        }
        $this->line('   Remove the package with: composer remove vormia-folks/atu-multi-currency');
        $this->newLine();
    }
}
