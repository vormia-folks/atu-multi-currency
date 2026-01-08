<?php

namespace Vormia\ATUMultiCurrency\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SettingsManager
{
    /**
     * Get a setting value from database or config file
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        $settingsSource = config('atu-multi-currency.settings_source', 'file');

        if ($settingsSource === 'database') {
            $setting = DB::table('atu_multicurrency_settings')
                ->where('key', $key)
                ->first();

            if ($setting && $setting->value !== null) {
                $decoded = json_decode($setting->value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
            }
        }

        // Fallback to config file
        return config("atu-multi-currency.{$key}", $default);
    }

    /**
     * Set a setting value in database
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setSetting(string $key, $value): bool
    {
        $settingsSource = config('atu-multi-currency.settings_source', 'file');

        if ($settingsSource !== 'database') {
            return false;
        }

        $jsonValue = is_array($value) || is_object($value) 
            ? json_encode($value) 
            : $value;

        try {
            DB::table('atu_multicurrency_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $jsonValue, 'updated_at' => now()]
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all settings from database or config
     *
     * @return array
     */
    public function getAllSettings(): array
    {
        $settingsSource = config('atu-multi-currency.settings_source', 'file');

        if ($settingsSource === 'database') {
            $settings = DB::table('atu_multicurrency_settings')->get();
            $result = [];

            foreach ($settings as $setting) {
                $decoded = json_decode($setting->value, true);
                $result[$setting->key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
            }

            return $result;
        }

        // Return config values (excluding API config)
        return [
            'default_currency' => config('atu-multi-currency.default_currency', 'USD'),
            'conversion' => config('atu-multi-currency.conversion', [
                'apply_fees' => true,
                'log_conversions' => true,
                'round_precision' => 2,
            ]),
        ];
    }

    /**
     * Check if settings source is database
     *
     * @return bool
     */
    public function isDatabaseSource(): bool
    {
        return config('atu-multi-currency.settings_source', 'file') === 'database';
    }
}
