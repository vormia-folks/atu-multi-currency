<?php

// ATU Multi-Currency admin Volt routes (optional manual merge)
// The package registers the same routes when livewire/volt is installed.
// Add inside: Route::middleware(['auth'])->group(function () { ... });
// You may need: use Livewire\Volt\Volt;

// >>> ATU Multi-Currency Web Routes START
Route::prefix('admin/atu')->name('admin.atu.')->group(function () {
    Volt::route('currencies', 'currencies.index')->name('currencies.index');
    Volt::route('currencies/create', 'currencies.create')->name('currencies.create');
    Volt::route('currencies/edit/{id}', 'currencies.edit')->name('currencies.edit');
    Volt::route('currencies/settings', 'currencies.settings')->name('currencies.settings');
    Volt::route('currencies/logs', 'currencies.logs')->name('currencies.logs');
});
// >>> ATU Multi-Currency Web Routes END
