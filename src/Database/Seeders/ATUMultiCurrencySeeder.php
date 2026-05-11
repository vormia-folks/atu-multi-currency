<?php

namespace Vormia\ATUMultiCurrency\Database\Seeders;

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
        $existingDefault = Currency::where('is_default', true)->first();

        if ($existingDefault) {
            $this->info('Default currency already exists. Skipping seeder.');

            return;
        }

        $currencyCode = 'USD';
        $currencySymbol = '$';

        try {
            if (DB::getSchemaBuilder()->hasTable('a2_ec_settings')) {
                $a2CurrencyCode = DB::table('a2_ec_settings')
                    ->where('key', 'currency_code')
                    ->value('value');

                $a2CurrencySymbol = DB::table('a2_ec_settings')
                    ->where('key', 'currency_symbol')
                    ->value('value');

                if ($a2CurrencyCode && $a2CurrencySymbol) {
                    $currencyCode = $a2CurrencyCode;
                    $currencySymbol = $a2CurrencySymbol;
                    $this->info("Found base currency from a2_ec_settings: {$currencyCode} ({$currencySymbol})");
                } else {
                    $this->warn('a2_ec_settings table exists but currency_code or currency_symbol not found. Using default USD/$');
                }
            } else {
                $this->warn('a2_ec_settings table does not exist. Using default USD/$');
            }
        } catch (\Exception $e) {
            $this->warn("Could not read from a2_ec_settings: {$e->getMessage()}. Using default USD/$");
            Log::warning('ATU Multi-Currency: Could not read from a2_ec_settings', [
                'error' => $e->getMessage(),
            ]);
        }

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

        $this->info("Default currency created: {$currencyCode} ({$currencySymbol}) with rate 1.00000000");
    }

    private function info(string $message): void
    {
        $this->command?->info($message);
        Log::info('ATU Multi-Currency seeder: ' . $message);
    }

    private function warn(string $message): void
    {
        $this->command?->warn($message);
        Log::warning('ATU Multi-Currency seeder: ' . $message);
    }
}
