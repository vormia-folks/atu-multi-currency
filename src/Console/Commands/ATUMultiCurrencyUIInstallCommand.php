<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Vormia\ATUMultiCurrency\ATUMultiCurrency;

class ATUMultiCurrencyUIInstallCommand extends Command
{
    protected $signature = 'atumulticurrency:ui-install {--inject-sidebar : Inject Flux sidebar snippet into the app layout (optional)}';

    protected $description = 'Verify ATU Multi-Currency UI dependencies; optionally inject Flux sidebar (admin views and routes load from the package; Livewire is a Composer dependency of this package)';

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
            $this->info('Livewire is installed: admin UI routes are registered by the package at /admin/atu/currencies');
        }

        $this->mergeWebRoutesIfNeeded();

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
     * Match vormiaphp/ui-livewireflux-admin: primary layouts path, then components path.
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
     * Append the marked ATU route block to routes/web.php when it is not already present
     * and the named routes are not registered (avoids duplicate definitions when the package
     * service provider already loaded routes).
     */
    private function mergeWebRoutesIfNeeded(): void
    {
        $routesPath = base_path('routes/web.php');
        $stubPath = ATUMultiCurrency::stubsPath('reference/routes-to-add.php');

        if (! File::exists($routesPath)) {
            $this->warn('routes/web.php not found; skipping route merge.');

            return;
        }

        if (! File::exists($stubPath)) {
            $this->error('Route reference stub missing: ' . $stubPath);

            return;
        }

        $webContent = File::get($routesPath);

        if (str_contains($webContent, ATUMultiCurrency::ATU_WEB_ROUTES_FILE_MARKER)) {
            $this->line('routes/web.php already contains the marked ATU route block. Skipping route merge.');

            return;
        }

        $stubRaw = File::get($stubPath);
        if (! preg_match(
            '/\/\/\s*>>>\s*ATU Multi-Currency Web Routes START.*?\/\/\s*>>>\s*ATU Multi-Currency Web Routes END/s',
            $stubRaw,
            $m
        )) {
            $this->error('Could not extract marked route block from: ' . $stubPath);

            return;
        }

        $block = trim($m[0]);
        $append = "\n\n" . $block . "\n";
        File::put($routesPath, rtrim($webContent) . $append);
        $this->info('Merged ATU admin route block into routes/web.php (marked for ui-uninstall). On the next app bootstrap the package skips loading the duplicate route file.');
    }

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

        $markers = [
            'admin.atu.currencies.index',
            "route('admin.atu.currencies.index')",
        ];

        foreach ($markers as $marker) {
            if (str_contains($content, $marker)) {
                $this->warn('Sidebar already contains ATU currency links. Skipping.');

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
            $this->info('Sidebar snippet injected into the Platform group (' . basename(dirname($sidebarPath)) . '/' . basename($sidebarPath) . ').');
        } else {
            $this->warn('Could not find a Platform flux:sidebar.group or flux:navlist.group. Merge manually from: ' . $sidebarToAdd);
        }
    }

    /**
     * Return the 0-based line index to splice new lines before (inside the Platform group,
     * immediately before its closing tag). Matches both Flux admin shells.
     *
     * @param  array<int, string>  $lines
     */
    private function findSidebarSnippetInsertionLine(array $lines): int
    {
        // ui-livewireflux-admin: <flux:sidebar.group ... heading="__('Platform')" ...>
        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/<flux:sidebar\.group\b[^>]*\bPlatform\b/i', $lines[$i])
                || preg_match('/<flux:sidebar\.group\b[^>]*heading\s*=\s*["\']__\(\s*[\'"]Platform[\'"]\s*\)["\']/i', $lines[$i])) {
                for ($j = $i + 1; $j < min($i + 80, count($lines)); $j++) {
                    if (preg_match('/<\/flux:sidebar\.group>/i', $lines[$j])) {
                        return $j;
                    }
                }
            }
        }

        // Newer layouts: <flux:navlist.group ... Platform ...> (class="grid" optional)
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
        $this->info('UI setup check finished.');
        $this->line('Reference stubs (optional manual merge):');
        $this->line('  ' . ATUMultiCurrency::stubsPath('reference/routes-to-add.php'));
        $this->line('  ' . ATUMultiCurrency::stubsPath('reference/sidebar-menu-to-add.blade.php'));
        $this->newLine();
    }
}
