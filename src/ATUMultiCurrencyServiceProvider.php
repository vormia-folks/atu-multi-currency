<?php

namespace Vormia\ATUMultiCurrency;

use Vormia\ATUMultiCurrency\ATUMultiCurrency;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyHelpCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyInstallCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyUninstallCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyRefreshCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyUIInstallCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyUIUninstallCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyUIUpdateCommand;
use Vormia\ATUMultiCurrency\Support\Installer;
use Vormia\ATUMultiCurrency\Support\SettingsManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class ATUMultiCurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('atumulticurrency.version', ATUMultiCurrency::VERSION);

        $this->app->singleton(Installer::class, function (Application $app) {
            return new Installer(
                new Filesystem(),
                ATUMultiCurrency::stubsPath(),
                $app->basePath()
            );
        });

        $this->app->singleton(SettingsManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ATUMultiCurrencyInstallCommand::class,
                ATUMultiCurrencyRefreshCommand::class,
                ATUMultiCurrencyUninstallCommand::class,
                ATUMultiCurrencyHelpCommand::class,
                ATUMultiCurrencyUIInstallCommand::class,
                ATUMultiCurrencyUIUninstallCommand::class,
                ATUMultiCurrencyUIUpdateCommand::class,
            ]);
        }
    }
}
