<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class ATUMultiCurrencyUIUninstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'atumulticurrency:ui-uninstall {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all ATU Multi-Currency UI package files and configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗑️  Uninstalling ATU Multi-Currency UI Package...');
        $this->newLine();

        $force = $this->option('force');

        // Warning message
        $this->error('⚠️  DANGER: This will completely remove ATU Multi-Currency UI from your application!');
        $this->warn('   This action will:');
        $this->warn('   • Remove all UI view files');
        $this->warn('   • Remove routes from routes/web.php');
        $this->warn('   • Remove sidebar menu code');
        $this->newLine();

        if (!$force && !$this->confirm('Are you absolutely sure you want to uninstall the UI?', false)) {
            $this->info('❌ Uninstall cancelled.');
            return;
        }

        // Final confirmation
        if (!$force) {
            $this->newLine();
            $this->error('🚨 FINAL WARNING: This action cannot be undone!');
            if (!$this->confirm('Type "yes" to proceed with UI uninstallation', false)) {
                $this->info('❌ Uninstall cancelled.');
                return;
            }
        }

        // Step 1: Create final backup
        $this->step('Creating final backup...');
        $this->createFinalBackup();

        // Step 2: Remove UI files
        $this->step('Removing UI files...');
        $this->removeUIFiles();

        // Step 3: Remove routes
        $this->step('Removing routes from routes/web.php...');
        $this->removeRoutes();

        // Step 4: Remove sidebar menu
        $this->step('Removing sidebar menu...');
        $this->removeSidebarMenu();

        // Step 5: Clear caches
        $this->step('Clearing application caches...');
        $this->clearCaches();

        $this->displayCompletionMessage();
    }

    /**
     * Display a step message
     */
    private function step($message)
    {
        $this->info("🗂️  {$message}");
    }

    /**
     * Create final backup before uninstallation
     */
    private function createFinalBackup()
    {
        $backupDir = storage_path('app/atu-multicurrency-ui-final-backup-' . date('Y-m-d-H-i-s'));

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $filesToBackup = [
            resource_path('views/livewire/admin/atu') => $backupDir . '/views/livewire/admin/atu',
            base_path('routes/web.php') => $backupDir . '/routes/web.php',
            resource_path('views/components/layouts/app/sidebar.blade.php') => $backupDir . '/views/components/layouts/app/sidebar.blade.php',
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

        $this->info("✅ Final backup created in: {$backupDir}");
    }

    /**
     * Remove UI files
     */
    private function removeUIFiles(): void
    {
        $uiPath = resource_path('views/livewire/admin/atu');

        if (File::exists($uiPath)) {
            File::deleteDirectory($uiPath);
            $this->info('✅ UI files removed successfully.');
        } else {
            $this->warn('⚠️  UI files not found at: ' . $uiPath);
        }
    }

    /**
     * Remove routes from routes/web.php by exact line matching
     */
    private function removeRoutes(): void
    {
        $routesPath = base_path('routes/web.php');

        if (!File::exists($routesPath)) {
            $this->warn('⚠️  routes/web.php not found.');
            return;
        }

        $content = File::get($routesPath);
        $lines = explode("\n", $content);

        // Define exact route patterns to match (with flexible whitespace)
        $routePatterns = [
            // Currencies routes
            "/\s*Volt::route\s*\(\s*['\"]currencies['\"]\s*,\s*['\"]admin\.atu\.currencies\.index['\"]\s*\)\s*->name\s*\(\s*['\"]admin\.atu\.currencies\.index['\"]\s*\)\s*;/",
            "/\s*Volt::route\s*\(\s*['\"]currencies\/create['\"]\s*,\s*['\"]admin\.atu\.currencies\.create['\"]\s*\)\s*->name\s*\(\s*['\"]admin\.atu\.currencies\.create['\"]\s*\)\s*;/",
            "/\s*Volt::route\s*\(\s*['\"]currencies\/edit\/\{id\}['\"]\s*,\s*['\"]admin\.atu\.currencies\.edit['\"]\s*\)\s*->name\s*\(\s*['\"]admin\.atu\.currencies\.edit['\"]\s*\)\s*;/",
            "/\s*Volt::route\s*\(\s*['\"]currencies\/settings['\"]\s*,\s*['\"]admin\.atu\.currencies\.settings['\"]\s*\)\s*->name\s*\(\s*['\"]admin\.atu\.currencies\.settings['\"]\s*\)\s*;/",
        ];

        // Also match comment lines that might be associated with these routes
        $commentPatterns = [
            "/\s*\/\/\s*ATU\s*Multi-Currency\s*Routes/",
            "/\s*\/\/\s*Currencies/",
        ];

        $removedCount = 0;
        $newLines = [];

        foreach ($lines as $line) {
            $shouldRemove = false;

            // Check if line matches any route pattern
            foreach ($routePatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $shouldRemove = true;
                    $removedCount++;
                    break;
                }
            }

            // Check if line matches comment patterns (only if it's a comment line)
            if (!$shouldRemove && preg_match('/^\s*\/\//', $line)) {
                foreach ($commentPatterns as $pattern) {
                    if (preg_match($pattern, $line)) {
                        $shouldRemove = true;
                        break;
                    }
                }
            }

            if (!$shouldRemove) {
                $newLines[] = $line;
            }
        }

        // Remove empty Route::group blocks that might be left behind
        $content = implode("\n", $newLines);
        
        // Remove empty Route::group(['prefix' => 'admin/atu'], function () { }); blocks
        $content = preg_replace('/Route::group\s*\(\s*\[\s*[\'"]prefix[\'"]\s*=>\s*[\'"]admin\/atu[\'"]\s*\]\s*,\s*function\s*\(\s*\)\s*\{\s*\}\s*\)\s*;/s', '', $content);
        
        // Remove Route::group opening with only whitespace/comments before closing
        $content = preg_replace('/Route::group\s*\(\s*\[\s*[\'"]prefix[\'"]\s*=>\s*[\'"]admin\/atu[\'"]\s*\]\s*,\s*function\s*\(\s*\)\s*\{\s*(?:\/\/.*?\n\s*)*\}\s*\)\s*;/s', '', $content);

        // Clean up extra whitespace
        $content = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $content);

        File::put($routesPath, $content);
        
        if ($removedCount > 0) {
            $this->info("✅ Removed {$removedCount} route(s) successfully.");
        } else {
            $this->warn('⚠️  No matching routes found to remove.');
        }
    }

    /**
     * Remove sidebar menu code
     */
    private function removeSidebarMenu(): void
    {
        $sidebarPath = resource_path('views/components/layouts/app/sidebar.blade.php');

        if (!File::exists($sidebarPath)) {
            $this->warn('⚠️  Sidebar file not found.');
            return;
        }

        $content = File::get($sidebarPath);
        $lines = explode("\n", $content);

        // Track which lines to remove
        $linesToRemove = [];
        $inCurrencyBlock = false;

        // First pass: identify all lines to remove
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmedLine = trim($line);

            // Check for @if (auth()->user()?->isAdminOrSuperAdmin()) before currency menu
            if (preg_match('/@if\s*\(\s*auth\(\)\s*->\s*user\(\)\s*\?->\s*isAdminOrSuperAdmin\(\)\s*\)/', $line)) {
                // Check if next few lines contain currency menu
                for ($j = $i + 1; $j < min($i + 10, count($lines)); $j++) {
                    if (preg_match('/route\(["\']admin\.atu\.currencies\.index["\']\)/', $lines[$j])) {
                        $inCurrencyBlock = true;
                        break;
                    }
                    if (preg_match('/@endif/', $lines[$j])) {
                        break;
                    }
                }
            }

            // Check for currency menu item
            if (preg_match('/<flux:navlist\.item\s+icon=["\']currency-dollar["\'].*?route\(["\']admin\.atu\.currencies\.index["\']\)/s', $line)) {
                $linesToRemove[$i] = true;
                // Continue removing until closing tag
                for ($j = $i + 1; $j < min($i + 5, count($lines)); $j++) {
                    $linesToRemove[$j] = true;
                    if (preg_match('/<\/flux:navlist\.item>/', $lines[$j])) {
                        break;
                    }
                }
            }

            // Check for HR tags before currency menu
            if ($inCurrencyBlock && preg_match('/<hr\s*\/?>/', $trimmedLine)) {
                $linesToRemove[$i] = true;
            }

            // Check for @endif closing the block
            if ($inCurrencyBlock && preg_match('/@endif/', $line)) {
                $linesToRemove[$i] = true;
                $inCurrencyBlock = false;
            }

            // Check for currency text
            if (preg_match('/\{\{\s*__\(["\']Currencies["\']\)\s*\}\}/', $trimmedLine)) {
                $linesToRemove[$i] = true;
            }
        }

        // Second pass: build new content without removed lines
        $newLines = [];
        $removedCount = 0;
        for ($i = 0; $i < count($lines); $i++) {
            if (!isset($linesToRemove[$i])) {
                $newLines[] = $lines[$i];
            } else {
                $removedCount++;
            }
        }

        // Clean up extra whitespace
        $content = implode("\n", $newLines);
        $content = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $content);

        File::put($sidebarPath, $content);
        
        if ($removedCount > 0) {
            $this->info("✅ Removed {$removedCount} sidebar menu item(s) successfully.");
        } else {
            $this->warn('⚠️  No matching sidebar menu items found to remove.');
        }
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
        $this->info('🎉 ATU Multi-Currency UI package uninstalled successfully!');
        $this->newLine();

        $this->comment('📋 What was removed:');
        $this->line('   ✅ All UI view files');
        $this->line('   ✅ Routes from routes/web.php');
        $this->line('   ✅ Sidebar menu code');
        $this->line('   ✅ Application caches cleared');
        $this->line('   ✅ Final backup created in storage/app/');
        $this->newLine();

        $this->comment('📖 Note:');
        $this->line('   The ATU Multi-Currency package itself is still installed.');
        $this->line('   To uninstall the full package, run: php artisan atumulticurrency:uninstall');
        $this->newLine();

        $this->info('✨ Thank you for using ATU Multi-Currency UI!');
    }
}
