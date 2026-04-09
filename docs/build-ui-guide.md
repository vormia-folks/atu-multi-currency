## UI build guide (admin pages)

ATU Multi-Currency ships optional admin UI assets (Volt/Livewire views + route/sidebar helpers) that you can install into your app.

### Install UI

```sh
php artisan atumulticurrency:ui-install
```

What it attempts to do:

- Copy admin views into `resources/views/livewire/admin/atu/`
- Inject routes into `routes/web.php` (best-effort)
- Inject sidebar menu items into `resources/views/components/layouts/app/sidebar.blade.php` (best-effort)
- Clear application caches

If your project’s file layout differs from the default Vormia starter, automatic injection can fail. Use the manual steps below.

### Manual route setup (recommended to verify)

Add the routes in `routes/web.php` inside your authenticated admin group (example below). If your app doesn’t use Volt, adapt these routes to your routing style.

```php
use Livewire\Volt\Volt;

Route::prefix('admin/atu/currencies')->name('admin.atu.currencies.')->group(function () {
    Volt::route('/', 'admin.atu.currencies.index')->name('index');
    Volt::route('create', 'admin.atu.currencies.create')->name('create');
    Volt::route('edit/{id}', 'admin.atu.currencies.edit')->name('edit');
    Volt::route('settings', 'admin.atu.currencies.settings')->name('settings');
    Volt::route('logs', 'admin.atu.currencies.logs')->name('logs');
});
```

### Manual sidebar menu setup

If sidebar injection fails, add the menu items into your sidebar view in the appropriate place for your layout.

You can use the reference snippet shipped with the package:

- `src/stubs/reference/sidebar-menu-to-add.blade.php`

### Updating UI assets

If you installed UI earlier and want to re-copy/re-inject assets:

```sh
php artisan atumulticurrency:ui-update
```

### Uninstall UI assets

```sh
php artisan atumulticurrency:ui-uninstall
```

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

Source:

- `a2_ec_settings.currency_code`

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
- Default currency code **must equal** `a2_ec_settings.currency_code`
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
