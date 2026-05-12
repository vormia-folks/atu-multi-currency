# ATU Multi-Currency

Laravel package for currency normalization, conversion, display, and reporting alongside A2 Commerce. Core commerce data stays in the **base system currency**; this package adds rates, logs, settings, and APIs without replacing A2 as the source of truth.

## Introduction

ATU Multi-Currency is a **projection layer**: it converts and decorates for display and reporting, keeps an audit trail, and syncs optional settings with A2Commerce when that stack is present.

**A2 Commerce owns truth** — amounts in A2Commerce stay in base currency. ATU owns `atu_multicurrency_*` tables, conversion logs, and admin/API surfaces.

**How the package is wired (v2.x)** — After `composer require`, Laravel loads **migrations, merged config, API routes, and admin Livewire routes** directly from the package under `vendor` (`livewire/livewire` ^4 is required). The installer **does not copy** migrations, controllers, or views into your app; it mainly ensures `.env` keys and walks you through migrate/seed.

## Features

- **Currency management** — Multiple currencies, manual or automatic rates, optional fees
- **Flexible codes** — 3–4 character currency codes (ISO-style), optional display names
- **Smart fallbacks** — Empty code or symbol falls back to the other when appropriate
- **Conversion logging** — Immutable conversion audit rows
- **Rate history** — Historical exchange rates
- **Settings storage** — Database-backed settings (configurable via `ATU_CURRENCY_SETTINGS_SOURCE`)
- **A2Commerce integration** — Optional read/sync of base currency via `a2_ec_settings` (key/value rows)
- **JSON API** — Registered under `/api/atu/currency`
- **Admin UI** — Livewire 4 full-page components served from the package
- **Artisan tooling** — Install, refresh, uninstall, UI checks, and help

## Requirements

- PHP 8.2+
- Laravel 12.x or 13.x
- [Vormia](https://github.com/vormiaphp/vormia) 5.x (install in your app first)

### Optional (Flux admin shell)

- `vormiaphp/ui-livewireflux-admin` — required by `atumulticurrency:ui-install` for layout compatibility checks
- `livewire/flux` — optional; use `ui-install --inject-sidebar` to merge menu snippets

`livewire/livewire` **^4** is required by this package (declared in `composer.json`). See `composer.json` `suggest` for optional Flux-related packages.

## Dependencies

- **livewire/livewire** (required, ^4) — Admin UI routes and single-file components
- **vormiaphp/vormia** (required) — Users, taxonomies, and related infrastructure

**A2Commerce** is optional. If `a2_ec_settings` exists, the seeder and sync services can align the default currency with A2; otherwise defaults apply (for example USD).

## Installation

### 1. Require the package

```sh
composer require vormia-folks/atu-multi-currency
```

The service provider is discovered automatically.

### 2. Run the installer (optional `.env`, migrate, seed)

```sh
php artisan atumulticurrency:install
```

This command:

- Appends missing **ATU Multi-Currency** keys to `.env` and `.env.example` (unless `--skip-env`)
- Explains that **routes, config, migrations, and Livewire views load from vendor**
- Prompts to run `php artisan migrate` (package migrations are loaded via `loadMigrationsFrom`)
- Prompts to run the **base currency** seeder

**Option:**

- `--skip-env` — Do not modify `.env` / `.env.example`

### 3. Migrate and seed (if you skipped prompts)

```sh
php artisan migrate
php artisan db:seed --class="Vormia\\ATUMultiCurrency\\Database\\Seeders\\ATUMultiCurrencySeeder"
```

The seeder creates a default currency with rate `1.0` when none exists. If `a2_ec_settings` is present, it reads rows with `key` of `currency_code` and `currency_symbol`; otherwise it uses USD / `$`.

### 4. Optional: publish config

Config is merged from the package. To override in your app:

```sh
php artisan vendor:publish --tag=atumulticurrency-config
```

### 5. Optional: admin UI checklist

Admin routes are registered at **`/admin/atu/currencies`**. To verify optional Flux layout dependencies and optionally inject sidebar links:

```sh
php artisan atumulticurrency:ui-install
php artisan atumulticurrency:ui-install --inject-sidebar
```

After `composer update` of this package, clear caches:

```sh
php artisan atumulticurrency:ui-update
```

## Commands

| Command | Purpose |
| --- | --- |
| `atumulticurrency:install` | Env keys; prompts for migrate/seed (`--skip-env`) |
| `atumulticurrency:refresh` | Roll back and re-run package migrations; optional seed (`--force`, `--seed`) |
| `atumulticurrency:uninstall` | Remove env keys; optional migration rollback (`--keep-env`, `--force`) |
| `atumulticurrency:ui-install` | Check UI deps; optional Flux sidebar (`--inject-sidebar`) |
| `atumulticurrency:ui-update` | Clear caches after package update |
| `atumulticurrency:ui-uninstall` | Remove **legacy** copied views and marked route/sidebar snippets (`--force`) |
| `atumulticurrency:help` | Summary of commands, env, routes, seeder class |

Run `php artisan atumulticurrency:help` for the canonical, up-to-date list.

## Configuration

Merged config lives in the package at `config/atu-multi-currency.php`. After publishing, edit `config/atu-multi-currency.php` in your application.

Typical keys include `default_currency`, `api`, `conversion`, and `table_prefix`.

## Environment variables

Installed keys (when not skipped):

```env
# ATU Multi-Currency Configuration
ATU_CURRENCY_API_KEY=
ATU_CURRENCY_UPDATE_FREQUENCY=daily
ATU_CURRENCY_SETTINGS_SOURCE=database
```

- **`ATU_CURRENCY_API_KEY`** — External rates API (optional)
- **`ATU_CURRENCY_UPDATE_FREQUENCY`** — How often you refresh rates (your own scheduler logic)
- **`ATU_CURRENCY_SETTINGS_SOURCE`** — `database` (recommended) or `file`

## Database tables

All use the `atu_multicurrency_` prefix (configurable via `table_prefix`):

| Table | Role |
| --- | --- |
| `atu_multicurrency_currencies` | Supported currencies, rates, fees, default/active flags |
| `atu_multicurrency_currency_rates_log` | Historical rates |
| `atu_multicurrency_currency_conversion_log` | Immutable conversion audit |
| `atu_multicurrency_settings` | Stored conversion and display settings |

## Core principles

1. **A2 Commerce owns truth** for stored order/catalog amounts in base currency (when A2 is used).
2. **ATU is a projection layer** — convert, log, and report without silently rewriting core rows.
3. **Complexity stays in ATU tables** — rates, logs, fees, settings.
4. **Logs are append-only** — do not mutate historical conversion rows.

## Default currency and A2Commerce

When A2Commerce is present, **CurrencySyncService** keeps the default currency’s code/symbol aligned with `a2_ec_settings` using `updateOrInsert` on the `key` / `value` shape used by your A2 install.

**Operational rules** (see admin UI and services for enforcement):

- Default currency rate stays **1.0**
- Only one default currency
- Changing default is a deliberate flow (set another currency default first, where applicable)

## JSON API

Routes are registered by the package with the `api` middleware stack, prefix **`/api/atu/currency`**, and names like `api.atu.currency.index`.

Endpoints include listing currencies, current/default, switch, CRUD, toggle active, set default, settings read/update, and conversion logs. Secure them with your own middleware (Sanctum, admin gates, throttling) as needed.

## Admin UI (Livewire 4)

The service provider registers the package view location with `Livewire::addLocation` and loads **`/admin/atu/currencies`** routes (names such as `admin.atu.currencies.index`).

For manual route or layout merges, use the reference stubs under `vendor/vormia-folks/atu-multi-currency/src/stubs/reference/`.

## Documentation

| Document | Description |
| --- | --- |
| [`CHANGELOG.md`](CHANGELOG.md) | Version history (Keep a Changelog) |
| [`docs/build-guide.md`](docs/build-guide.md) | Install, database, API, admin UI (Livewire / Flux), UI contract |
| [`docs/package-creation-guide.md`](docs/package-creation-guide.md) | Template for ATU-style Laravel packages (optional) |
| [`docs/releases/v2.1.0.md`](docs/releases/v2.1.0.md) | Release notes for v2.1.0 |

[A2Commerce](https://github.com/a2-atu/a2commerce) documents the commerce core this package extends.

## Uninstallation

```sh
php artisan atumulticurrency:uninstall
composer remove vormia-folks/atu-multi-currency
```

The uninstall command can strip ATU env keys and optionally roll back this package’s migrations. It does not remove the Composer package; `composer remove` does that. Routes and admin Livewire UI unregister once the package is removed.

Use `atumulticurrency:ui-uninstall` first if you still have **legacy copied** Blade or Livewire files from older installs.

## Troubleshooting

- **Migrations not applied** — Run `php artisan migrate`. Package migrations are registered automatically.
- **Seeder skipped** — A default row already exists, or run the seeder class shown in `atumulticurrency:help`.
- **No admin pages** — Confirm `livewire/livewire` is installed (it is required by this package); run `atumulticurrency:ui-install` to verify Flux-related packages, routes, and auth middleware.
- **`a2_ec_settings` missing** — Expected without A2Commerce; seeder falls back to USD.

## Contributing

Contributions are welcome via pull request.

## License

MIT. See [opensource.org/licenses/MIT](https://opensource.org/licenses/MIT).

## Versioning

[Semantic Versioning](https://semver.org/); releases are tagged (for example `v2.1.0`).

```sh
composer require vormia-folks/atu-multi-currency:^2.1
```

```sh
git tag --list
git show v2.1.0 --no-patch
```

### Recent releases

- **v2.1.0** — [`CHANGELOG.md`](CHANGELOG.md) · [`docs/releases/v2.1.0.md`](docs/releases/v2.1.0.md)
- **v2.0.0** — See [`CHANGELOG.md`](CHANGELOG.md) (section **[2.0.0]**)

Older tags (`v1.x`, `v0.x`) may reflect the previous “copy stubs into the app” workflow; prefer **v2.x** docs for current behavior.

---

Built for the A2 Commerce ecosystem.
