<?php

namespace Vormia\ATUMultiCurrency\Support;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Vormia\ATUMultiCurrency\ATUMultiCurrency;

/**
 * Publishes Flux-admin UI snippets into the host app, mirroring {@see \Vormia\UILivewireFluxAdmin\UILivewireFlux}
 * (copy stubs into resources/views) rather than pasting large Blade blobs into the sidebar.
 */
final class FluxAdminUiInstaller
{
    public function __construct(
        private Filesystem $filesystem,
    ) {}

    public static function default(): self
    {
        return new self(new Filesystem);
    }

    public function sidebarMenuPartialSourcePath(): string
    {
        return ATUMultiCurrency::stubsPath('resources/views/components/atu-multicurrency/sidebar-menu.blade.php');
    }

    public function sidebarMenuPartialDestinationPath(): string
    {
        return resource_path('views/components/atu-multicurrency/sidebar-menu.blade.php');
    }

    /**
     * Copy the sidebar menu partial into the host app (same idea as UILivewireFlux::copyStubs for views).
     *
     * @return bool false when the source stub is missing or copy failed
     */
    public function publishSidebarMenuPartial(?Command $console): bool
    {
        $src = $this->sidebarMenuPartialSourcePath();
        $dest = $this->sidebarMenuPartialDestinationPath();

        if (! $this->filesystem->exists($src)) {
            return false;
        }

        $this->filesystem->ensureDirectoryExists(dirname($dest));

        if ($this->filesystem->exists($dest) && $console !== null) {
            if (! $console->confirm("File {$dest} already exists. Override?", false)) {
                $console->line("  Skipped overwriting: {$dest}");

                return $this->filesystem->exists($dest);
            }
        }

        return $this->filesystem->copy($src, $dest);
    }

    public function removePublishedSidebarMenuPartial(): void
    {
        $dest = $this->sidebarMenuPartialDestinationPath();
        if ($this->filesystem->exists($dest)) {
            $this->filesystem->delete($dest);
        }
        $dir = dirname($dest);
        if ($this->filesystem->isDirectory($dir) && $this->filesystem->isEmptyDirectory($dir)) {
            $this->filesystem->deleteDirectory($dir);
        }
    }
}
