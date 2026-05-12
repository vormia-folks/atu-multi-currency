<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Vormia\ATUMultiCurrency\Support\FluxAdminUiInstaller;

class ATUMultiCurrencyUIUninstallCommand extends Command
{
    protected $signature = 'atumulticurrency:ui-uninstall {--force : Skip confirmation prompts}';

    protected $description = 'Remove legacy copied ATU UI views and optional pasted routes/sidebar snippets (package routes stay until composer remove)';

    public function handle(): int
    {
        $this->info('ATU Multi-Currency UI cleanup (host files only)...');
        $this->newLine();

        $force = $this->option('force');

        if (! $force && ! $this->confirm('Remove legacy copied views and pasted route/sidebar snippets?', false)) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $this->createFinalBackup();

        $this->removeLegacyCopiedViews();
        $this->removeMarkedRoutes();
        $this->removeMarkedSidebar();
        FluxAdminUiInstaller::default()->removePublishedSidebarMenuPartial();

        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (\Exception $e) {
                // ignore
            }
        }

        $this->newLine();
        $this->info('Done. Package UI loads from vendor (Livewire is required by this package).');
        $this->line('Remove the package with: composer remove vormia-folks/atu-multi-currency');

        return self::SUCCESS;
    }

    private function createFinalBackup(): void
    {
        $backupDir = storage_path('app/atu-multicurrency-ui-final-backup-' . date('Y-m-d-H-i-s'));
        File::ensureDirectoryExists($backupDir, 0755, true);

        $sidebarPaths = array_filter([
            resource_path('views/layouts/app/sidebar.blade.php'),
            resource_path('views/components/layouts/app/sidebar.blade.php'),
        ], fn (string $p): bool => File::exists($p));

        $map = [
            resource_path('views/livewire/admin/atu') => $backupDir . '/views/livewire/admin/atu',
            base_path('routes/web.php') => $backupDir . '/routes/web.php',
        ];

        $partial = resource_path('views/components/atu-multicurrency/sidebar-menu.blade.php');
        if (File::exists($partial)) {
            $rel = ltrim(str_replace(base_path(), '', $partial), DIRECTORY_SEPARATOR);
            $map[$partial] = $backupDir . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
        }

        foreach ($sidebarPaths as $sidebarPath) {
            $rel = ltrim(str_replace(base_path(), '', $sidebarPath), DIRECTORY_SEPARATOR);
            $map[$sidebarPath] = $backupDir . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
        }

        foreach ($map as $source => $destination) {
            if (! File::exists($source)) {
                continue;
            }
            if (File::isDirectory($source)) {
                File::copyDirectory($source, $destination);
            } else {
                File::ensureDirectoryExists(dirname($destination));
                File::copy($source, $destination);
            }
        }

        $this->line('Backup: ' . $backupDir);
    }

    private function removeLegacyCopiedViews(): void
    {
        $path = resource_path('views/livewire/admin/atu');
        if (File::exists($path)) {
            File::deleteDirectory($path);
            $this->line('Removed legacy copied views: resources/views/livewire/admin/atu');
        }
    }

    private function removeMarkedRoutes(): void
    {
        $routesPath = base_path('routes/web.php');
        if (! File::exists($routesPath)) {
            return;
        }

        $content = File::get($routesPath);
        $pattern = '/\n?\/\/ >>> ATU Multi-Currency Web Routes START.*?\/\/ >>> ATU Multi-Currency Web Routes END\s*\n?/s';
        $updated = preg_replace($pattern, "\n", $content, -1, $count);

        if ($count > 0) {
            File::put($routesPath, preg_replace("/\n{3,}/", "\n\n", $updated));
            $this->line('Removed marked ATU route block from routes/web.php');

            return;
        }

        $this->line('No marked ATU route block in routes/web.php (nothing to remove).');
    }

    private function removeMarkedSidebar(): void
    {
        $paths = [
            resource_path('views/layouts/app/sidebar.blade.php'),
            resource_path('views/components/layouts/app/sidebar.blade.php'),
        ];

        $pattern = '/\n?\{\{-- >>> ATU Multi-Currency Sidebar START --\}\}.*?\{\{-- >>> ATU Multi-Currency Sidebar END --\}\}\s*\n?/s';
        $removedAny = false;

        foreach ($paths as $sidebarPath) {
            if (! File::exists($sidebarPath)) {
                continue;
            }

            $content = File::get($sidebarPath);
            $updated = preg_replace($pattern, "\n", $content, -1, $count);

            if ($count > 0) {
                File::put($sidebarPath, preg_replace("/\n{3,}/", "\n\n", $updated));
                $this->line('Removed marked ATU sidebar block from ' . $sidebarPath . '.');
                $removedAny = true;
            }
        }

        if (! $removedAny) {
            $this->line('No marked ATU sidebar block found.');
        }
    }
}
