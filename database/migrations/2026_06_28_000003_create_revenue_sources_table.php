<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('revenue_sources')->restrictOnDelete();
            $table->string('source_type', 100)->index();
            $table->string('code', 100);
            $table->string('display_code', 20)->nullable();
            $table->string('name', 255);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('accepts_values')->default(false);
            $table->boolean('is_enabled')->default(true)->index();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['fund_id', 'code']);
            $table->index(['fund_id', 'parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_sources');
    }
};
