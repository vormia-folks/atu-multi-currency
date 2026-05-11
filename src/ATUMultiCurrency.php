<?php

namespace Vormia\ATUMultiCurrency;

use Composer\InstalledVersions;

class ATUMultiCurrency
{
    public const VERSION = '0.1.0';

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
     * Absolute path to the package stubs (reference snippets and Volt views).
     */
    public static function stubsPath(string $suffix = ''): string
    {
        $base = __DIR__ . '/stubs';

        return $suffix ? $base . '/' . ltrim($suffix, '/') : $base;
    }
}
