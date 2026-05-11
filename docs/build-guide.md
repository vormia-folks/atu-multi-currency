## Build guide (ATU Multi-Currency)

How the package is meant to be used in a Laravel application: **code ships in `vendor`**, and the app gets database tables, merged config, routes, and optional Volt admin pages through Laravel’s normal package mechanisms.

### What this package is

- **Projection layer** — Commerce amounts stay in the **base/system currency** in A2 (or your app). ATU converts for display, APIs, and reporting and records conversions in its own tables.
- **Self-contained wiring** — Migrations are loaded with `loadMigrationsFrom`. API routes load from `routes/atu-multicurrency-api.php`. Config is merged from the package. When `livewire/volt` is present, Volt views are mounted from the package and web routes load from `routes/atumulticurrency-web.php`.
- **Installer is thin** — `atumulticurrency:install` adds `.env` keys (unless `--skip-env`) and prompts for migrate/seed. It does **not** copy migrations, controllers, or views into your project tree.

### Requirements

- PHP 8.2+, Laravel 12 or 13, Vormia 5.x
- Optional: Livewire + Volt + Flux-related packages for the admin UI (see `composer.json` `suggest` and `atumulticurrency:help`)

### Install

```sh
composer require vormia-folks/atu-multi-currency
php artisan atumulticurrency:install
```

`install` options:

- **`--skip-env`** — Do not append keys to `.env` / `.env.example`

### Migrations

Tables (prefix configurable):

- `atu_multicurrency_currencies`
- `atu_multicurrency_currency_rates_log`
- `atu_multicurrency_currency_conversion_log`
- `atu_multicurrency_settings`

Run:

```sh
php artisan migrate
```

Migrations are discovered from the package; `migrate` records them like any other migration.

### Refresh (development / reset)

```sh
php artisan atumulticurrency:refresh
```

Rolls back this package’s migrations using per-file `--path` resolution (via `ATUMultiCurrency::migrationsPathRelativeToBase()`), then runs `migrate` again. Options: `--force`, `--seed`.

### Seeding

```sh
php artisan db:seed --class="Vormia\\ATUMultiCurrency\\Database\\Seeders\\ATUMultiCurrencySeeder"
```

Behavior:

- If a default currency already exists, the seeder exits without inserting.
- If `a2_ec_settings` exists, reads `currency_code` and `currency_symbol` from **rows** where `key` matches those names.
- Otherwise uses **USD** / **`$`** and creates the default row with rate **1.0**.

### Configuration and environment

Merged package config: `config/atu-multi-currency.php` inside the package. Override with:

```sh
php artisan vendor:publish --tag=atumulticurrency-config
```

Environment keys (added by the installer when possible):

- `ATU_CURRENCY_API_KEY`
- `ATU_CURRENCY_UPDATE_FREQUENCY`
- `ATU_CURRENCY_SETTINGS_SOURCE`

### HTTP surface

- **API** — Prefix `/api/atu/currency`, middleware `api`, route names `api.atu.currency.*`
- **Admin Volt** — When Volt is installed: `/admin/atu/currencies`, route names `admin.atu.currencies.*`

Add authentication, authorization, and rate limiting in your app as required (for example middleware on route groups or a consuming gateway).

### Uninstall

```sh
php artisan atumulticurrency:uninstall
```

Can remove env keys and optionally roll back migrations. Remove the Composer dependency when you no longer want the package:

```sh
composer remove vormia-folks/atu-multi-currency
```

### Further reading

- **Changelog** — `CHANGELOG.md` (root)
- **UI commands and Flux sidebar** — `docs/build-ui-guide.md`
- **Release notes** — `docs/releases/v2.1.0.md` (current minor)
- **Package authoring template** — `docs/package-creation-guide.md`
