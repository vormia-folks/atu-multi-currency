<?php

use Illuminate\Support\Facades\Route;

/*
| Admin Livewire 4 full-page UI for ATU Multi-Currency. Requires livewire/livewire ^4.
| View components are registered via Livewire::addLocation in ATUMultiCurrencyServiceProvider::boot().
*/
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('admin/atu')->name('admin.atu.')->group(function () {
        Route::livewire('currencies', 'currencies.index')->name('currencies.index');
        Route::livewire('currencies/create', 'currencies.create')->name('currencies.create');
        Route::livewire('currencies/edit/{id}', 'currencies.edit')->name('currencies.edit');
        Route::livewire('currencies/settings', 'currencies.settings')->name('currencies.settings');
        Route::livewire('currencies/logs', 'currencies.logs')->name('currencies.logs');
    });
});
