<?php

namespace Vormia\ATUMultiCurrency\Http\Controllers\Api\Atu\Multicurrency;

use Illuminate\Http\Request;
use Vormia\ATUMultiCurrency\Models\Currency;
use Vormia\ATUMultiCurrency\Support\SettingsManager;

class CurrencySettingsController extends ApiController
{
    public function show()
    {
        try {
            $settingsManager = app(SettingsManager::class);
            $defaultCurrency = Currency::where('is_default', true)->first();

            $conversionSettings = $settingsManager->getSetting('conversion', [
                'apply_fees' => true,
                'log_conversions' => true,
                'round_precision' => 2,
            ]);

            if (! is_array($conversionSettings)) {
                $conversionSettings = [
                    'apply_fees' => config('atu-multi-currency.conversion.apply_fees', true),
                    'log_conversions' => config('atu-multi-currency.conversion.log_conversions', true),
                    'round_precision' => config('atu-multi-currency.conversion.round_precision', 2),
                ];
            }

            return $this->success(
                [
                    'settings_source' => config('atu-multi-currency.settings_source', 'file'),
                    'default_currency_code' => $defaultCurrency ? $defaultCurrency->code : 'USD',
                    'conversion' => $conversionSettings,
                ],
                'Currency settings fetched',
                200
            );
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'apply_fees' => 'required|boolean',
                'log_conversions' => 'required|boolean',
                'round_precision' => 'required|integer|min:0|max:10',
            ]);

            $settingsManager = app(SettingsManager::class);

            if (! $settingsManager->isDatabaseSource()) {
                return $this->error(
                    'Settings are currently managed from config file. Set ATU_CURRENCY_SETTINGS_SOURCE=database in .env to enable database settings.',
                    422
                );
            }

            $conversionSettings = [
                'apply_fees' => (bool) $validated['apply_fees'],
                'log_conversions' => (bool) $validated['log_conversions'],
                'round_precision' => (int) $validated['round_precision'],
            ];

            $settingsManager->setSetting('conversion', $conversionSettings);

            return $this->success(
                [
                    'conversion' => $conversionSettings,
                ],
                'Currency settings updated',
                200
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), $e->getMessage());
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 500);
        }
    }
}
