<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('atu_multicurrency_currencies', function (Blueprint $table) {
            $table->id();
            $table->char('code', 4)->comment('ISO 4217 currency code (3-4 characters: USD, KES, ZAR)');
            $table->string('symbol', 10)->comment('Currency symbol ($, KSh, R)');
            $table->string('name')->nullable()->comment('Full currency name (e.g., South African Rand, United States Dollar)');
            $table->decimal('rate', 18, 8)->comment('1 unit = ? base currency');
            $table->boolean('is_auto')->default(false)->comment('API-managed or manual');
            $table->decimal('fee', 12, 4)->nullable()->comment('Optional per-currency fee');
            $table->boolean('is_default')->default(false)->comment('Must match A2 base currency');
            $table->unsignedBigInteger('country_taxonomy_id')->nullable()->comment('vrm_taxonomies reference');
            $table->boolean('is_active')->default(true)->comment('Toggle availability');
            $table->timestamps();

            $table->index('code');
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atu_multicurrency_currencies');
    }
};
