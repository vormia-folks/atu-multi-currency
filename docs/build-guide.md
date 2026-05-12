# ATU Multi-Currency build guide

Installation, database behavior, JSON API, **admin UI** (Livewire 4 / Flux), and the **UI contract** for anyone extending or auditing the package.

---

## Core package

How the package is meant to be used in a Laravel application: **code ships in `vendor`**, and the app gets database tables, merged config, routes, and **Livewire 4** admin pages through Laravel’s normal package mechanisms (`livewire/livewire` is required).

### What this package is

- **Projection layer** — Commerce amounts stay in the **base/system currency** in A2 (or your app). ATU converts for display, APIs, and reporting and records conversions in its own tables.
- **Self-contained wiring** — Migrations are loaded with `loadMigrationsFrom`. API routes load from `routes/atu-multicurrency-api.php`. Config is merged from the package. The package requires **Livewire 4**; it calls `Livewire::addLocation` for its single-file views and loads web routes from `routes/atumulticurrency-web.php`.
- **Installer is thin** — `atumulticurrency:install` adds `.env` keys (unless `--skip-env`) and prompts for migrate/seed. It does **not** copy migrations, controllers, or views into your project tree.

### Requirements

- PHP 8.2+, Laravel 12 or 13, Vormia 5.x, Livewire 4.x (`livewire/livewire` is required by this package)
- Optional: Flux-related packages for the admin shell (see `composer.json` `suggest` and `atumulticurrency:help`)

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
- **Admin Livewire** — `/admin/atu/currencies`, route names `admin.atu.currencies.*`

Add authentication, authorization, and rate limiting in your app as required (for example middleware on route groups or a consuming gateway).

### Uninstall

```sh
php artisan atumulticurrency:uninstall
```

Can remove env keys and optionally roll back migrations. Remove the Composer dependency when you no longer want the package:

```sh
composer remove vormia-folks/atu-multi-currency
```

---

## Admin UI setup

From **v2.x**, Livewire admin views and `/admin/atu/currencies` routes are registered **from the package** (Livewire is a Composer dependency). You normally do **not** copy Blade or Livewire component files into `resources/views` for day-to-day use.

The `ui-*` commands help verify optional layout packages, inject a **Flux** sidebar snippet if you want it, clear caches after upgrades, and clean up **legacy** files from older installs that copied views into the app.

### Install UI (checks + optional sidebar)

```sh
php artisan atumulticurrency:ui-install
php artisan atumulticurrency:ui-install --inject-sidebar
```

What this command does:

- Verifies **`vormiaphp/ui-livewireflux-admin`** (^2.0) is installed (required for the check to pass).
- Verifies ATU migrations have been applied (`atu_multicurrency_currencies` exists).
- If **`livewire/livewire`** is missing (broken vendor), prints how to fix Composer and points at reference routes.
- If **`livewire/flux`** is installed and you pass **`--inject-sidebar`**, attempts to merge the menu snippet after the Platform nav group in `resources/views/components/layouts/app/sidebar.blade.php` (skips if markers already present).
- Clears config, route, view, and application caches.

If your layout path differs from the Flux starter, injection may fail; merge manually from the reference stub.

### Routes (normal v2 case)

The package registers routes under **`/admin/atu/currencies`** (route names like `admin.atu.currencies.index`). No `routes/web.php` edits are required.

### Manual route setup (only without the package provider or a broken Livewire install)

If you cannot rely on the package registering routes and the view location, adapt the reference file to your routing stack and register the same `Livewire::addLocation` path (see comments in the stub):

- `src/stubs/reference/routes-to-add.php`

### Manual sidebar menu setup

Reference snippet:

- `src/stubs/reference/sidebar-menu-to-add.blade.php`

### Updating after `composer update`

```sh
php artisan atumulticurrency:ui-update
```

Clears caches; UI templates live in `vendor`, so pulling a newer package version is enough for view changes.

### Uninstall legacy copied UI (host files only)

```sh
php artisan atumulticurrency:ui-uninstall
```

Removes **legacy** copied views under `resources/views/livewire/admin/atu` and marked snippets in `routes/web.php` / sidebar if present from older workflows. Package routes still apply until you `composer remove` the package.

### What the shipped administration screens cover

The installed admin screens are intended to cover:

- **Currencies list**: active/default status, rate visibility, actions
- **Create/Edit currency**: code/symbol/name, rate, fee, auto/manual flags
- **Settings**: conversion settings (precision, fees, logging, and settings source)
- **Logs**: view conversion log entries

---

## UI specification (controls and admin settings)

This section describes **UI-level controls** required to manage the `atu-multi-currency` package. It guides admin panel implementation, UX decisions, and validation rules. The UI layer **configures behavior** but does not own conversion logic.

### 1. System awareness (base currency)

#### Read-only system currency

Source (typical A2Commerce shape: `key` / `value` rows):

- Row with `key` = `currency_code` in `a2_ec_settings`

UI behavior:

- Display prominently as **System Base Currency**
- Mark with `*` wherever shown
- Must be **read-only** in ATU UI

Example:

> System Currency: **KES\*** (Managed by A2 Commerce)

Rules:

- ATU cannot change this value
- ATU must validate that its default currency matches this

### 2. Currency management screen

#### 2.1 Currency list view

Display a table with:

| Column  | Description                      |
| ------- | -------------------------------- |
| Code    | ISO 4217 (USD, EUR, GBP)         |
| Symbol  | $, €, £                          |
| Rate    | Conversion against base currency |
| Mode    | Auto / Manual                    |
| Fee     | Currency-specific fee            |
| Country | Optional taxonomy                |
| Status  | Active / Inactive                |
| Default | `*` indicator                    |

Actions:

- Add Currency
- Edit Currency
- Activate / Deactivate

Restrictions:

- Default currency cannot be deactivated

#### 2.2 Add / edit currency form

##### Fields

**Currency Code**

- ISO 4217 (3 characters)
- Required
- Uppercase enforced
- Unique

**Currency Symbol**

- Short display symbol
- Required

**Conversion Rate**

- Numeric
- Meaning:

  > `1 [Currency Code] = X [System Base Currency]`

**Rate Mode**

- Auto
- Manual

If **Auto**:

- Rate field becomes read-only
- Rate sourced from exchangeratesapi

If **Manual**:

- Admin enters rate

**Additional Fee**

- Optional
- Fixed amount
- Applied at checkout

**Country (Optional)**

- Select from `vrm_taxonomies`
- Used for geo-based currency selection

**Status**

- Active / Inactive

### 3. Default currency rules

#### Default currency selector

UI control:

- Radio button or locked toggle

Rules:

- Only one currency can be default
- Default currency code **must equal** the `currency_code` value stored in `a2_ec_settings`
- UI must prevent mismatch

Display:

- Default currency marked with `*`

### 4. Global currency settings

#### 4.1 Currency visibility rules

Control:

- Dropdown / multi-select

Options:

- Show all active currencies
- Select specific currencies
- Auto-pick by country

Behavior:

- Affects frontend selectors only
- Does not affect internal calculations

#### 4.2 Currency order

Control:

- Dropdown

Options:

- Ascending (A–Z)
- Descending (Z–A)
- Default first

#### 4.3 Exchange rate API settings

##### API key input

- exchangeratesapi API key
- Encrypted storage

##### Rate source preference

Options:

- Manual only
- Auto only
- Prefer auto, fallback to manual

Validation:

- Auto cannot be enabled without API key

#### 4.4 Fee strategy

Control:

- Radio buttons

Options:

- No fees
- Use per-currency fee
- Use global fee

If **Global Fee**:

- Show global fee input

Rules:

- Currency fee overrides global fee only if selected

### 5. Product-level UI integration

#### Product create / edit screen

##### Price input enhancements

Fields:

- Sale Price
- Acquiring Price

Additional controls (injected by ATU):

- Price currency selector

Behavior:

- Admin selects input currency
- ATU converts to base currency on save

Hidden (system-managed):

- Rate used
- Rate source

### 6. Frontend currency selector

#### User-facing control

Options:

- Dropdown
- Modal
- Auto-detected by country

Rules:

- Affects display only
- Does not change stored values

Persistence:

- Session-based
- Optional user profile binding

### 7. Checkout UI behavior

Display:

- Order total in selected currency
- Fee breakdown (if applied)

Notes:

- Base currency may be shown in small text
- Payment gateway currency remains system-defined

### 8. Reporting UI controls

Filters:

- Currency to view report in
- Date range

Behavior:

- Conversion happens at report time
- Conversion log written with context `report`

### 9. UX guardrails (important)

UI must:

- Never allow editing base currency inside ATU
- Never allow deleting default currency
- Clearly distinguish **display currency** vs **system currency**

Warnings to show:

- Manual rate overrides
- Fee application impact

### 10. Summary

The UI layer:

- Configures currencies
- Controls visibility and behavior
- Delegates all logic to ATU services

No UI action should:

- Mutate stored base prices directly
- Bypass conversion logging
- Override A2 Commerce rules

---

## Further reading

- **Changelog** — `CHANGELOG.md` (repository root)
- **Release notes** — `docs/releases/v2.1.0.md` (current minor)
- **Package authoring template** — `docs/package-creation-guide.md` (if present in your tree)
