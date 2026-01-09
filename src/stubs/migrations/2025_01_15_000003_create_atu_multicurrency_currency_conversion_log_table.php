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
        Schema::create('atu_multicurrency_currency_conversion_log', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50)->comment('product, order, report');
            $table->unsignedBigInteger('entity_id')->nullable()->comment('Related entity ID');
            $table->string('context', 50)->comment('save, preview, checkout, report');
            $table->char('base_currency_code', 4)->comment('Source currency code (3-4 characters)');
            $table->char('target_currency_code', 4)->comment('Target currency code (3-4 characters)');
            $table->decimal('base_amount', 18, 6)->comment('Original amount');
            $table->decimal('converted_amount', 18, 6)->comment('Converted amount');
            $table->decimal('rate_used', 18, 8)->comment('Conversion rate applied');
            $table->decimal('fee_applied', 12, 4)->nullable()->comment('Fee applied if any');
            $table->enum('rate_source', ['manual', 'api'])->comment('Rate source');
            $table->unsignedBigInteger('currency_id')->comment('Target currency reference');
            $table->unsignedBigInteger('user_id')->nullable()->comment('User who triggered conversion');
            $table->timestamp('occurred_at')->comment('When conversion occurred');
            $table->timestamp('created_at')->nullable();

            $table->foreign('currency_id')
                ->references('id')
                ->on('atu_multicurrency_currencies')
                ->onDelete('cascade');

            $table->index('entity_type');
            $table->index('entity_id');
            $table->index('currency_id');
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atu_multicurrency_currency_conversion_log');
    }
};
