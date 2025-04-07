<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('total_price', 10, 2)->nullable();
            $table->text('custom_message')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('delivery_location')->nullable();
            $table->decimal('delivery_fee', 10, 2)->nullable();
            $table->timestamps();
        });

        // First, update any existing NULL values to be 0
        DB::statement('UPDATE order_items SET total_price = price * quantity WHERE total_price IS NULL');
        
        // Then modify the column to be non-nullable with default value 0
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('total_price', 10, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('total_price', 10, 2)->nullable()->change();
        });
    }
};
