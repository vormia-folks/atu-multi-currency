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
        Schema::create('atu_multicurrency_currency_rates_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('currency_id');
            $table->decimal('rate', 18, 8)->comment('Historical rate snapshot');
            $table->enum('source', ['manual', 'api'])->comment('Rate source');
            $table->timestamp('fetched_at')->nullable()->comment('When rate was fetched');
            $table->timestamp('created_at')->nullable();

            $table->foreign('currency_id')
                ->references('id')
                ->on('atu_multicurrency_currencies')
                ->onDelete('cascade');

            $table->index('currency_id');
            $table->index('fetched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atu_multicurrency_currency_rates_log');
    }
};
