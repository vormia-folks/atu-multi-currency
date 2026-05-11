<?php

use Illuminate\Support\Facades\Route;
use Vormia\ATUMultiCurrency\Http\Controllers\Api\Atu\Multicurrency\CurrencyController;
use Vormia\ATUMultiCurrency\Http\Controllers\Api\Atu\Multicurrency\CurrencyLogsController;
use Vormia\ATUMultiCurrency\Http\Controllers\Api\Atu\Multicurrency\CurrencySettingsController;

Route::middleware('api')->prefix('api/atu/currency')->group(function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('api.atu.currency.index');
    Route::get('/current', [CurrencyController::class, 'current'])->name('api.atu.currency.current');
    Route::post('/switch', [CurrencyController::class, 'switch'])->name('api.atu.currency.switch');

    Route::post('/', [CurrencyController::class, 'store'])->name('api.atu.currency.store');
    Route::put('/{id}', [CurrencyController::class, 'update'])->name('api.atu.currency.update');
    Route::delete('/{id}', [CurrencyController::class, 'destroy'])->name('api.atu.currency.destroy');
    Route::patch('/{id}/toggle-active', [CurrencyController::class, 'toggleActive'])->name('api.atu.currency.toggle_active');
    Route::patch('/{id}/set-default', [CurrencyController::class, 'setDefault'])->name('api.atu.currency.set_default');

    Route::get('/settings', [CurrencySettingsController::class, 'show'])->name('api.atu.currency.settings.show');
    Route::put('/settings', [CurrencySettingsController::class, 'update'])->name('api.atu.currency.settings.update');

    Route::get('/logs/conversions', [CurrencyLogsController::class, 'conversionLogs'])->name('api.atu.currency.logs.conversions');
});
