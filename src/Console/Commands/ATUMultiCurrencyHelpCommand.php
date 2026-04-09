<?php

namespace Vormia\ATUMultiCurrency\Console\Commands;

use Vormia\ATUMultiCurrency\ATUMultiCurrency;
use Illuminate\Console\Command;

class ATUMultiCurrencyHelpCommand extends Command
{
    protected $signature = 'atumulticurrency:help';

    protected $description = 'Display help information for ATU Multi-Currency package commands';

    public function handle(): int
    {
        $this->displayHeader();
        $this->displayCommands();
        $this->displayUsageExamples();
        $this->displayEnvironmentKeys();
        $this->displayRoutes();
        $this->displayFooter();

        return self::SUCCESS;
    }
    
    /**
     * Display the header
     */
    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                  ATU MULTI-CURRENCY HELP                     ║');
        $this->info('║                  Version ' . str_pad(ATUMultiCurrency::VERSION, 25) . '║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        $this->comment('💱 ATU Multi-Currency provides currency normalization, conversion,');
        $this->comment('   display, and reporting support for A2 Commerce.');
        $this->newLine();
    }
    
    /**
     * Display available commands
     */
    private function displayCommands(): void
    {
        $this->info('📋 AVAILABLE COMMANDS:');
        $this->newLine();
        
        $commands = [
            [
                'command' => 'atumulticurrency:install',
                'description' => 'Install ATU Multi-Currency package with all files and configurations',
                'options' => '--api (core only, no routes/UI), --no-overwrite (keep existing files), --skip-env (leave .env untouched)'
            ],
            [
                'command' => 'atumulticurrency:refresh',
                'description' => 'Refresh migrations and seeders, clear caches',
                'options' => '--force (skip confirmation), --seed (force re-seeding)'
            ],
            [
                'command' => 'atumulticurrency:uninstall',
                'description' => 'Remove all ATU Multi-Currency package files and configurations',
                'options' => '--keep-env (preserve env keys), --force (skip confirmation prompts)'
            ],
            [
                'command' => 'atumulticurrency:help',
                'description' => 'Display this help information',
                'options' => null
            ]
        ];
        
        foreach ($commands as $cmd) {
            $this->line("  <fg=green>{$cmd['command']}</>");
            $this->line("    {$cmd['description']}");
            if ($cmd['options']) {
                $this->line("    <fg=yellow>Options:</> {$cmd['options']}");
            }
            $this->newLine();
        }
    }
    
    /**
     * Display usage examples
     */
    private function displayUsageExamples(): void
    {
        $this->info('💡 USAGE EXAMPLES:');
        $this->newLine();
        
        $examples = [
            [
                'title' => 'Installation',
                'command' => 'php artisan atumulticurrency:install',
                'description' => 'Install ATU Multi-Currency with all files and configurations'
            ],
            [
                'title' => 'API-only Installation (Core Only)',
                'command' => 'php artisan atumulticurrency:install --api',
                'description' => 'Install core only (skips routes modification and UI resources)'
            ],
            [
                'title' => 'Install (Preserve Existing Files)',
                'command' => 'php artisan atumulticurrency:install --no-overwrite',
                'description' => 'Install without overwriting existing files'
            ],
            [
                'title' => 'Install (Skip Environment)',
                'command' => 'php artisan atumulticurrency:install --skip-env',
                'description' => 'Install without modifying .env files'
            ],
            [
                'title' => 'Refresh Package',
                'command' => 'php artisan atumulticurrency:refresh',
                'description' => 'Refresh migrations, seeders, and clear caches'
            ],
            [
                'title' => 'Refresh (Force)',
                'command' => 'php artisan atumulticurrency:refresh --force',
                'description' => 'Refresh without confirmation prompts'
            ],
            [
                'title' => 'Uninstall Package',
                'command' => 'php artisan atumulticurrency:uninstall',
                'description' => 'Remove all ATU Multi-Currency files and configurations'
            ],
            [
                'title' => 'Uninstall (Keep Environment)',
                'command' => 'php artisan atumulticurrency:uninstall --keep-env',
                'description' => 'Uninstall but preserve environment variables'
            ],
            [
                'title' => 'Force Uninstall',
                'command' => 'php artisan atumulticurrency:uninstall --force',
                'description' => 'Uninstall without confirmation prompts'
            ]
        ];
        
        foreach ($examples as $example) {
            $this->line("  <fg=cyan>{$example['title']}:</>");
            $this->line("    <fg=white>{$example['command']}</>");
            $this->line("    <fg=gray>{$example['description']}</>");
            $this->newLine();
        }
    }
    
    /**
     * Display environment keys
     */
    private function displayEnvironmentKeys(): void
    {
        $this->info('⚙️  ENVIRONMENT VARIABLES:');
        $this->newLine();
        
        $this->line('  <fg=white>These keys are added to .env and .env.example during installation:</>');
        $this->newLine();
        
        $envKeys = [
            ['key' => 'ATU_CURRENCY_API_KEY', 'value' => '', 'description' => 'API key for currency rate updates (optional)'],
            ['key' => 'ATU_CURRENCY_UPDATE_FREQUENCY', 'value' => 'daily', 'description' => 'How often to update currency rates (daily, weekly, etc.)'],
        ];
        
        $this->line('  <fg=cyan># ATU Multi-Currency Configuration</>');
        foreach ($envKeys as $env) {
            $value = $env['value'] !== '' ? "={$env['value']}" : '=';
            $this->line("  <fg=white>{$env['key']}{$value}</>");
            $this->line("    <fg=gray>{$env['description']}</>");
        }
        
        $this->newLine();
    }
    
    /**
     * Display routes information
     */
    private function displayRoutes(): void
    {
        $this->info('🛣️  API ROUTES:');
        $this->newLine();
        
        $this->line('  <fg=white>The following route block is added to routes/api.php (commented out by default):</>');
        $this->newLine();
        
        $this->line('  <fg=cyan>// >>> ATU Multi-Currency Routes START</>');
        $this->line('  <fg=cyan>// Route::prefix(\'atu/currency\')->group(function () {</>');
        $this->line('  <fg=cyan>//     // Public-ish</>');
        $this->line('  <fg=cyan>//     Route::get(\'/\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencyController::class, \'index\'])->name(\'api.atu.currency.index\');</>');
        $this->line('  <fg=cyan>//     Route::get(\'/current\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencyController::class, \'current\'])->name(\'api.atu.currency.current\');</>');
        $this->line('  <fg=cyan>//     Route::post(\'/switch\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencyController::class, \'switch\'])->name(\'api.atu.currency.switch\');</>');
        $this->line('  <fg=cyan>//</>');
        $this->line('  <fg=cyan>//     // Admin-style actions (add middleware as needed)</>');
        $this->line('  <fg=cyan>//     Route::post(\'/\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencyController::class, \'store\'])->name(\'api.atu.currency.store\');</>');
        $this->line('  <fg=cyan>//     Route::put(\'/{id}\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencyController::class, \'update\'])->name(\'api.atu.currency.update\');</>');
        $this->line('  <fg=cyan>//     Route::delete(\'/{id}\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencyController::class, \'destroy\'])->name(\'api.atu.currency.destroy\');</>');
        $this->line('  <fg=cyan>//     Route::patch(\'/{id}/toggle-active\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencyController::class, \'toggleActive\'])->name(\'api.atu.currency.toggle_active\');</>');
        $this->line('  <fg=cyan>//     Route::patch(\'/{id}/set-default\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencyController::class, \'setDefault\'])->name(\'api.atu.currency.set_default\');</>');
        $this->line('  <fg=cyan>//</>');
        $this->line('  <fg=cyan>//     // Settings</>');
        $this->line('  <fg=cyan>//     Route::get(\'/settings\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencySettingsController::class, \'show\'])->name(\'api.atu.currency.settings.show\');</>');
        $this->line('  <fg=cyan>//     Route::put(\'/settings\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencySettingsController::class, \'update\'])->name(\'api.atu.currency.settings.update\');</>');
        $this->line('  <fg=cyan>//</>');
        $this->line('  <fg=cyan>//     // Logs</>');
        $this->line('  <fg=cyan>//     Route::get(\'/logs/conversions\', [\\App\\Http\\Controllers\\Api\\Atu\\Multicurrency\\CurrencyLogsController::class, \'conversionLogs\'])->name(\'api.atu.currency.logs.conversions\');</>');
        $this->line('  <fg=cyan>// });</>');
        $this->line('  <fg=cyan>// >>> ATU Multi-Currency Routes END</>');
        
        $this->newLine();
        $this->line('  <fg=gray>Note: Routes are commented out by default. Uncomment and implement as needed.</>');
        $this->newLine();
    }
    
    /**
     * Display footer
     */
    private function displayFooter(): void
    {
        $this->info('📚 ADDITIONAL RESOURCES:');
        $this->newLine();
        
        $this->line('  <fg=white>Implementation Guide:</> docs/build-guide.md');
        $this->line('  <fg=white>Package Repository:</> vormia-folks/atu-multi-currency');
        
        $this->newLine();
        $this->comment('💡 For more detailed documentation, review the docs/build-guide.md file.');
        $this->newLine();
        
        $this->info('🎉 Thank you for using ATU Multi-Currency!');
        $this->newLine();
    }
}
