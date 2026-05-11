## UI build guide (admin pages)

From **v2.x**, Volt admin views and `/admin/atu/currencies` routes are registered **from the package** when `livewire/volt` is installed. You normally do **not** copy Blade or Volt files into `resources/views` for day-to-day use.

The `ui-*` commands help verify optional layout packages, inject a **Flux** sidebar snippet if you want it, clear caches after upgrades, and clean up **legacy** files from older installs that copied views into the app.

### Install UI (checks + optional sidebar)

```sh
php artisan atumulticurrency:ui-install
php artisan atumulticurrency:ui-install --inject-sidebar
```

What this command does:

- Verifies **`vormiaphp/ui-livewireflux-admin`** (^2.0) is installed (required for the check to pass).
- Verifies ATU migrations have been applied (`atu_multicurrency_currencies` exists).
- If **`livewire/volt`** is missing, prints how to install it and points at reference routes.
- If **`livewire/flux`** is installed and you pass **`--inject-sidebar`**, attempts to merge the menu snippet after the Platform nav group in `resources/views/components/layouts/app/sidebar.blade.php` (skips if markers already present).
- Clears config, route, view, and application caches.

If your layout path differs from the Flux starter, injection may fail; merge manually from the reference stub.

### Routes (normal v2 case)

With Volt installed, the package registers routes under **`/admin/atu/currencies`** (route names like `admin.atu.currencies.index`). No `routes/web.php` edits are required.

### Manual route setup (only without Volt)

If you cannot use Volt, adapt the reference file to your routing stack:

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

### What the UI screens cover

The installed admin screens are intended to cover:

- **Currencies list**: active/default status, rate visibility, actions
- **Create/Edit currency**: code/symbol/name, rate, fee, auto/manual flags
- **Settings**: conversion settings (precision, fees, logging, and settings source)
- **Logs**: view conversion log entries

# ATU Multi-Currency – UI Controls & Admin Settings

This document describes **all UI-level controls** required to manage the `atu-multi-currency` package.

It is written to guide:

- Admin panel implementation
- UX decisions
- Validation rules

The UI layer **configures behavior** but does not own conversion logic.

---

## 1. System Awareness (Base Currency)

### Read-only System Currency

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

---

## 2. Currency Management Screen

### 2.1 Currency List View

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

---

### 2.2 Add / Edit Currency Form

#### Fields

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

---

## 3. Default Currency Rules

### Default Currency Selector

UI Control:

- Radio button or locked toggle

Rules:

- Only one currency can be default
- Default currency code **must equal** the `currency_code` value stored in `a2_ec_settings`
- UI must prevent mismatch

Display:

- Default currency marked with `*`

---

## 4. Global Currency Settings

### 4.1 Currency Visibility Rules

Control:

- Dropdown / multi-select

Options:

- Show all active currencies
- Select specific currencies
- Auto-pick by country

Behavior:

- Affects frontend selectors only
- Does not affect internal calculations

---

### 4.2 Currency Order

Control:

- Dropdown

Options:

- Ascending (A–Z)
- Descending (Z–A)
- Default first

---

### 4.3 Exchange Rate API Settings

#### API Key Input

- exchangeratesapi API key
- Encrypted storage

#### Rate Source Preference

Options:

- Manual only
- Auto only
- Prefer auto, fallback to manual

Validation:

- Auto cannot be enabled without API key

---

### 4.4 Fee Strategy

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

---

## 5. Product-Level UI Integration

### Product Create / Edit Screen

#### Price Input Enhancements

Fields:

- Sale Price
- Acquiring Price

Additional Controls (Injected by ATU):

- Price Currency Selector

Behavior:

- Admin selects input currency
- ATU converts to base currency on save

Hidden (System-managed):

- Rate used
- Rate source

---

## 6. Frontend Currency Selector

### User-Facing Control

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

---

## 7. Checkout UI Behavior

Display:

- Order total in selected currency
- Fee breakdown (if applied)

Notes:

- Base currency may be shown in small text
- Payment gateway currency remains system-defined

---

## 8. Reporting UI Controls

Filters:

- Currency to view report in
- Date range

Behavior:

- Conversion happens at report time
- Conversion log written with context `report`

---

## 9. UX Guardrails (Important)

UI must:

- Never allow editing base currency inside ATU
- Never allow deleting default currency
- Clearly distinguish **display currency** vs **system currency**

Warnings to show:

- Manual rate overrides
- Fee application impact

---

## 10. Summary

The UI layer:

- Configures currencies
- Controls visibility and behavior
- Delegates all logic to ATU services

No UI action should:

- Mutate stored base prices directly
- Bypass conversion logging
- Override A2 Commerce rules

---

**This document defines the UI contract for ATU Multi-Currency and should be used alongside the Build Guide.**
