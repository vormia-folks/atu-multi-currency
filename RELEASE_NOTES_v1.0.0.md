# Release Notes - v1.0.0

## Overview

This is the official v1.0.0 release of ATU Multi-Currency, marking the first stable version of the package. This release includes comprehensive currency management features, enhanced currency code support (3-4 characters), optional currency names, smart fallback logic, UI components, and full integration with A2Commerce.

## 🚀 New Features

### Core Currency Management
- Complete currency management system with create, edit, and list functionality
- Support for multiple currencies with automatic or manual rate management
- Default currency synchronization with A2Commerce settings
- Active/inactive currency status management
- Country taxonomy association for currencies

### Enhanced Currency Code Support
- Support for 4-character currency codes (e.g., ZAR for South African Rand)
- Updated validation to accept 3-4 character currency codes (ISO 4217 compatible)
- Backward compatible with existing 3-character codes (USD, EUR, GBP, etc.)
- Flexible code validation with automatic fallback

### Currency Name Field
- Added optional `name` field to store full descriptive currency names
- Examples: "South African Rand", "United States Dollar", "Kenyan Shilling"
- Improves user experience and display clarity in admin interface

### Smart Fallback Logic
- Automatic fallback between currency code and symbol
- If currency code is empty, the symbol will be used as the code automatically
- If currency symbol is empty, the code will be used as the symbol automatically
- At least one of code or symbol must be provided
- Simplifies currency entry in the admin interface

### Currency Synchronization Service
- Automatic synchronization of default currency with A2Commerce settings
- Bidirectional sync between `atu_multicurrency_currencies` and `a2_ec_settings`
- Prevents infinite sync loops with built-in protection
- Seamless integration with A2Commerce base currency

### UI Components
- Complete admin interface for currency management
- Livewire Volt components for create, edit, list, settings, and logs
- Responsive design with dark mode support
- Search and filter functionality for currencies
- Currency logs viewer with detailed conversion history

### Conversion Logging
- Immutable audit trail of all currency conversions
- Tracks entity type, context, amounts, rates, and fees
- User tracking for conversion events
- Historical conversion data for reporting

### Rate History
- Track historical exchange rates for accurate reporting
- Manual and API-managed rate sources
- Rate change history with timestamps

### Fee Configuration
- Optional per-currency conversion fees
- Fee application on checkout
- Configurable fee amounts per currency

## ✨ Improvements

### Database Schema
- Comprehensive migration system for all currency tables
- Updated `atu_multicurrency_currencies.code` from `char(3)` to `char(4)`
- Added `name` column (nullable string) to `atu_multicurrency_currencies` table
- Updated `atu_multicurrency_currency_conversion_log` table to support 4-character codes
- Created migration for backward compatibility with existing installations
- Proper indexing for performance optimization

### Form Validation
- Enhanced validation rules for currency codes (3-4 characters)
- Improved error messages and user guidance
- Better handling of empty code/symbol scenarios
- Custom validation for code uniqueness
- Rate validation with precision support

### User Interface
- Updated create and edit forms to include currency name field
- Improved field descriptions and help text
- Updated input maxlength attributes for 4-character codes
- Clearer instructions about fallback behavior
- Better visual feedback and notifications
- Country selection with Select2 integration

### Command Line Tools
- `atumulticurrency:install` - Complete package installation
- `atumulticurrency:ui-install` - UI components installation
- `atumulticurrency:refresh` - Refresh migrations and seeders
- `atumulticurrency:uninstall` - Complete package removal
- `atumulticurrency:help` - Help and usage information

### Documentation
- Comprehensive README with installation instructions
- Detailed database schema documentation
- Usage examples and code snippets
- Troubleshooting guide
- Manual setup instructions for routes and sidebar

## 🔧 Technical Changes

### Migrations
- **Main Migration:** `2025_01_15_000001_create_atu_multicurrency_currencies_table.php`
  - Creates currencies table with all required fields
  - Supports 4-character codes and optional name field
  
- **Rates Log Migration:** `2025_01_15_000002_create_atu_multicurrency_currency_rates_log_table.php`
  - Tracks historical exchange rates
  
- **Conversion Log Migration:** `2025_01_15_000003_create_atu_multicurrency_currency_conversion_log_table.php`
  - Immutable audit trail for conversions
  - Supports 4-character currency codes
  
- **Settings Migration:** `2025_01_15_000004_create_atu_multicurrency_settings_table.php`
  - Package settings storage
  
- **Alter Migration:** `2025_01_15_000005_alter_atu_multicurrency_tables_for_4char_codes.php`
  - Alters existing tables to support 4-character codes
  - Adds `name` column if it doesn't exist
  - Safe to run on existing installations

### Service Classes
- **CurrencySyncService** - Handles synchronization with A2Commerce
- **SettingsManager** - Manages package settings
- **Installer** - Handles package installation and file copying

### Form Components
- Updated `create.blade.php` with name field and fallback logic
- Updated `edit.blade.php` with name field and fallback logic
- Enhanced validation in both create and update methods
- Automatic fallback logic implemented in save/update methods
- Improved error handling and user feedback

### Database Updates
- Modified main currencies table migration
- Modified conversion log table migration
- All changes are backward compatible

## 📝 Documentation Updates

- Complete README.md with all features documented
- Added examples showing currency name usage
- Documented fallback behavior
- Updated database schema documentation
- Installation and setup guides
- Troubleshooting section
- Release notes for previous versions

## 🐛 Bug Fixes

- Fixed route removal logic in uninstall command
- Improved error handling in currency sync service
- Enhanced validation messages for better user experience
- Fixed whitespace and formatting issues in views

## 📦 Installation

To install v1.0.0:

```bash
composer require vormia-folks/atu-multi-currency:^1.0
php artisan atumulticurrency:install
php artisan migrate
php artisan db:seed --class=ATUMultiCurrencySeeder
```

For UI components:

```bash
php artisan atumulticurrency:ui-install
```

## 🔄 Migration from Previous Versions

### For New Installations
- No changes required - new migrations include all updates
- Follow the installation steps above

### For Existing Installations (v0.2.1 and earlier)
1. Update the package: `composer require vormia-folks/atu-multi-currency:^1.0`
2. Run migrations: `php artisan migrate`
3. The new migration will automatically:
   - Alter `code` column from `char(3)` to `char(4)`
   - Add `name` column if it doesn't exist
   - Update conversion log tables to support 4-character codes

**No breaking changes** - This is a fully backward-compatible release. Existing 3-character currency codes will continue to work without any modifications.

## 📚 Usage Examples

### Adding a Currency with 4-Character Code

```php
DB::table('atu_multicurrency_currencies')->insert([
    'code' => 'ZAR',  // 3-4 character code supported
    'symbol' => 'R',
    'name' => 'South African Rand',  // Optional descriptive name
    'rate' => '18.50000000',
    'is_auto' => true,
    'fee' => null,
    'is_default' => false,
    'is_active' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### Using Fallback Logic

If you only provide a code, the symbol will automatically use the code:
- Code: `ZAR` → Symbol: `ZAR` (if symbol is empty)

If you only provide a symbol, the code will automatically use the symbol:
- Symbol: `R` → Code: `R` (if code is empty)

### Default Currency Synchronization

The package automatically synchronizes the default currency with A2Commerce:
- When you update the default currency's code or symbol, it updates `a2_ec_settings`
- The default currency rate is always locked at 1.0
- Only one currency can be set as default at any time

## 🎯 Key Features Summary

- ✅ Multi-currency support with rate management
- ✅ 3-4 character currency code support (ISO 4217)
- ✅ Optional currency names for better UX
- ✅ Smart fallback between code and symbol
- ✅ A2Commerce integration and synchronization
- ✅ Complete admin UI with Livewire Volt
- ✅ Conversion logging and audit trail
- ✅ Rate history tracking
- ✅ Fee configuration per currency
- ✅ Country taxonomy association
- ✅ Comprehensive command-line tools
- ✅ Full documentation and examples

## 🙏 Thank You

Thank you for using ATU Multi-Currency! This v1.0.0 release represents a stable, production-ready package with all core features implemented. If you encounter any issues or have suggestions, please open an issue on the repository.

---

**Release Date:** 2026-01-09  
**Version:** 1.0.0  
**Previous Version:** 0.2.1  
**Status:** Stable Release
