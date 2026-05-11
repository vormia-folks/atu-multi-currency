<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ATU Multi-Currency package
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency code. This should match the base currency
    | in a2_ec_settings.currency_code
    |
    */
    'default_currency' => env('A2_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Currency API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic currency rate updates
    |
    */
    'api' => [
        'key' => env('ATU_CURRENCY_API_KEY', ''),
        'update_frequency' => env('ATU_CURRENCY_UPDATE_FREQUENCY', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversion Settings
    |--------------------------------------------------------------------------
    |
    | Settings for currency conversion behavior
    |
    */
    'conversion' => [
        'apply_fees' => true,
        'log_conversions' => true,
        'round_precision' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | All ATU Multi-Currency tables use this prefix
    |
    */
    'table_prefix' => 'atu_multicurrency_',

    /*
    |--------------------------------------------------------------------------
    | Settings Source
    |--------------------------------------------------------------------------
    |
    | Determines where settings are stored and retrieved from.
    | Options: 'file' (config file) or 'database' (atu_multicurrency_settings table)
    |
    */
    'settings_source' => env('ATU_CURRENCY_SETTINGS_SOURCE', 'file'),
];
