<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use RuntimeException;
use Vormia\ATUMultiCurrency\ATUMultiCurrency;
use Vormia\ATUMultiCurrency\Support\ATUMultiCurrencyUiKit;

class ATUMultiCurrencyUIInstallCommand extends Command
{
    protected $signature = 'atumulticurrency:ui-install {--inject-sidebar : Inject Flux sidebar snippet into the app layout (optional)}';

    protected $description = 'Verify ATU Multi-Currency UI dependencies; copy view stubs; inject routes and optional Flux sidebar like ui-livewireflux-admin';

    public function handle(): int
    {
        $this->info('ATU Multi-Currency UI setup...');
        $this->newLine();

        if (! $this->checkRequiredDependencies()) {
            return self::FAILURE;
        }
        if (! $this->checkATUPackageInstalled()) {
            return self::FAILURE;
        }

        if (! class_exists(Livewire::class)) {
            $this->warn('livewire/livewire is not installed (unexpected: this package requires it). Admin Livewire pages will not register.');
            $this->line('Run composer install, or merge routes/views manually from:');
            $this->line('  ' . ATUMultiCurrency::stubsPath('reference/routes-to-add.php'));
            $this->newLine();
        } else {
            $this->info('Livewire is installed: admin UI routes resolve under /admin/atu/currencies');
        }

        $this->info('Copying view stubs into resources/views (same pattern as ui-livewireflux-admin)...');
        $kit = ATUMultiCurrencyUiKit::default();
        try {
            $kit->copyViewStubsToApp();
            $this->line('  Done: ' . $kit->viewsStubDestination());
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->injectRoutes();

        if ($this->option('inject-sidebar') && InstalledVersions::isInstalled('livewire/flux')) {
            $this->injectSidebarMenu();
        } elseif (InstalledVersions::isInstalled('livewire/flux')) {
            $this->line('To inject the Flux sidebar snippet, run: php artisan atumulticurrency:ui-install --inject-sidebar');
            $this->line('Reference: ' . ATUMultiCurrency::stubsPath('reference/sidebar-menu-to-add.blade.php'));
        } else {
            $this->warn('livewire/flux is not installed. Add navigation manually using the reference stub.');
        }

        $this->clearCaches();
        $this->displayCompletionMessage();

        return self::SUCCESS;
    }

    private function checkRequiredDependencies(): bool
    {
        $this->info('Checking required dependencies...');

        $required = [
            'vormiaphp/ui-livewireflux-admin' => '^2.0',
        ];

        foreach ($required as $package => $version) {
            if (InstalledVersions::isInstalled($package)) {
                $this->line('  OK ' . $package . ' (' . InstalledVersions::getPrettyVersion($package) . ')');
            } else {
                $this->error('  Missing ' . $package . ' — install with: composer require ' . $package . ':' . $version);

                return false;
            }
        }

        return true;
    }

    private function checkATUPackageInstalled(): bool
    {
        $this->info('Checking database...');

        if (! Schema::hasTable('atu_multicurrency_currencies')) {
            $this->error('ATU Multi-Currency migrations have not been run.');
            $this->line('Run: php artisan atumulticurrency:install');

            return false;
        }

        $this->line('  OK migrations present.');

        return true;
    }

    /**
     * Inject ATU admin routes into routes/web.php inside the auth middleware group (vormiaphp/ui-livewireflux-admin style).
     */
    private function injectRoutes(): void
    {
        $routesPath = base_path('routes/web.php');
        $routesToAdd = ATUMultiCurrency::stubsPath('reference/routes-to-add.php');

        if (! File::exists($routesPath)) {
            $this->error('routes/web.php not found.');

            return;
        }

        if (! File::exists($routesToAdd)) {
            $this->error('routes-to-add stub not found: ' . $routesToAdd);

            return;
        }

        $content = File::get($routesPath);
        $stubRaw = File::get($routesToAdd);

        if (! preg_match(
            '/\/\/\s*>>>\s*ATU Multi-Currency Web Routes START.*?\/\/\s*>>>\s*ATU Multi-Currency Web Routes END/s',
            $stubRaw,
            $m
        )) {
            $this->error('Could not extract marked route block from: ' . $routesToAdd);

            return;
        }

        $routesContent = trim($m[0]);

        $routeMarkers = [
            'admin.atu.currencies.index',
            "Route::prefix('admin/atu')",
            ATUMultiCurrency::ATU_WEB_ROUTES_FILE_MARKER,
        ];

        foreach ($routeMarkers as $marker) {
            if (str_contains($content, $marker)) {
                $this->line('ATU admin routes already appear in routes/web.php. Skipping route injection.');

                return;
            }
        }

        $middlewarePatterns = [
            '/(Route::middleware\(\[\s*[\'"]auth[\'"]\s*,\s*[\'"]verified[\'"]\s*\]\)->group\(function\s*\(\)\s*\{)/s',
            '/(Route::middleware\(\[[\'"]auth[\'"]\]\)->group\(function\s*\(\)\s*\{)/s',
            '/(Route::middleware\s*\(\s*\[[\'"]auth[\'"]\s*\]\s*\)\s*->\s*group\s*\(\s*function\s*\(\)\s*\{)/s',
        ];

        foreach ($middlewarePatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $insertionPoint = strpos($content, $matches[1]) + strlen($matches[1]);
                $content = substr_replace($content, "\n    " . $routesContent . "\n", $insertionPoint, 0);
                File::put($routesPath, $content);
                $this->info('Injected ATU admin routes into routes/web.php (inside auth middleware group).');

                return;
            }
        }

        $this->warn('Could not find auth middleware group in routes/web.php.');
        $this->line('Add the routes from: ' . $routesToAdd);
        $this->line('Place them inside Route::middleware([\'auth\'])->group(...) or with verified, as in ui-livewireflux-admin.');
    }

    /**
     * Primary layouts path, then components path (same as ui-livewireflux-admin getSidebarPath).
     */
    private function resolveSidebarPath(): ?string
    {
        $primary = resource_path('views/layouts/app/sidebar.blade.php');
        $fallback = resource_path('views/components/layouts/app/sidebar.blade.php');

        if (File::exists($primary)) {
            return $primary;
        }

        return File::exists($fallback) ? $fallback : null;
    }

    /**
     * Paste sidebar markup from the reference stub (same approach as ui-livewireflux-admin injectSidebarMenu).
     */
    private function injectSidebarMenu(): void
    {
        $sidebarPath = $this->resolveSidebarPath();
        $sidebarToAdd = ATUMultiCurrency::stubsPath('reference/sidebar-menu-to-add.blade.php');

        if ($sidebarPath === null) {
            $this->warn('Sidebar not found (checked resources/views/layouts/app/sidebar.blade.php and resources/views/components/layouts/app/sidebar.blade.php).');
            $this->line('Merge manually from: ' . $sidebarToAdd);

            return;
        }

        if (! File::exists($sidebarToAdd)) {
            $this->error('Reference stub missing: ' . $sidebarToAdd);

            return;
        }

        $content = File::get($sidebarPath);
        $sidebarContent = File::get($sidebarToAdd);
        $sidebarContent = preg_replace('/^<\?php\s*/', '', $sidebarContent);
        $sidebarContent = preg_replace('/\?>\s*/', '', $sidebarContent);
        $sidebarContent = preg_replace('/\/\/.*$/m', '', $sidebarContent);
        $sidebarContent = trim($sidebarContent);

        $menuMarkers = [
            'admin.atu.currencies.index',
            "route('admin.atu.currencies.index')",
            "{{ __('Currencies') }}",
        ];

        foreach ($menuMarkers as $marker) {
            if (str_contains($content, $marker)) {
                $this->warn('Sidebar already contains ATU currency links. Skipping sidebar injection.');

                return;
            }
        }

        if (str_contains($content, '>>> ATU Multi-Currency Sidebar START')) {
            $this->warn('Sidebar already contains the marked ATU block. Skipping.');

            return;
        }

        $lines = explode("\n", $content);
        $insertionLine = $this->findSidebarSnippetInsertionLine($lines);

        if ($insertionLine !== -1 && $insertionLine <= count($lines)) {
            $sidebarLines = explode("\n", $sidebarContent);
            array_splice($lines, $insertionLine, 0, $sidebarLines);
            File::put($sidebarPath, implode("\n", $lines));
            $this->info('Sidebar menu injected into the Platform group (' . basename(dirname($sidebarPath)) . '/' . basename($sidebarPath) . ').');
        } else {
            $this->warn('Could not find Platform flux:sidebar.group or flux:navlist.group. Merge manually from: ' . $sidebarToAdd);
        }
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function findSidebarSnippetInsertionLine(array $lines): int
    {
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/<flux:sidebar\.group\s+.*?:heading=["\']__\(["\']Platform["\']\)["\'].*?>/i', $lines[$i])) {
                for ($j = $i + 1; $j < min($i + 50, count($lines)); $j++) {
                    if (preg_match('/<\/flux:sidebar\.group>/i', $lines[$j])) {
                        return $j;
                    }
                }
            }
        }

        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/<flux:sidebar\.group.*?heading.*?Platform.*?>/i', $lines[$i])) {
                for ($j = $i + 1; $j < min($i + 50, count($lines)); $j++) {
                    if (preg_match('/<\/flux:sidebar\.group>/i', $lines[$j])) {
                        return $j;
                    }
                }
            }
        }

        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/<flux:navlist\.group\b[^>]*\bPlatform\b/i', $lines[$i])) {
                for ($j = $i + 1; $j < min($i + 80, count($lines)); $j++) {
                    if (preg_match('/<\/flux:navlist\.group>/i', $lines[$j])) {
                        return $j;
                    }
                }
            }
        }

        return -1;
    }

    private function clearCaches(): void
    {
        $this->info('Clearing caches...');
        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
                $this->line('  ' . $command);
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    private function displayCompletionMessage(): void
    {
        $this->newLine();
        $this->info('UI setup finished.');
        $this->line('Admin Livewire views were copied into your app; re-run this command after package updates to refresh files (you may be prompted to overwrite).');
        $this->line('Reference stubs (optional manual merge):');
        $this->line('  ' . ATUMultiCurrency::stubsPath('reference/routes-to-add.php'));
        $this->line('  ' . ATUMultiCurrency::stubsPath('reference/sidebar-menu-to-add.blade.php'));
        $this->newLine();
    }
}
