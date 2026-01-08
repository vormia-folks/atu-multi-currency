# ATU Multi-Currency

Laravel 12 package that provides currency normalization, conversion, display, and reporting support for A2 Commerce. This package extends A2Commerce functionality while keeping A2 core tables simple and authoritative.

## Introduction

ATU Multi-Currency is a comprehensive Laravel package designed to provide multi-currency support for e-commerce applications built on A2Commerce. The package acts as a projection layer that converts, decorates, logs, and reports currency conversions without mutating the core A2Commerce data.

The package follows a core principle: **A2 Commerce owns truth** - all prices stored in A2Commerce tables remain in the base system currency, while ATU Multi-Currency handles all conversion complexity, rate management, and reporting.

## Features

- **Currency Management** - Support for multiple currencies with automatic or manual rate management
- **Conversion Logging** - Immutable audit trail of all currency conversions
- **Rate History** - Track historical exchange rates for accurate reporting
- **Fee Configuration** - Optional per-currency conversion fees
- **Base Currency Seeding** - Automatic seeding from A2Commerce settings
- **Projection Layer** - Convert and display prices without mutating core data
- **Reporting Support** - Multi-currency financial reporting capabilities
- **Event-Driven** - Integrates seamlessly with A2Commerce event system

## Requirements

- PHP 8.2+
- Laravel 12.x
- Vormia 4.2+ (must be installed first)
- A2Commerce 0.1.6+ (must be installed first)

## Dependencies

### Required Dependencies

- **vormiaphp/vormia**: Required for core functionality and database structure
  - Used for user management, taxonomies, and meta data handling
  - Install with: `composer require vormiaphp/vormia`

- **a2-atu/a2commerce**: Required for e-commerce functionality
  - Provides the base commerce system that this package extends
  - Install with: `composer require a2-atu/a2commerce`

The package will automatically check for required dependencies during installation and provide helpful error messages if they're missing.

## Installation

Before installing ATU Multi-Currency, ensure you have Laravel, Vormia, and A2Commerce installed.

### Step 1: Install Laravel

Follow the [Laravel installation guide](https://laravel.com/docs/installation) to install Laravel.

### Step 2: Install Vormia

ATU Multi-Currency requires Vormia to be installed first. Follow the [Vormia installation guide](https://github.com/vormiaphp/vormia) to install Vormia.

### Step 3: Install A2Commerce

ATU Multi-Currency extends A2Commerce functionality. Install A2Commerce first:

```sh
composer require a2-atu/a2commerce
php artisan a2commerce:install
```

### Step 4: Install ATU Multi-Currency

```sh
composer require vormia-folks/atu-multi-currency
```

### Step 5: Run ATU Multi-Currency Installation

```sh
php artisan atumulticurrency:install
```

This will automatically install ATU Multi-Currency with all files and configurations:

**Automatically Installed:**

- ✅ All migration files copied to `database/migrations`
- ✅ Seeder file copied to `database/seeders`
- ✅ Configuration file copied to `config/atu-multi-currency.php`
- ✅ Environment variables added to `.env` and `.env.example`
- ✅ Currency routes (commented out) added to `routes/api.php`

**Installation Options:**

- `--no-overwrite`: Keep existing files instead of replacing them
- `--skip-env`: Leave `.env` files untouched

**Example:**

```sh
# Install without overwriting existing files
php artisan atumulticurrency:install --no-overwrite

# Install without modifying .env files
php artisan atumulticurrency:install --skip-env
```

### Step 6: Run Migrations and Seeders

The installation command will prompt you to run migrations and seeders. You can also run them manually:

```sh
# Run migrations
php artisan migrate

# Run seeders to create base currency
php artisan db:seed --class=ATUMultiCurrencySeeder
```

The seeder will automatically read the base currency from `a2_ec_settings` table and create the default currency with a rate of 1.00000000.

## Available Commands

### Install Command

Install the package with all necessary files and configurations:

```sh
php artisan atumulticurrency:install
```

**Options:**
- `--skip-env`: Do not modify .env files
- `--no-overwrite`: Skip existing files instead of replacing

### Refresh Command

Refresh migrations and seeders, clear caches:

```sh
php artisan atumulticurrency:refresh
```

**Options:**
- `--force`: Skip confirmation prompts
- `--seed`: Force re-seeding

This command will:
- Rollback and re-run migrations for `atu_multicurrency_*` tables
- Re-run seeders to restore base currency
- Clear all application caches

### Uninstall Command

Remove all package files and configurations:

```sh
php artisan atumulticurrency:uninstall
```

**Options:**
- `--keep-env`: Preserve environment variables
- `--force`: Skip confirmation prompts

**⚠️ Warning:** This will remove all ATU Multi-Currency files and optionally drop database tables. A backup will be created in `storage/app/atumulticurrency-final-backup-{timestamp}/`.

### Help Command

Display help information and usage examples:

```sh
php artisan atumulticurrency:help
```

## Configuration

After installation, you can configure the package in `config/atu-multi-currency.php`:

```php
return [
    'default_currency' => env('A2_CURRENCY', 'USD'),
    
    'api' => [
        'key' => env('ATU_CURRENCY_API_KEY', ''),
        'update_frequency' => env('ATU_CURRENCY_UPDATE_FREQUENCY', 'daily'),
    ],
    
    'conversion' => [
        'apply_fees' => true,
        'log_conversions' => true,
        'round_precision' => 2,
    ],
    
    'table_prefix' => 'atu_multicurrency_',
];
```

## Environment Variables

The following environment variables are added to your `.env` file during installation:

```env
# ATU Multi-Currency Configuration
ATU_CURRENCY_API_KEY=
ATU_CURRENCY_UPDATE_FREQUENCY=daily
```

- `ATU_CURRENCY_API_KEY`: API key for automatic currency rate updates (optional)
- `ATU_CURRENCY_UPDATE_FREQUENCY`: How often to update currency rates (daily, weekly, etc.)

## Database Tables

The package creates three database tables with the `atu_multicurrency_` prefix:

### `atu_multicurrency_currencies`

Holds supported currencies and conversion rules:
- Currency code (ISO 4217)
- Symbol
- Exchange rate
- Fee configuration
- Default currency flag
- Active status

### `atu_multicurrency_currency_rates_log`

Tracks historical exchange rates:
- Currency reference
- Rate value
- Source (manual or API)
- Timestamp

### `atu_multicurrency_currency_conversion_log`

Immutable audit trail of all conversions:
- Entity type and ID
- Conversion context
- Base and target currencies
- Amounts and rates used
- Fees applied
- User and timestamp

## Core Principles

1. **A2 Commerce owns truth** - Prices in A2Commerce tables are always in base currency
2. **ATU is a projection layer** - Converts, decorates, logs, and reports without mutating core data
3. **All complexity lives inside ATU** - Conversion history, rates, fees, and reporting
4. **Logs are immutable** - Never update conversion logs, always insert new rows

## Usage

### Seeding Base Currency

The package automatically seeds the base currency from A2Commerce settings during installation. The seeder reads:
- `currency_code` from `a2_ec_settings` → `atu_multicurrency_currencies.code`
- `currency_symbol` from `a2_ec_settings` → `atu_multicurrency_currencies.symbol`

If `a2_ec_settings` doesn't exist, it defaults to USD/$.

### Adding Additional Currencies

You can add additional currencies by inserting records into the `atu_multicurrency_currencies` table:

```php
DB::table('atu_multicurrency_currencies')->insert([
    'code' => 'KES',
    'symbol' => 'KSh',
    'rate' => '130.50000000',
    'is_auto' => true,
    'fee' => null,
    'is_default' => false,
    'is_active' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

## Documentation

For detailed implementation guides and architecture documentation, see:

- **Build Guide**: `docs/build-guide.md` - Authoritative implementation guide
- **A2Commerce Documentation**: See A2Commerce package documentation for base functionality

## Uninstallation

To completely remove the package:

```sh
# Uninstall package files and optionally drop tables
php artisan atumulticurrency:uninstall

# Remove from composer
composer remove vormia-folks/atu-multi-currency
```

**Note:** The uninstall command will:
- Remove all copied files and stubs
- Remove routes from `routes/api.php`
- Optionally drop database tables (with confirmation)
- Optionally remove environment variables
- Create a backup before removal

## Troubleshooting

### Seeder Fails

If the seeder fails because `a2_ec_settings` table doesn't exist:
- Ensure A2Commerce is installed and migrations have been run
- The seeder will fallback to USD/$ if the table is missing

### Migration Errors

If migrations fail:
- Ensure all A2Commerce migrations have been run first
- Check that the database connection is configured correctly
- Verify foreign key constraints are supported

### Currency Not Found

If base currency is not found:
- Run the seeder manually: `php artisan db:seed --class=ATUMultiCurrencySeeder`
- Check that `a2_ec_settings` table exists and has currency data

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For issues, questions, or contributions:
- Check the documentation in `docs/build-guide.md`
- Review A2Commerce documentation for base functionality
- Open an issue on the package repository

## Version

Current version: **0.1.0**

---

**Built with ❤️ for the A2 Commerce ecosystem**
