<?php

// ATU Multi-Currency Routes
// Add these routes to your routes/web.php file
// Place them inside: Route::middleware(['auth'])->group(function () { ... });
// Note: If you have configured your own starterkit, you may need to add: use Livewire\Volt\Volt;

Route::prefix('admin/atu/currencies')->name('admin.atu.currencies.')->group(function () {
    // Currencies
    Volt::route('', 'admin.atu.currencies.index')->name('index');
    Volt::route('create', 'admin.atu.currencies.create')->name('create');
    Volt::route('edit/{id}', 'admin.atu.currencies.edit')->name('edit');
    Volt::route('settings', 'admin.atu.currencies.settings')->name('settings');
    Volt::route('logs', 'admin.atu.currencies.logs')->name('logs');
});
