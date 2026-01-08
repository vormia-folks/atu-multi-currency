<?php

namespace Vormia\ATUMultiCurrency\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CurrencySyncService
{
    /**
     * Flag to prevent infinite sync loops
     * 
     * @var bool
     */
    private static $syncing = false;

    /**
     * Sync ATU MultiCurrency default currency to A2Commerce settings
     * 
     * Updates currency_code and currency_symbol in a2_ec_settings table
     * 
     * @return bool True if sync was successful, false otherwise
     */
    public function syncToA2Commerce(): bool
    {
        // Prevent infinite loops
        if (self::$syncing) {
            return false;
        }

        try {
            // Check if a2_ec_settings table exists
            if (!DB::getSchemaBuilder()->hasTable('a2_ec_settings')) {
                Log::debug('ATU MultiCurrency: a2_ec_settings table does not exist. Skipping sync.');
                return false;
            }

            // Get default currency from ATU MultiCurrency
            $defaultCurrency = $this->getDefaultCurrency();
            
            if (!$defaultCurrency) {
                Log::warning('ATU MultiCurrency: No default currency found. Cannot sync to A2Commerce.');
                return false;
            }

            self::$syncing = true;

            // Update or insert currency_code in a2_ec_settings
            DB::table('a2_ec_settings')->updateOrInsert(
                ['key' => 'currency_code'],
                [
                    'value' => strtoupper($defaultCurrency->code),
                    'updated_at' => now(),
                ]
            );

            // Update or insert currency_symbol in a2_ec_settings
            DB::table('a2_ec_settings')->updateOrInsert(
                ['key' => 'currency_symbol'],
                [
                    'value' => $defaultCurrency->symbol,
                    'updated_at' => now(),
                ]
            );

            Log::info('ATU MultiCurrency: Successfully synced default currency to A2Commerce', [
                'code' => $defaultCurrency->code,
                'symbol' => $defaultCurrency->symbol,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('ATU MultiCurrency: Failed to sync to A2Commerce', [
                'error' => $e->getMessage(),
            ]);
            return false;
        } finally {
            self::$syncing = false;
        }
    }

    /**
     * Sync A2Commerce settings to ATU MultiCurrency default currency
     * 
     * Updates the default currency's code and symbol based on a2_ec_settings
     * Only updates if the symbol matches to avoid conflicts
     * 
     * @return bool True if sync was successful, false otherwise
     */
    public function syncFromA2Commerce(): bool
    {
        // Prevent infinite loops
        if (self::$syncing) {
            return false;
        }

        try {
            // Check if a2_ec_settings table exists
            if (!DB::getSchemaBuilder()->hasTable('a2_ec_settings')) {
                Log::debug('ATU MultiCurrency: a2_ec_settings table does not exist. Skipping sync.');
                return false;
            }

            // Get currency from A2Commerce settings
            $a2Currency = $this->getA2CommerceCurrency();
            
            if (!$a2Currency || !isset($a2Currency['code']) || !isset($a2Currency['symbol'])) {
                Log::debug('ATU MultiCurrency: A2Commerce currency settings not found. Skipping sync.');
                return false;
            }

            // Get default currency from ATU MultiCurrency
            $defaultCurrency = $this->getDefaultCurrency();
            
            if (!$defaultCurrency) {
                Log::warning('ATU MultiCurrency: No default currency found. Cannot sync from A2Commerce.');
                return false;
            }

            // Only sync if symbol matches to avoid conflicts
            // This ensures we're updating the correct currency
            if ($defaultCurrency->symbol !== $a2Currency['symbol']) {
                Log::debug('ATU MultiCurrency: Symbol mismatch. Skipping sync from A2Commerce.', [
                    'atu_symbol' => $defaultCurrency->symbol,
                    'a2_symbol' => $a2Currency['symbol'],
                ]);
                return false;
            }

            self::$syncing = true;

            // Update default currency code and symbol
            DB::table('atu_multicurrency_currencies')
                ->where('id', $defaultCurrency->id)
                ->update([
                    'code' => strtoupper($a2Currency['code']),
                    'symbol' => $a2Currency['symbol'],
                    'updated_at' => now(),
                ]);

            Log::info('ATU MultiCurrency: Successfully synced from A2Commerce to default currency', [
                'code' => $a2Currency['code'],
                'symbol' => $a2Currency['symbol'],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('ATU MultiCurrency: Failed to sync from A2Commerce', [
                'error' => $e->getMessage(),
            ]);
            return false;
        } finally {
            self::$syncing = false;
        }
    }

    /**
     * Bidirectional sync - checks both and updates if needed
     * 
     * This method compares both sources and updates the one that needs updating
     * 
     * @return bool True if sync was successful, false otherwise
     */
    public function syncBoth(): bool
    {
        // Prevent infinite loops
        if (self::$syncing) {
            return false;
        }

        try {
            $defaultCurrency = $this->getDefaultCurrency();
            $a2Currency = $this->getA2CommerceCurrency();

            // If no default currency, cannot sync
            if (!$defaultCurrency) {
                return false;
            }

            // If no A2Commerce currency, sync to A2Commerce
            if (!$a2Currency || !isset($a2Currency['code']) || !isset($a2Currency['symbol'])) {
                return $this->syncToA2Commerce();
            }

            // Compare and determine which needs updating
            $codeMatches = strtoupper($defaultCurrency->code) === strtoupper($a2Currency['code']);
            $symbolMatches = $defaultCurrency->symbol === $a2Currency['symbol'];

            // If both match, no sync needed
            if ($codeMatches && $symbolMatches) {
                return true;
            }

            // If ATU is different, sync to A2Commerce (ATU is source of truth for default currency)
            if (!$codeMatches || !$symbolMatches) {
                return $this->syncToA2Commerce();
            }

            return true;
        } catch (\Exception $e) {
            Log::error('ATU MultiCurrency: Failed to sync both directions', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get the default currency from ATU MultiCurrency
     * 
     * @return object|null The default currency object or null if not found
     */
    public function getDefaultCurrency(): ?object
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('atu_multicurrency_currencies')) {
                return null;
            }

            return DB::table('atu_multicurrency_currencies')
                ->where('is_default', true)
                ->first();
        } catch (\Exception $e) {
            Log::error('ATU MultiCurrency: Failed to get default currency', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get currency from A2Commerce settings
     * 
     * @return array|null Array with 'code' and 'symbol' keys, or null if not found
     */
    public function getA2CommerceCurrency(): ?array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('a2_ec_settings')) {
                return null;
            }

            $currencyCode = DB::table('a2_ec_settings')
                ->where('key', 'currency_code')
                ->value('value');

            $currencySymbol = DB::table('a2_ec_settings')
                ->where('key', 'currency_symbol')
                ->value('value');

            if ($currencyCode === null || $currencySymbol === null) {
                return null;
            }

            return [
                'code' => $currencyCode,
                'symbol' => $currencySymbol,
            ];
        } catch (\Exception $e) {
            Log::error('ATU MultiCurrency: Failed to get A2Commerce currency', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
