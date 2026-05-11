<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Volt\Volt;
use Vormia\ATUMultiCurrency\ATUMultiCurrency;

class ATUMultiCurrencyUIInstallCommand extends Command
{
    protected $signature = 'atumulticurrency:ui-install {--inject-sidebar : Inject Flux sidebar snippet into the app layout (optional)}';

    protected $description = 'Verify ATU Multi-Currency UI dependencies; optionally inject Flux sidebar (views and routes load from the package when Volt is installed)';

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

        if (! class_exists(Volt::class)) {
            $this->warn('livewire/volt is not installed. Volt admin pages will not register.');
            $this->line('Install Volt, or merge routes/views manually from:');
            $this->line('  ' . ATUMultiCurrency::stubsPath('reference/routes-to-add.php'));
            $this->newLine();
        } else {
            $this->info('Volt is installed: admin UI routes are registered by the package at /admin/atu/currencies');
        }

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

    private function injectSidebarMenu(): void
    {
        $sidebarPath = resource_path('views/components/layouts/app/sidebar.blade.php');
        $sidebarToAdd = ATUMultiCurrency::stubsPath('reference/sidebar-menu-to-add.blade.php');

        if (! File::exists($sidebarPath)) {
            $this->warn('Sidebar not found: ' . $sidebarPath);
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

        $lines = explode("\n", $content);
        $insertionLine = -1;
        $inPlatformGroup = false;

        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/<flux:navlist\.group\s+.*?:heading=["\']__\(["\']Platform["\']\)["\'].*?class=["\']grid["\']>/i', $lines[$i])) {
                $inPlatformGroup = true;
                continue;
            }
            if ($inPlatformGroup && preg_match('/<\/flux:navlist\.group>/i', $lines[$i])) {
                $insertionLine = $i + 1;
                break;
            }
        }

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
            $sidebarLines = explode("\n", $sidebarContent);
            array_splice($lines, $insertionLine, 0, $sidebarLines);
            File::put($sidebarPath, implode("\n", $lines));
            $this->info('Sidebar snippet injected after the Platform nav group.');
        } else {
            $this->warn('Could not find Platform navlist.group. Merge manually from: ' . $sidebarToAdd);
        }
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
