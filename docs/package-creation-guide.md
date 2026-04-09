# Laravel Package Creation Guide

This guide walks you through creating a Laravel package following the ATU package style and conventions. Use this guide as a template when creating new packages.

## Table of Contents

1. [Package Structure](#package-structure)
2. [Composer Configuration](#composer-configuration)
3. [Main Package Class](#main-package-class)
4. [Service Provider](#service-provider)
5. [Installer Class](#installer-class)
6. [Console Commands](#console-commands)
7. [Stubs Organization](#stubs-organization)
8. [Support Classes](#support-classes)
9. [Testing Setup](#testing-setup)
10. [Best Practices](#best-practices)

---

## Package Structure

Create the following directory structure for your package:

```
your-package-name/
├── composer.json
├── composer.lock
├── README.md
├── CHANGELOG.md
├── phpunit.xml.dist
├── src/
│   ├── YourPackageName.php              # Main package class
│   ├── YourPackageNameServiceProvider.php
│   ├── Console/
│   │   └── Commands/
│   │       ├── YourPackageNameInstallCommand.php
│   │       ├── YourPackageNameUninstallCommand.php
│   │       ├── YourPackageNameUpdateCommand.php
│   │       └── YourPackageNameHelpCommand.php
│   ├── Providers/                        # Optional: Event service providers
│   ├── Support/
│   │   ├── Installer.php                 # Handles file copying and setup
│   │   └── [OtherSupportClasses].php
│   └── stubs/                            # Files to copy to application
│       ├── config/
│       ├── migrations/
│       ├── database/
│       │   └── seeders/
│       ├── app/                           # Controllers, Models, etc.
│       ├── resources/
│       └── reference/                     # Reference files for manual setup
└── tests/
    └── ExampleTest.php
```

---

## Composer Configuration

### Basic `composer.json` Template

```json
{
    "name": "vendor/package-name",
    "description": "Package description - A package for Laravel that provides [functionality].",
    "keywords": ["laravel", "php", "your-keywords"],
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.2"
    },
    "autoload": {
        "psr-4": {
            "Vendor\\PackageName\\": "src/"
        },
        "exclude-from-classmap": [
            "src/stubs/"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "Vendor\\PackageName\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Vendor\\PackageName\\YourPackageNameServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

### Key Points:

- **Name**: Use kebab-case (e.g., `vendor/package-name`)
- **Namespace**: Use PascalCase (e.g., `Vendor\PackageName`)
- **Exclude stubs**: Always exclude `src/stubs/` from classmap
- **Laravel Discovery**: Register service provider in `extra.laravel.providers`
- **No version field**: Don't add `"version"` field in composer.json (relies on git tags)

---

## Main Package Class

Create a main class that serves as the package entry point:

```php
<?php

namespace Vendor\PackageName;

class YourPackageName
{
    public const VERSION = '0.1.0';

    /**
     * Absolute path to the package stubs.
     */
    public static function stubsPath(string $suffix = ''): string
    {
        $base = __DIR__ . '/stubs';

        return $suffix ? $base . '/' . ltrim($suffix, '/') : $base;
    }
}
```

### Key Points:

- **VERSION constant**: Always include version constant
- **stubsPath() method**: Provides path to stubs directory
- **Simple and focused**: Keep this class minimal

---

## Service Provider

The service provider registers services and commands:

```php
<?php

namespace Vendor\PackageName;

use Vendor\PackageName\YourPackageName;
use Vendor\PackageName\Console\Commands\YourPackageNameInstallCommand;
use Vendor\PackageName\Console\Commands\YourPackageNameUninstallCommand;
use Vendor\PackageName\Console\Commands\YourPackageNameUpdateCommand;
use Vendor\PackageName\Console\Commands\YourPackageNameHelpCommand;
use Vendor\PackageName\Support\Installer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class YourPackageNameServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register version instance
        $this->app->instance('yourpackagename.version', YourPackageName::VERSION);

        // Register Installer as singleton
        $this->app->singleton(Installer::class, function (Application $app) {
            return new Installer(
                new Filesystem(),
                YourPackageName::stubsPath(),
                $app->basePath()
            );
        });

        // Register other services as singletons
        // $this->app->singleton(YourService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                YourPackageNameInstallCommand::class,
                YourPackageNameUpdateCommand::class,
                YourPackageNameUninstallCommand::class,
                YourPackageNameHelpCommand::class,
            ]);
        }
    }
}
```

### Key Points:

- **Version instance**: Register version as app instance
- **Installer singleton**: Always register Installer with Filesystem, stubs path, and base path
- **Console commands**: Only register commands when running in console
- **Service registration**: Register services in `register()`, not `boot()`

---

## Installer Class

The `Installer` class handles copying files, managing environment variables, and route injection:

```php
<?php

namespace Vendor\PackageName\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Installer
{
    // Define environment keys to add/remove
    private const ENV_KEYS = [
        'YOUR_PACKAGE_API_KEY' => '',
        'YOUR_PACKAGE_SETTING' => 'default',
    ];

    // Route markers for injection
    private const ROUTE_MARK_START = '// >>> Your Package Routes START';
    private const ROUTE_MARK_END = '// >>> Your Package Routes END';
    private const ROUTE_BLOCK = <<<'PHP'
// >>> Your Package Routes START
// Route::prefix('your-package')->group(function () {
//     Route::get('/', [\App\Http\Controllers\YourPackage\YourController::class, 'index']);
// });
// >>> Your Package Routes END
PHP;

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $stubsPath,
        private readonly string $appBasePath
    ) {}

    /**
     * Install fresh assets and env keys.
     *
     * @return array{copied: array, env: array, routes: array}
     */
    public function install(bool $overwrite = true, bool $touchEnv = true): array
    {
        $copied = $this->copyStubs($overwrite);
        $envChanges = $touchEnv ? $this->ensureEnvKeys() : [];
        $routes = $this->ensureRoutes();

        return ['copied' => $copied, 'env' => $envChanges, 'routes' => $routes];
    }

    /**
     * Update simply re-runs install with overwrite.
     */
    public function update(bool $touchEnv = true): array
    {
        return $this->install(true, $touchEnv);
    }

    /**
     * Remove copied assets and env keys.
     *
     * @return array{removed: array, env: array, routes: array}
     */
    public function uninstall(bool $touchEnv = true): array
    {
        $removed = $this->removeStubTargets();
        $env = $touchEnv ? $this->removeEnvKeys() : [];
        $routes = $this->removeRoutes();

        return ['removed' => $removed, 'env' => $env, 'routes' => $routes];
    }

    // ... (see full implementation in ATUMultiCurrency/Support/Installer.php)
}
```

### Key Methods to Implement:

1. **`copyStubs()`**: Copy files from stubs to application directories
2. **`removeStubTargets()`**: Remove copied files (except migrations)
3. **`ensureEnvKeys()`**: Add environment variables to `.env` files
4. **`removeEnvKeys()`**: Remove environment variables
5. **`ensureRoutes()`**: Inject routes into route files
6. **`removeRoutes()`**: Remove injected routes

### Stub Directory Mapping:

The installer maps stub directories to application directories:

- `stubs/config/` → `config/`
- `stubs/migrations/` → `database/migrations/`
- `stubs/database/` → `database/`
- `stubs/app/` → `app/`
- `stubs/controllers/` → `app/Http/Controllers/`
- `stubs/models/` → `app/Models/`
- `stubs/services/` → `app/Services/`
- `stubs/resources/` → `resources/`

### Path Handling:

- Use `targetPath()` to map stub paths to application paths
- Use `appPathWithPrefix()` for app directory files
- Normalize paths with `pathJoin()` helper
- Convert directory names to StudlyCase for app files

---

## Console Commands

### Install Command

```php
<?php

namespace Vendor\PackageName\Console\Commands;

use Vendor\PackageName\YourPackageName;
use Vendor\PackageName\Support\Installer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class YourPackageNameInstallCommand extends Command
{
    protected $signature = 'yourpackagename:install 
                            {--skip-env : Do not modify .env files} 
                            {--no-overwrite : Skip existing files instead of replacing}';

    protected $description = 'Install Your Package Name with all necessary files and configurations';

    public function handle(Installer $installer): int
    {
        $this->displayHeader();
        
        $overwrite = !$this->option('no-overwrite');
        $touchEnv = !$this->option('skip-env');

        // Copy files
        $this->step('Copying package files and stubs...');
        $results = $installer->install($overwrite, false);
        $this->displayCopyResults($results['copied']);

        // Environment variables
        $this->step('Updating environment files...');
        if ($touchEnv) {
            $this->updateEnvFiles();
        } else {
            $this->line('   ⏭️  Environment keys skipped (--skip-env flag used).');
        }

        // Routes
        $this->step('Ensuring routes...');
        $this->handleRoutes($results['routes'] ?? []);

        // Migrations
        $migrationsRun = $this->handleMigrations();

        // Seeders
        if ($migrationsRun) {
            $this->handleSeeders();
        }

        $this->displayCompletionMessage($touchEnv, $migrationsRun);

        return self::SUCCESS;
    }

    // Helper methods...
}
```

### Standard Commands:

1. **Install Command** (`yourpackagename:install`)
   - Options: `--skip-env`, `--no-overwrite`
   - Copies files, updates env, adds routes, runs migrations/seeders

2. **Uninstall Command** (`yourpackagename:uninstall`)
   - Options: `--keep-env`, `--force`
   - Removes files, optionally removes env, removes routes

3. **Update Command** (`yourpackagename:update`)
   - Re-runs install with overwrite
   - Updates files and configurations

4. **Help Command** (`yourpackagename:help`)
   - Displays usage information and examples

### Command Naming Convention:

- Use kebab-case: `yourpackagename:install`
- Match package name format
- Keep commands descriptive and consistent

---

## Stubs Organization

Organize stubs to mirror the application structure:

```
src/stubs/
├── config/
│   └── your-package.php
├── migrations/
│   └── YYYY_MM_DD_HHMMSS_create_your_table.php
├── database/
│   └── seeders/
│       └── YourPackageSeeder.php
├── app/
│   └── Http/
│       └── Controllers/
│           └── YourPackage/
│               └── YourController.php
├── resources/
│   └── views/
│       └── your-package/
│           └── index.blade.php
└── reference/
    ├── routes-to-add.php
    └── sidebar-menu-to-add.blade.php
```

### Stub File Guidelines:

- **Config files**: Place in `stubs/config/`
- **Migrations**: Use timestamp format `YYYY_MM_DD_HHMMSS_description.php`
- **Seeders**: Place in `stubs/database/seeders/`
- **Controllers**: Use namespace structure matching app structure
- **Views**: Mirror resources directory structure
- **Reference files**: Provide examples for manual setup

---

## Support Classes

Create support classes for package-specific functionality:

```php
<?php

namespace Vendor\PackageName\Support;

class YourService
{
    // Package-specific logic
}
```

### Common Support Classes:

- **Installer**: File copying and setup
- **SettingsManager**: Configuration management
- **SyncService**: Data synchronization (if needed)
- **Helper classes**: Utility functions

---

## Testing Setup

### `phpunit.xml.dist` Template

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         processIsolation="false"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
        <exclude>
            <directory>src/stubs</directory>
        </exclude>
    </source>
</phpunit>
```

### Test Structure:

```php
<?php

namespace Vendor\PackageName\Tests;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_example(): void
    {
        $this->assertTrue(true);
    }
}
```

---

## Best Practices

### 1. Naming Conventions

- **Package name**: `vendor/package-name` (kebab-case)
- **Namespace**: `Vendor\PackageName` (PascalCase)
- **Class names**: PascalCase
- **Command signatures**: `packagename:action` (lowercase)
- **Config keys**: `package-name.key` (kebab-case)

### 2. Version Management

- Use git tags for versioning
- Don't include `version` field in `composer.json`
- Store version as constant in main package class

### 3. File Organization

- Keep stubs organized by destination
- Use reference files for manual setup instructions
- Exclude stubs from autoload classmap

### 4. Environment Variables

- Always provide defaults
- Group related variables with comments
- Support both `.env` and `.env.example`
- Allow skipping env modification with `--skip-env`

### 5. Route Injection

- Use markers for route injection
- Support both automatic and manual setup
- Provide reference files for manual setup
- Don't overwrite existing routes

### 6. Migration Handling

- Never delete migrations during uninstall
- Use descriptive migration names
- Include timestamps in migration filenames
- Support rollback operations

### 7. Error Handling

- Provide clear error messages
- Support graceful degradation
- Don't fail silently
- Log important operations

### 8. Documentation

- Include comprehensive README.md
- Document all commands and options
- Provide usage examples
- Include troubleshooting section

### 9. Dependencies

- Specify minimum PHP version (^8.2)
- Specify Laravel version (^12.0)
- List required dependencies clearly
- Use `suggest` for optional dependencies

### 10. Service Registration

- Register services as singletons when appropriate
- Use dependency injection
- Keep service provider focused
- Register event listeners in separate provider if needed

---

## Quick Start Checklist

When creating a new package, follow this checklist:

- [ ] Create directory structure
- [ ] Set up `composer.json` with correct namespace
- [ ] Create main package class with VERSION constant
- [ ] Create service provider
- [ ] Implement Installer class
- [ ] Create install command
- [ ] Create uninstall command
- [ ] Create update command (optional)
- [ ] Create help command
- [ ] Organize stubs directory
- [ ] Create config file stub
- [ ] Create migrations (if needed)
- [ ] Create seeders (if needed)
- [ ] Set up PHPUnit configuration
- [ ] Write README.md
- [ ] Test installation process
- [ ] Test uninstallation process
- [ ] Verify environment variable handling
- [ ] Verify route injection
- [ ] Test with different Laravel versions

---

## Example Package Structure

For a complete example, refer to:

- **ATU Multi-Currency**: `/Users/cybertruck/DevProjects/A2-Atu/ATU/MultiCurrency/`
- **A2Commerce**: `/Users/cybertruck/DevProjects/A2-Atu/ATU/MultiCurrency/example-package/`

These packages follow all the conventions outlined in this guide and serve as excellent references.

---

## Common Patterns

### Pattern 1: Settings Management

```php
// Support/SettingsManager.php
class SettingsManager
{
    public function getSetting(string $key, $default = null)
    {
        // Check database first, fallback to config
    }
    
    public function setSetting(string $key, $value): bool
    {
        // Store in database if enabled
    }
}
```

### Pattern 2: Service Registration

```php
// ServiceProvider.php
public function register(): void
{
    $this->app->singleton(YourService::class, function ($app) {
        return new YourService(
            $app->make(Installer::class),
            config('your-package.setting')
        );
    });
}
```

### Pattern 3: Command Options

```php
// Always support these standard options:
{--skip-env : Do not modify .env files}
{--no-overwrite : Skip existing files}
{--force : Skip confirmation prompts}
```

---

## Troubleshooting

### Issue: Files not copying correctly

**Solution**: Check `targetPath()` method in Installer class. Ensure stub directory names match the mapping.

### Issue: Environment variables not added

**Solution**: Verify `ENV_KEYS` constant is defined and `ensureEnvKeys()` is called.

### Issue: Routes not injecting

**Solution**: Check route markers match exactly. Ensure route file exists before injection.

### Issue: Namespace errors

**Solution**: Verify PSR-4 autoload configuration in `composer.json` matches directory structure.

---

## Conclusion

This guide provides a comprehensive template for creating Laravel packages following the ATU package style. By following these conventions, your packages will be:

- Consistent with existing packages
- Easy to install and uninstall
- Well-documented and maintainable
- Following Laravel best practices

For questions or improvements to this guide, refer to existing packages or create an issue in the package repository.
