<?php

// ATU Multi-Currency admin Livewire 4 full-page routes (optional manual merge).
// The package registers these routes by default (Livewire is a Composer dependency), and calls
// Livewire::addLocation(viewPath: <package>/src/stubs/resources/views/livewire/admin/atu)
// in ATUMultiCurrencyServiceProvider::boot().
//
// If you copy only these routes into your app, you must also register that view path so
// component names like currencies.index resolve — either duplicate the addLocation call
// in one of your service providers, or add the path under component_locations in
// config/livewire.php (see Livewire 4 "Programmatic registration" in the Components docs).
//
// use Illuminate\Support\Facades\Route;

// >>> ATU Multi-Currency Web Routes START
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('admin/atu')->name('admin.atu.')->group(function () {
        Route::livewire('currencies', 'currencies.index')->name('currencies.index');
        Route::livewire('currencies/create', 'currencies.create')->name('currencies.create');
        Route::livewire('currencies/edit/{id}', 'currencies.edit')->name('currencies.edit');
        Route::livewire('currencies/settings', 'currencies.settings')->name('currencies.settings');
        Route::livewire('currencies/logs', 'currencies.logs')->name('currencies.logs');
    });
});
// >>> ATU Multi-Currency Web Routes END
