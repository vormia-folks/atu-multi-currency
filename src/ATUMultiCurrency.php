<?php

namespace Vormia\ATUMultiCurrency;

use Composer\InstalledVersions;

class ATUMultiCurrency
{
    public const VERSION = '2.1.0';

    /** Marker inside routes/web.php after `atumulticurrency:ui-install` merges admin routes. */
    public const ATU_WEB_ROUTES_FILE_MARKER = '>>> ATU Multi-Currency Web Routes START';

    /**
     * Absolute path to the package root (directory containing composer.json).
     */
    public static function packageRoot(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Path to package database/migrations (absolute).
     */
    public static function migrationsPath(): string
    {
        return self::packageRoot() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
    }

    /**
     * Path relative to the host application base_path(), for Artisan --path.
     */
    public static function migrationsPathRelativeToBase(): string
    {
        $base = base_path();

        if (class_exists(InstalledVersions::class)) {
            $root = InstalledVersions::getInstallPath('vormia-folks/atu-multi-currency');
            if (is_string($root) && $root !== '') {
                $full = $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
                if (str_starts_with($full, $base)) {
                    return ltrim(str_replace('\\', '/', substr($full, strlen($base))), '/');
                }
            }
        }

        $absolute = self::migrationsPath();
        if (str_starts_with($absolute, $base)) {
            return ltrim(str_replace('\\', '/', substr($absolute, strlen($base))), '/');
        }

        return 'vendor/vormia-folks/atu-multi-currency/database/migrations';
    }

    /**
     * Absolute path to the package stubs (reference snippets and Livewire single-file views).
     */
    public static function stubsPath(string $suffix = ''): string
    {
        $base = __DIR__ . '/stubs';

        return $suffix ? $base . '/' . ltrim($suffix, '/') : $base;
    }

    /**
     * True when the host app's routes/web.php already contains the marked ATU admin block
     * merged by `atumulticurrency:ui-install`. The service provider skips loading the same
     * routes from the package file to avoid duplicate route names.
     */
    public static function hostWebPhpContainsAtuAdminRouteBlock(?string $appBasePath = null): bool
    {
        $base = $appBasePath ?? (function_exists('base_path') ? base_path() : '');
        if ($base === '') {
            return false;
        }

        $path = $base . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php';
        if (! is_file($path)) {
            return false;
        }

        $contents = @file_get_contents($path);

        return is_string($contents) && str_contains($contents, self::ATU_WEB_ROUTES_FILE_MARKER);
    }

    /**
     * True when the host app has ATU admin Livewire single-file views under resources/views
     * (published by ui-install). The service provider passes this path to Livewire::addLocation
     * instead of the package vendor path so routes resolve to the copied blades.
     */
    public static function appHasCopiedAtuAdminLivewireViews(?string $appBasePath = null): bool
    {
        $base = $appBasePath ?? (function_exists('base_path') ? base_path() : '');
        if ($base === '') {
            return false;
        }

        $dir = $base . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR
            . 'livewire' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'atu';
        if (! is_dir($dir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                return true;
            }
        }

        return false;
    }
}
