<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_lookups', function (Blueprint $table) {
            $table->string('lookup_type', 100);
            $table->string('lookup_code', 100);
            $table->string('meaning', 255);
            $table->string('slug', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->tinyInteger('is_enabled')->default(1)->index();
            $table->tinyInteger('is_default')->default(0);
            $table->timestamps();

            $table->primary(['lookup_type', 'lookup_code'], 'lookup_type_lookup_code_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_lookups');
    }
};
