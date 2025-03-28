<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('cart_id');
                $table->unsignedBigInteger('product_id');
                $table->string('name')->nullable(); // Removed the after() method
                $table->string('image')->nullable(); // Removed the after() method
                $table->integer('quantity')->default(1);
                $table->decimal('price_at_time_of_addition', 10, 2);
                $table->text('custom_message')->nullable();
                $table->date('delivery_date')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        } else if (Schema::hasTable('cart_items') && !Schema::hasColumn('cart_items', 'image')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->string('image')->nullable()->after('name');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'image')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        } else {
            Schema::dropIfExists('cart_items');
        }
    }
};
