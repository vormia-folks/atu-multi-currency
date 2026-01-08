<?php

namespace Vormia\ATUMultiCurrency;

class ATUMultiCurrency
{
    public const VERSION = '0.1.0';

    /**
     * Absolute path to the package stubs.
     */
    public static function stubsPath(string $suffix = ''): string
    {
        $base = __DIR__ . '/stubs';

        return $suffix ? $base . '/' . ltrim($suffix, '/') : $base;
    }
}
