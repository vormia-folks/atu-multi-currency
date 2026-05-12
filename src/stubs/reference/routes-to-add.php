<?php

// ATU Multi-Currency admin Livewire routes (optional manual merge).
// `atumulticurrency:ui-install` injects the marked block inside Route::middleware(['auth'])->group(...)
// in routes/web.php (same pattern as vormiaphp/ui-livewireflux-admin). The package skips loading
// routes/atumulticurrency-web.php when this marker is present to avoid duplicate route names.
//
// After ui-install copies views to resources/views/livewire/admin/atu, the package skips
// Livewire::addLocation for the vendor view path while those files exist.
//
// use Illuminate\Support\Facades\Route;

// >>> ATU Multi-Currency Web Routes START
Route::prefix('admin/atu')->name('admin.atu.')->group(function () {
    Route::livewire('currencies', 'currencies.index')->name('currencies.index');
    Route::livewire('currencies/create', 'currencies.create')->name('currencies.create');
    Route::livewire('currencies/edit/{id}', 'currencies.edit')->name('currencies.edit');
    Route::livewire('currencies/settings', 'currencies.settings')->name('currencies.settings');
    Route::livewire('currencies/logs', 'currencies.logs')->name('currencies.logs');
});
// >>> ATU Multi-Currency Web Routes END
