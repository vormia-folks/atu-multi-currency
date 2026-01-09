<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vormia\ATUMultiCurrency\Models\Currency;

class ATUMultiCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if default currency already exists
        $existingDefault = Currency::where('is_default', true)->first();

        if ($existingDefault) {
            $this->command->info('Default currency already exists. Skipping seeder.');
            return;
        }

        // Try to read base currency from a2_ec_settings
        $currencyCode = 'USD';
        $currencySymbol = '$';

        try {
            if (DB::getSchemaBuilder()->hasTable('a2_ec_settings')) {
                // Get currency_code and currency_symbol from key-value settings table
                $a2CurrencyCode = DB::table('a2_ec_settings')
                    ->where('key', 'currency_code')
                    ->value('value');

                $a2CurrencySymbol = DB::table('a2_ec_settings')
                    ->where('key', 'currency_symbol')
                    ->value('value');

                if ($a2CurrencyCode && $a2CurrencySymbol) {
                    $currencyCode = $a2CurrencyCode;
                    $currencySymbol = $a2CurrencySymbol;
                    $this->command->info("Found base currency from a2_ec_settings: {$currencyCode} ({$currencySymbol})");
                } else {
                    $this->command->warn('a2_ec_settings table exists but currency_code or currency_symbol not found. Using default USD/$');
                }
            } else {
                $this->command->warn('a2_ec_settings table does not exist. Using default USD/$');
            }
        } catch (\Exception $e) {
            $this->command->warn("Could not read from a2_ec_settings: {$e->getMessage()}. Using default USD/$");
            Log::warning('ATU Multi-Currency: Could not read from a2_ec_settings', [
                'error' => $e->getMessage()
            ]);
        }

        // Create default currency
        Currency::create([
            'code' => strtoupper($currencyCode),
            'symbol' => $currencySymbol,
            'rate' => '1.00000000',
            'is_auto' => false,
            'fee' => null,
            'is_default' => true,
            'country_taxonomy_id' => null,
            'is_active' => true,
        ]);

        $this->command->info("✅ Default currency created: {$currencyCode} ({$currencySymbol}) with rate 1.00000000");
    }
}
