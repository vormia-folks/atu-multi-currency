<?php

namespace Vormia\ATUMultiCurrency\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Vormia\ATUMultiCurrency\ATUMultiCurrency;

/**
 * Copies package UI stubs into the host app, mirroring vormiaphp/ui-livewireflux-admin UILivewireFlux::copyStubs().
 */
final class ATUMultiCurrencyUiKit
{
    public function __construct(
        private ?Filesystem $filesystem = null,
    ) {
        $this->filesystem ??= new Filesystem();
    }

    public static function default(): self
    {
        return new self();
    }

    public function viewsStubSource(): string
    {
        return ATUMultiCurrency::stubsPath('resources/views');
    }

    public function viewsStubDestination(): string
    {
        return resource_path('views');
    }

    /**
     * @throws RuntimeException when the stub views directory is missing
     */
    public function copyViewStubsToApp(): void
    {
        $source = $this->viewsStubSource();
        $destination = $this->viewsStubDestination();

        if (! $this->filesystem->isDirectory($source)) {
            throw new RuntimeException("Source directory does not exist: {$source}");
        }

        $this->filesystem->ensureDirectoryExists($destination);

        foreach ($this->filesystem->allFiles($source) as $file) {
            $relativePath = ltrim(str_replace($source, '', $file->getPathname()), '/\\');
            if (str_ends_with($file->getFilename(), '.backup')) {
                continue;
            }
            $destFile = rtrim($destination, '/\\') . '/' . $relativePath;

            if ($this->filesystem->exists($destFile)) {
                if (app()->runningInConsole() && app()->bound('command')) {
                    $command = app('command');
                    if (method_exists($command, 'confirm')) {
                        if (! $command->confirm("File {$destFile} already exists. Override?", false)) {
                            $command->line("  Skipped: {$destFile}");

                            continue;
                        }
                    }
                }
            }

            $this->filesystem->ensureDirectoryExists(dirname($destFile));
            $this->filesystem->copy($file->getPathname(), $destFile);
        }
    }
}
