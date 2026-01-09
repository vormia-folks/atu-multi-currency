<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration alters existing tables to support 4-character currency codes
     * and adds the name field. It's safe to run even if tables don't exist yet.
     */
    public function up(): void
    {
        // Alter currencies table if it exists
        if (Schema::hasTable('atu_multicurrency_currencies')) {
            Schema::table('atu_multicurrency_currencies', function (Blueprint $table) {
                // Change code column from char(3) to char(4) if it exists
                if (Schema::hasColumn('atu_multicurrency_currencies', 'code')) {
                    $table->char('code', 4)->change()->comment('ISO 4217 currency code (3-4 characters: USD, KES, ZAR)');
                }
                
                // Add name column if it doesn't exist
                if (!Schema::hasColumn('atu_multicurrency_currencies', 'name')) {
                    $table->string('name')->nullable()->after('symbol')->comment('Full currency name (e.g., South African Rand, United States Dollar)');
                }
            });
        }

        // Alter conversion log table if it exists
        if (Schema::hasTable('atu_multicurrency_currency_conversion_log')) {
            Schema::table('atu_multicurrency_currency_conversion_log', function (Blueprint $table) {
                // Change base_currency_code from char(3) to char(4) if it exists
                if (Schema::hasColumn('atu_multicurrency_currency_conversion_log', 'base_currency_code')) {
                    $table->char('base_currency_code', 4)->change()->comment('Source currency code (3-4 characters)');
                }
                
                // Change target_currency_code from char(3) to char(4) if it exists
                if (Schema::hasColumn('atu_multicurrency_currency_conversion_log', 'target_currency_code')) {
                    $table->char('target_currency_code', 4)->change()->comment('Target currency code (3-4 characters)');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This will revert to 3-character codes, which may cause data loss
     * if any 4-character codes exist. Use with caution.
     */
    public function down(): void
    {
        // Revert currencies table if it exists
        if (Schema::hasTable('atu_multicurrency_currencies')) {
            Schema::table('atu_multicurrency_currencies', function (Blueprint $table) {
                // Change code column back to char(3) if it exists
                if (Schema::hasColumn('atu_multicurrency_currencies', 'code')) {
                    $table->char('code', 3)->change()->comment('ISO 4217 currency code (USD, KES, ZAR)');
                }
                
                // Remove name column if it exists
                if (Schema::hasColumn('atu_multicurrency_currencies', 'name')) {
                    $table->dropColumn('name');
                }
            });
        }

        // Revert conversion log table if it exists
        if (Schema::hasTable('atu_multicurrency_currency_conversion_log')) {
            Schema::table('atu_multicurrency_currency_conversion_log', function (Blueprint $table) {
                // Change base_currency_code back to char(3) if it exists
                if (Schema::hasColumn('atu_multicurrency_currency_conversion_log', 'base_currency_code')) {
                    $table->char('base_currency_code', 3)->change()->comment('Source currency code');
                }
                
                // Change target_currency_code back to char(3) if it exists
                if (Schema::hasColumn('atu_multicurrency_currency_conversion_log', 'target_currency_code')) {
                    $table->char('target_currency_code', 3)->change()->comment('Target currency code');
                }
            });
        }
    }
};
