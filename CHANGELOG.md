# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **Admin UI** — Livewire 4 single-file components (`Livewire\Component`), `Route::livewire()`, and `Livewire::addLocation()` for package view discovery; **`livewire/livewire` ^4** is required.
- **Composer** — Require **`livewire/livewire` ^4.0** (admin UI); removed from `suggest`.
- Merged **`docs/build-ui-guide.md`** into **`docs/build-guide.md`** as one document (core install + admin UI + UI contract). Removed **`docs/build-ui-guide.md`**.

## [2.1.0] - 2026-05-11

Narrative upgrade guide: [`docs/releases/v2.1.0.md`](docs/releases/v2.1.0.md).

### Added

- `ATUMultiCurrency::packageRoot()`, `migrationsPath()`, and `migrationsPathRelativeToBase()` for stable paths to package assets and Artisan `--path` usage during refresh.
- Service provider registers **migrations** from the package (`loadMigrationsFrom`), **merged config** from the package, **API routes** (`routes/atu-multicurrency-api.php`), and **admin web routes** with package-hosted Livewire views when the admin UI stack is installed.
- Config publish tag **`atumulticurrency-config`**: `php artisan vendor:publish --tag=atumulticurrency-config`.
- Root **`CHANGELOG.md`** (this file).

### Changed

- **Install / uninstall model** — `atumulticurrency:install` focuses on optional `.env` keys and migrate/seed prompts; it does **not** copy migrations, controllers, or views into the host app. `Support\Installer` only manages environment keys (constructor and behavior differ from v2.0.0).
- **`atumulticurrency:help`** — Clearer, shorter summary of commands, env, HTTP entry points, seeder class, and publish tag.
- **`atumulticurrency:ui-install`** — Validates **`vormiaphp/ui-livewireflux-admin`**; improved messages when Livewire or Flux is missing; optional **`--inject-sidebar`** behavior unchanged in intent but with clearer guidance.
- **`ATUMultiCurrencySeeder`** — Centralized `info` / `warn` helpers (command output when available, plus `Log`).

### Documentation

- **`README.md`** and **`docs/build-guide.md`** updated for the v2.1 vendor-loaded architecture (build guide now includes the former UI guide).

### Version constant

- `ATUMultiCurrency::VERSION` and `app('atumulticurrency.version')` set to **2.1.0**.

## [2.0.0] - 2026-04-09

### Changed

- **Composer** — Require **`vormiaphp/vormia` ^5.0**; expanded `suggest` for optional Livewire and Flux-related packages.
- **Admin Livewire UI** — Currency flows use the **Vormia Taxonomy** model where applicable; Select2 initialization adjustments for reliability.
- **Documentation** — README and UI build guide updates; clearer **API route** documentation; `.gitignore` keeps `example-package/` tracked as intended for this repo.

### Removed

- Obsolete migration stub that duplicated the four-character currency code migration path (consolidated elsewhere).

## Earlier releases

Versions **v1.x** and **v0.x** are tagged in Git. For per-tag commit ranges:

```sh
git tag --sort=-version:refname
git log v1.0.2..v2.0.0 --oneline
```

[Unreleased]: https://github.com/vormia-folks/atu-multi-currency/compare/v2.1.0...HEAD
[2.1.0]: https://github.com/vormia-folks/atu-multi-currency/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/vormia-folks/atu-multi-currency/compare/v1.1.1...v2.0.0
