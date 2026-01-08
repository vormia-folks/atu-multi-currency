<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class ATUMultiCurrencyUIUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'atumulticurrency:ui-update {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update ATU Multi-Currency UI package files (removes old files and copies fresh ones)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Updating ATU Multi-Currency UI Package...');
        $this->newLine();

        $force = $this->option('force');

        // Warning message
        $this->warn('⚠️  WARNING: This will replace existing UI files with fresh copies.');
        $this->warn('   Make sure you have backed up any custom modifications.');
        $this->newLine();

        if (!$force && !$this->confirm('Do you want to continue with the update?', false)) {
            $this->info('❌ Update cancelled.');
            return;
        }

        // Step 1: Create backup
        $this->step('Creating backup of existing files...');
        $this->createBackup();

        // Step 2: Update files
        $this->step('Updating UI files...');
        $this->updateUIFiles();

        // Step 3: Clear caches
        $this->step('Clearing application caches...');
        $this->clearCaches();

        $this->displayCompletionMessage();
    }

    /**
     * Display a step message
     */
    private function step($message)
    {
        $this->info("📦 {$message}");
    }

    /**
     * Create backup of existing files
     */
    private function createBackup()
    {
        $backupDir = storage_path('app/atu-multicurrency-ui-backups/' . date('Y-m-d-H-i-s'));

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filesToBackup = [
            resource_path('views/livewire/admin/atu') => $backupDir . '/views/livewire/admin/atu',
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

        $this->info("✅ Backup created in: {$backupDir}");
    }

    /**
     * Update UI files
     */
    private function updateUIFiles(): void
    {
        $stubsPath = __DIR__ . '/../../stubs/resources/views/livewire/admin/atu';
        $targetPath = resource_path('views/livewire/admin/atu');

        if (!File::exists($stubsPath)) {
            $this->error('❌ UI stubs not found at: ' . $stubsPath);
            return;
        }

        // Remove existing files
        if (File::exists($targetPath)) {
            File::deleteDirectory($targetPath);
        }

        // Copy fresh files
        File::ensureDirectoryExists($targetPath);
        File::copyDirectory($stubsPath, $targetPath);

        $this->info('✅ UI files updated successfully.');
    }

    /**
     * Clear application caches
     */
    private function clearCaches()
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
                $this->line("  Cleared: {$description}");
            } catch (\Exception $e) {
                $this->line("  Skipped: {$description} (not available)");
            }
        }

        $this->info('✅ Caches cleared successfully.');
    }

    /**
     * Display completion message
     */
    private function displayCompletionMessage()
    {
        $this->newLine();
        $this->info('🎉 ATU Multi-Currency UI package updated successfully!');
        $this->newLine();

        $this->comment('📋 What was updated:');
        $this->line('   ✅ All UI files replaced with fresh copies');
        $this->line('   ✅ Backups created in storage/app/atu-multicurrency-ui-backups/');
        $this->line('   ✅ Application caches cleared');
        $this->newLine();

        $this->comment('📖 Next steps:');
        $this->line('   1. Review any custom modifications in your backup files');
        $this->line('   2. Test your application to ensure everything works correctly');
        $this->newLine();

        $this->info('✨ Your ATU Multi-Currency UI package is now up to date!');
    }
}
