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
        Schema::create('company_financial_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->decimal('current_stock_price', 10, 2)->nullable();
            $table->decimal('sentiment_score', 5, 2)->nullable();
            $table->string('financial_signal')->nullable();
            $table->bigInteger('market_cap')->nullable();
            $table->bigInteger('average_daily_volume')->nullable();
            $table->decimal('price_performance_1week', 8, 4)->nullable();
            $table->decimal('price_performance_1month', 8, 4)->nullable();
            $table->decimal('fair_value_estimate', 10, 2)->nullable();
            $table->json('data_sources')->nullable();
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_financial_data');
    }
};
