<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
| Admin Volt UI for ATU Multi-Currency. Requires livewire/volt and livewire/livewire.
| Views are mounted from the package in ATUMultiCurrencyServiceProvider::boot().
*/
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('admin/atu')->name('admin.atu.')->group(function () {
        Volt::route('currencies', 'currencies.index')->name('currencies.index');
        Volt::route('currencies/create', 'currencies.create')->name('currencies.create');
        Volt::route('currencies/edit/{id}', 'currencies.edit')->name('currencies.edit');
        Volt::route('currencies/settings', 'currencies.settings')->name('currencies.settings');
        Volt::route('currencies/logs', 'currencies.logs')->name('currencies.logs');
    });
});
