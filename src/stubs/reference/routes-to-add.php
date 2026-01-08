<?php

// ATU Multi-Currency Routes
// Add these routes to your routes/web.php file
// Place them inside: Route::middleware(['auth'])->group(function () { ... });
// Note: If you have configured your own starterkit, you may need to add: use Livewire\Volt\Volt;

Route::group(['prefix' => 'admin/atu'], function () {
    // Currencies
    Volt::route('currencies', 'admin.atu.currencies.index')->name('admin.atu.currencies.index');
    Volt::route('currencies/create', 'admin.atu.currencies.create')->name('admin.atu.currencies.create');
    Volt::route('currencies/edit/{id}', 'admin.atu.currencies.edit')->name('admin.atu.currencies.edit');
    Volt::route('currencies/settings', 'admin.atu.currencies.settings')->name('admin.atu.currencies.settings');
    Volt::route('currencies/logs', 'admin.atu.currencies.logs')->name('admin.atu.currencies.logs');
});
