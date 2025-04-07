<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_fees', function (Blueprint $table) {
            $table->id();
            $table->string('location')->unique(); // Make location unique
            $table->decimal('fee', 10, 2);
            $table->timestamps();
        });

        // Add foreign key to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_fee_id')->nullable()->after('delivery_address')
                  ->constrained('delivery_fees')->nullOnDelete();
        });

        // Add foreign key to cart_items table
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('delivery_fee_id')->nullable()->after('delivery_location')
                  ->constrained('delivery_fees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Remove foreign keys first
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_fee_id']);
            $table->dropColumn('delivery_fee_id');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['delivery_fee_id']);
            $table->dropColumn('delivery_fee_id');
        });

        // Drop the main table
        Schema::dropIfExists('delivery_fees');
    }
};
