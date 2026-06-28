<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_forecast_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revenue_source_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('value_type', 20);
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->unique(['fund_id', 'revenue_source_id', 'year', 'value_type'], 'revenue_forecast_values_unique');
            $table->index(['fund_id', 'year', 'value_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_forecast_values');
    }
};
