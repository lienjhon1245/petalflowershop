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
        Schema::create('package_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('occasion_packages');
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity');
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_components');
    }
};
