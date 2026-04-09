# Release Notes - v0.1.1

## Overview

This release introduces enhanced currency code support, optional currency names, and smart fallback logic between currency codes and symbols. These improvements make the package more flexible for international currencies, especially those with 4-character codes like ZAR (South African Rand).

## 🚀 New Features

### Enhanced Currency Code Support
- Support for 4-character currency codes (e.g., ZAR for South African Rand)
- Updated validation to accept 3-4 character currency codes (ISO 4217 compatible)
- Backward compatible with existing 3-character codes (USD, EUR, GBP, etc.)

### Currency Name Field
- Added optional `name` field to store full descriptive currency names
- Examples: "South African Rand", "United States Dollar", "Kenyan Shilling"
- Improves user experience and display clarity

### Smart Fallback Logic
- Automatic fallback between currency code and symbol
- If currency code is empty, the symbol will be used as the code automatically
- If currency symbol is empty, the code will be used as the symbol automatically
- At least one of code or symbol must be provided
- Simplifies currency entry in the admin interface

## ✨ Improvements

### Database Schema
- Updated `atu_multicurrency_currencies.code` from `char(3)` to `char(4)`
- Added `name` column (nullable string) to `atu_multicurrency_currencies` table
- Updated `atu_multicurrency_currency_conversion_log` table to support 4-character codes
- Created migration for backward compatibility with existing installations

### Form Validation
- Enhanced validation rules for currency codes (3-4 characters)
- Improved error messages and user guidance
- Better handling of empty code/symbol scenarios

### User Interface
- Updated create and edit forms to include currency name field
- Improved field descriptions and help text
- Updated input maxlength attributes for 4-character codes
- Clearer instructions about fallback behavior

## 🔧 Technical Changes

### Migrations
- Updated the initial create-table migrations to support 4-character currency codes and the optional currency `name` field (fresh installs only).

### Form Components
- Updated `create.blade.php` with name field and fallback logic
- Updated `edit.blade.php` with name field and fallback logic
- Enhanced validation in both create and update methods
- Automatic fallback logic implemented in save/update methods

### Database Updates
- Modified main currencies table migration
- Modified conversion log table migration
- All changes are backward compatible

## 📝 Documentation Updates

- Updated README.md with new features documentation
- Added examples showing currency name usage
- Documented fallback behavior
- Updated database schema documentation

## 🐛 Bug Fixes

- None in this release

## 📦 Installation

To install v0.1.1:

```bash
composer require vormia-folks/atu-multi-currency:^0.1.1
php artisan atumulticurrency:install
php artisan migrate
```

## 🔄 Migration from v0.1.0

### For New Installations
- No changes required - new migrations include all updates

### For Existing Installations
1. Update the package: `composer require vormia-folks/atu-multi-currency:^0.1.1`
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
    'code' => 'ZAR',  // 4-character code supported
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

## 🙏 Thank You

Thank you for using ATU Multi-Currency! If you encounter any issues or have suggestions, please open an issue on the repository.

---

**Release Date:** 2025-01-15  
**Version:** 0.1.1  
**Previous Version:** 0.1.0
