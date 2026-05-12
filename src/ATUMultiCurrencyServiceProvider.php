<?php

namespace Vormia\ATUMultiCurrency;

use Vormia\ATUMultiCurrency\ATUMultiCurrency;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyHelpCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyInstallCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyRefreshCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyUIInstallCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyUIUninstallCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyUIUpdateCommand;
use Vormia\ATUMultiCurrency\Console\Commands\ATUMultiCurrencyUninstallCommand;
use Vormia\ATUMultiCurrency\Support\CurrencySyncService;
use Vormia\ATUMultiCurrency\Support\Installer;
use Vormia\ATUMultiCurrency\Support\SettingsManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ATUMultiCurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            ATUMultiCurrency::packageRoot() . '/config/atu-multi-currency.php',
            'atu-multi-currency'
        );

        $this->app->instance('atumulticurrency.version', ATUMultiCurrency::VERSION);

        $this->app->singleton(Installer::class, function (Application $app) {
            return new Installer(
                new Filesystem(),
                $app->basePath()
            );
        });

        $this->app->singleton(SettingsManager::class);
        $this->app->singleton(CurrencySyncService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(ATUMultiCurrency::migrationsPath());

        $this->loadRoutesFrom(ATUMultiCurrency::packageRoot() . '/routes/atu-multicurrency-api.php');

        Livewire::addLocation(
            viewPath: ATUMultiCurrency::stubsPath('resources/views/livewire/admin/atu')
        );

        if (! ATUMultiCurrency::hostWebPhpContainsAtuAdminRouteBlock($this->app->basePath())) {
            $this->loadRoutesFrom(ATUMultiCurrency::packageRoot() . '/routes/atumulticurrency-web.php');
        }

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

            $this->publishes([
                ATUMultiCurrency::packageRoot() . '/config/atu-multi-currency.php' => config_path('atu-multi-currency.php'),
            ], 'atumulticurrency-config');
        }
    }
}
