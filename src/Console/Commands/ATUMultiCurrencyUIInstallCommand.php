<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ATUMultiCurrencyUIInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'atumulticurrency:ui-install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install ATU Multi-Currency UI package with all necessary views, routes and configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Installing ATU Multi-Currency UI Package...');

        // Check for required dependencies
        $this->checkRequiredDependencies();

        // Check if ATU Multi-Currency package is installed
        $this->checkATUPackageInstalled();

        // Step 1: Copy UI stubs
        $this->step('Copying UI files from stubs...');
        $this->copyUIFiles();

        // Step 2: Inject routes
        $this->step('Injecting routes into routes/web.php...');
        $this->injectRoutes();

        // Step 3: Inject sidebar menu (if livewire/flux exists)
        if (InstalledVersions::isInstalled('livewire/flux')) {
            $this->step('Injecting sidebar menu...');
            $this->injectSidebarMenu();
        } else {
            $this->warn('⚠️  livewire/flux is not installed. Sidebar menu will not be automatically injected.');
            $this->line('   You will need to manually add the navigation links to resources/views/components/layouts/app/sidebar.blade.php');
        }

        // Step 4: Clear caches
        $this->step('Clearing application caches...');
        $this->clearCaches();

        $this->displayCompletionMessage();

        return 0;
    }

    /**
     * Check for required dependencies
     */
    private function checkRequiredDependencies(): void
    {
        $this->step('Checking required dependencies...');

        $required = [
            'vormiaphp/ui-livewireflux-admin' => '^2.0',
        ];

        $allGood = true;
        foreach ($required as $package => $version) {
            if (InstalledVersions::isInstalled($package)) {
                $installedVersion = InstalledVersions::getVersion($package);
                $this->info("  ✅ {$package} ({$installedVersion})");
            } else {
                $this->error("  ❌ {$package} - MISSING");
                $this->line("     Please install it first: composer require {$package}:{$version}");
                $allGood = false;
            }
        }

        if (!$allGood) {
            $this->error('❌ Required dependencies are missing. Please install them before continuing.');
            exit(1);
        }
    }

    /**
     * Check if ATU Multi-Currency package is installed and migrations run
     */
    private function checkATUPackageInstalled(): void
    {
        $this->step('Checking ATU Multi-Currency package installation...');

        // Check if migrations table exists
        if (!Schema::hasTable('atu_multicurrency_currencies')) {
            $this->error('❌ ATU Multi-Currency package migrations have not been run.');
            $this->line('   Please run: php artisan atumulticurrency:install');
            $this->line('   Then run migrations: php artisan migrate');
            exit(1);
        }

        $this->info('  ✅ ATU Multi-Currency package is installed and migrations are run.');
    }

    /**
     * Display a step message
     */
    private function step($message)
    {
        $this->info("📦 {$message}");
    }

    /**
     * Copy UI files from stubs
     */
    private function copyUIFiles(): void
    {
        $stubsPath = __DIR__ . '/../../stubs/resources/views/livewire/admin/atu';
        $targetPath = resource_path('views/livewire/admin/atu');

        if (!File::exists($stubsPath)) {
            $this->error('❌ UI stubs not found at: ' . $stubsPath);
            return;
        }

        if (File::exists($targetPath)) {
            if (!$this->confirm('UI files already exist. Overwrite?', false)) {
                $this->warn('⚠️  Skipping file copy.');
                return;
            }
        }

        File::ensureDirectoryExists($targetPath);
        File::copyDirectory($stubsPath, $targetPath);

        $this->info('✅ UI files copied successfully.');
    }

    /**
     * Inject routes into routes/web.php
     */
    private function injectRoutes(): void
    {
        $routesPath = base_path('routes/web.php');
        $routesToAdd = __DIR__ . '/../../stubs/reference/routes-to-add.php';

        // If developing locally, use local path
        if (!File::exists($routesToAdd)) {
            $routesToAdd = base_path('vendor/vormiaphp/atu-multicurrency/src/stubs/reference/routes-to-add.php');
        }

        if (!File::exists($routesPath)) {
            $this->error('❌ routes/web.php not found.');
            return;
        }

        if (!File::exists($routesToAdd)) {
            $this->error('❌ routes-to-add.php not found.');
            return;
        }

        $content = File::get($routesPath);
        $routesContent = File::get($routesToAdd);

        // Extract just the Route::group part (remove PHP tags and comments)
        $routesContent = preg_replace('/^<\?php\s*/', '', $routesContent);
        $routesContent = preg_replace('/\/\/.*$/m', '', $routesContent);
        $routesContent = trim($routesContent);

        // Check if routes already exist
        $routeMarkers = [
            'admin.atu.currencies.index',
            'admin.atu.currencies.create',
            'admin.atu.currencies.edit',
            'admin.atu.currencies.settings',
            "Route::group(['prefix' => 'admin/atu']",
        ];

        $routesExist = false;
        foreach ($routeMarkers as $marker) {
            if (strpos($content, $marker) !== false) {
                $routesExist = true;
                break;
            }
        }

        if ($routesExist) {
            $this->warn('⚠️  Routes already exist in routes/web.php. Skipping route injection.');
            return;
        }

        // Find the middleware group - try multiple patterns
        $middlewarePatterns = [
            // Standard pattern
            '/(Route::middleware\(\[[\'"]auth[\'"]\]\)->group\(function\s*\(\)\s*\{)/s',
            // With spaces variations
            '/(Route::middleware\s*\(\s*\[[\'"]auth[\'"]\s*\]\s*\)\s*->\s*group\s*\(\s*function\s*\(\)\s*\{)/s',
            // Single quotes
            '/(Route::middleware\(\[\'auth\'\]\)->group\(function\s*\(\)\s*\{)/s',
        ];

        $found = false;
        foreach ($middlewarePatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $insertionPoint = strpos($content, $matches[1]) + strlen($matches[1]);
                $content = substr_replace($content, "\n    " . $routesContent . "\n", $insertionPoint, 0);
                File::put($routesPath, $content);
                $this->info('✅ Routes injected successfully.');
                $this->comment('   Note: If you have configured your own starterkit, you may need to add:');
                $this->line('   use Livewire\Volt\Volt;');
                $this->line('   at the top of your routes/web.php file.');
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->warn('⚠️  Could not find Route::middleware([\'auth\'])->group in routes/web.php');
            $this->line('   Please manually add the routes from vendor/vormiaphp/atu-multicurrency/src/stubs/reference/routes-to-add.php');
            $this->line('   The routes should be placed inside the middleware group.');
            $this->newLine();
            $this->comment('   Note: If you have configured your own starterkit, you may need to add:');
            $this->line('   use Livewire\Volt\Volt;');
            $this->line('   at the top of your routes/web.php file.');
        }
    }

    /**
     * Inject sidebar menu into sidebar.blade.php
     */
    private function injectSidebarMenu(): void
    {
        $sidebarPath = resource_path('views/components/layouts/app/sidebar.blade.php');
        $sidebarToAdd = __DIR__ . '/../../stubs/reference/sidebar-menu-to-add.blade.php';

        // If developing locally, use local path
        if (!File::exists($sidebarToAdd)) {
            $sidebarToAdd = base_path('vendor/vormiaphp/atu-multicurrency/src/stubs/reference/sidebar-menu-to-add.blade.php');
        }

        if (!File::exists($sidebarPath)) {
            $this->warn('⚠️  Sidebar file not found at: ' . $sidebarPath);
            $this->line('   Please manually add the sidebar menu code.');
            return;
        }

        if (!File::exists($sidebarToAdd)) {
            $this->error('❌ sidebar-menu-to-add.blade.php not found.');
            return;
        }

        $content = File::get($sidebarPath);
        $sidebarContent = File::get($sidebarToAdd);

        // Extract just the menu code (remove PHP tags if present)
        $sidebarContent = preg_replace('/^<\?php\s*/', '', $sidebarContent);
        $sidebarContent = preg_replace('/\?>\s*/', '', $sidebarContent);
        $sidebarContent = preg_replace('/\/\/.*$/m', '', $sidebarContent);
        $sidebarContent = trim($sidebarContent);

        // Check if menu already exists
        $menuMarkers = [
            'admin.atu.currencies.index',
            "route('admin.atu.currencies.index')",
            "{{ __('Currencies') }}",
        ];

        $menuExists = false;
        foreach ($menuMarkers as $marker) {
            if (strpos($content, $marker) !== false) {
                $menuExists = true;
                break;
            }
        }

        if ($menuExists) {
            $this->warn('⚠️  Sidebar menu already exists. Skipping sidebar injection.');
            return;
        }

        // Find Platform navlist.group - look for the closing tag
        $lines = explode("\n", $content);
        $insertionLine = -1;

        // Pattern to find Platform group closing tag
        $inPlatformGroup = false;
        for ($i = 0; $i < count($lines); $i++) {
            // Check if this line contains the Platform group opening tag
            if (preg_match('/<flux:navlist\.group\s+.*?:heading=["\']__\(["\']Platform["\']\)["\'].*?class=["\']grid["\']>/i', $lines[$i])) {
                $inPlatformGroup = true;
                continue;
            }

            // If we're in the Platform group, look for the closing tag
            if ($inPlatformGroup && preg_match('/<\/flux:navlist\.group>/i', $lines[$i])) {
                $insertionLine = $i + 1;
                break;
            }
        }

        // Fallback: if Platform group not found, try to find it with more flexible patterns
        if ($insertionLine === -1) {
            for ($i = 0; $i < count($lines); $i++) {
                if (preg_match('/<flux:navlist\.group.*?heading.*?Platform.*?>/i', $lines[$i])) {
                    for ($j = $i + 1; $j < min($i + 20, count($lines)); $j++) {
                        if (preg_match('/<\/flux:navlist\.group>/i', $lines[$j])) {
                            $insertionLine = $j + 1;
                            break 2;
                        }
                    }
                }
            }
        }

        if ($insertionLine !== -1 && $insertionLine <= count($lines)) {
            // Insert the sidebar content
            $sidebarLines = explode("\n", $sidebarContent);
            array_splice($lines, $insertionLine, 0, $sidebarLines);
            $content = implode("\n", $lines);
            File::put($sidebarPath, $content);
            $this->info('✅ Sidebar menu injected successfully.');
        } else {
            $this->warn('⚠️  Could not find Platform navlist.group in sidebar file.');
            $this->line('   Please manually add the sidebar menu code after the Platform group closing tag.');
            $this->line('   The menu code should be placed in: ' . $sidebarPath);
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
        $this->info('🎉 ATU Multi-Currency UI package installed successfully!');
        $this->newLine();

        $this->comment('📋 Next steps:');
        $this->line('   1. Review your routes/web.php to ensure routes were added correctly');
        $this->line('   2. If you have configured your own starterkit, add: use Livewire\Volt\Volt; at the top of routes/web.php');
        $this->line('   3. Review your sidebar.blade.php to ensure menu items were added');
        $this->line('   4. Test your currency management routes');
        $this->newLine();

        $this->warn('⚠️  Manual Setup Required (if automatic injection failed):');
        $this->newLine();
        
        $this->comment('📝 To manually add routes:');
        $this->line('   Open routes/web.php and add the following inside Route::middleware([\'auth\'])->group(function () { ... }):');
        $this->newLine();
        $this->line('   Route::group([\'prefix\' => \'admin/atu\'], function () {');
        $this->line('       Volt::route(\'currencies\', \'admin.atu.currencies.index\')->name(\'admin.atu.currencies.index\');');
        $this->line('       Volt::route(\'currencies/create\', \'admin.atu.currencies.create\')->name(\'admin.atu.currencies.create\');');
        $this->line('       Volt::route(\'currencies/edit/{id}\', \'admin.atu.currencies.edit\')->name(\'admin.atu.currencies.edit\');');
        $this->line('       Volt::route(\'currencies/settings\', \'admin.atu.currencies.settings\')->name(\'admin.atu.currencies.settings\');');
        $this->line('       Volt::route(\'currencies/logs\', \'admin.atu.currencies.logs\')->name(\'admin.atu.currencies.logs\');');
        $this->line('   });');
        $this->newLine();
        $this->line('   Reference file: vendor/vormiaphp/atu-multicurrency/src/stubs/reference/routes-to-add.php');
        $this->newLine();

        $this->comment('📝 To manually add sidebar menu:');
        $this->line('   Open resources/views/components/layouts/app/sidebar.blade.php');
        $this->line('   Add the following menu items after the Platform group closing tag (</flux:navlist.group>):');
        $this->newLine();
        $this->line('   <hr />');
        $this->line('   <flux:navlist.item icon="currency-dollar" :href="route(\'admin.atu.currencies.index\')"');
        $this->line('       :current="request()->routeIs(\'admin.atu.currencies.index\') || request()->routeIs(\'admin.atu.currencies.create\') || request()->routeIs(\'admin.atu.currencies.edit\')" wire:navigate>');
        $this->line('       {{ __(\'Currencies\') }}');
        $this->line('   </flux:navlist.item>');
        $this->line('   <flux:navlist.item icon="document-text" :href="route(\'admin.atu.currencies.logs\')"');
        $this->line('       :current="request()->routeIs(\'admin.atu.currencies.logs\')" wire:navigate>');
        $this->line('       {{ __(\'Currency Logs\') }}');
        $this->line('   </flux:navlist.item>');
        $this->line('   <flux:navlist.item icon="cog-6-tooth" :href="route(\'admin.atu.currencies.settings\')"');
        $this->line('       :current="request()->routeIs(\'admin.atu.currencies.settings\')" wire:navigate>');
        $this->line('       {{ __(\'Currency Settings\') }}');
        $this->line('   </flux:navlist.item>');
        $this->newLine();
        $this->line('   Reference file: vendor/vormiaphp/atu-multicurrency/src/stubs/reference/sidebar-menu-to-add.blade.php');
        $this->newLine();

        $this->comment('📖 For help and available commands, run: php artisan atumulticurrency:help');
        $this->newLine();

        $this->info('✨ Happy coding with ATU Multi-Currency UI!');
    }
}
