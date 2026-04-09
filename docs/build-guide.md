## Build guide (ATU Multi-Currency)

This guide explains how the package is intended to be installed and how the “core only” vs “UI” parts fit together.

### What this package is (and isn’t)

- **Projection layer**: your commerce tables keep prices in the **base/system currency**. ATU Multi-Currency handles conversion for display/reporting without mutating the authoritative amounts.
- **ATU-owned tables**: exchange rates, rate history, conversion logs, and settings live in the `atu_multicurrency_*` tables.

### Install (package)

```sh
composer require vormia-folks/atu-multi-currency
```

### Install (copy stubs + optional wiring)

```sh
php artisan atumulticurrency:install
```

What the installer does (high-level):

- Copies stubs from `src/stubs/` into your app (migrations, config, seeder, controllers, and optionally UI resources)
- Optionally appends env keys to `.env` and `.env.example` (only if those files exist)
- Optionally appends a **commented** API route block to `routes/api.php`
- Prompts to run migrations, then prompts to run the base-currency seeder

### Installer flags

- **`--api`**: core-only install (skip route modification and skip UI resources)
- **`--skip-env`**: do not touch `.env` / `.env.example`
- **`--no-overwrite`**: do not overwrite existing copied files

### Migrations / tables

The package migrations create:

- **`atu_multicurrency_currencies`**
- **`atu_multicurrency_currency_rates_log`**
- **`atu_multicurrency_currency_conversion_log`**
- **`atu_multicurrency_settings`**

### Seeding (base currency)

You can seed via the installer prompt, or run manually:

```sh
php artisan db:seed --class=ATUMultiCurrencySeeder
```

Behavior:

- If A2Commerce’s `a2_ec_settings` exists, the seeder reads base currency fields from there.
- If it doesn’t exist, it falls back to **USD / $**.
- Ensures a **default currency** with rate locked at **1.0**.

### Configuration & env

Published config file:

- `config/atu-multi-currency.php`

Env keys used by this package:

- `ATU_CURRENCY_API_KEY`
- `ATU_CURRENCY_UPDATE_FREQUENCY`
- `ATU_CURRENCY_SETTINGS_SOURCE`

### API routes (optional)

The installer can append a commented block to `routes/api.php`.

To enable:

- Uncomment everything between:
  - `// >>> ATU Multi-Currency Routes START`
  - `// >>> ATU Multi-Currency Routes END`
- Add middleware as needed (auth/admin/throttle/etc).

