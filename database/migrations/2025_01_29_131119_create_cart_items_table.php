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
                $table->string('name')->nullable();
                $table->string('image')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('price_at_time_of_addition', 10, 2);
                $table->text('custom_message')->nullable();
                $table->datetime('delivery_date')->nullable();
                $table->string('delivery_location')->nullable();
                $table->decimal('delivery_fee', 10, 2)->nullable();
                $table->string('arrangement')->nullable();
                $table->string('event')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->json('details')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        } else if (Schema::hasTable('cart_items') && !Schema::hasColumn('cart_items', 'image')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->string('image')->nullable()->after('name');
            });
        } else {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->string('delivery_location')->nullable()->after('delivery_date');
                $table->decimal('delivery_fee', 10, 2)->nullable()->after('delivery_location');
                // Add new columns if they don't exist
                if (!Schema::hasColumn('cart_items', 'customization_id')) {
                    $table->string('customization_id')->nullable()->after('delivery_fee');
                }
                if (!Schema::hasColumn('cart_items', 'price')) {
                    $table->decimal('price', 10, 2)->nullable()->after('customization_id');
                }
                if (!Schema::hasColumn('cart_items', 'details')) {
                    $table->json('details')->nullable()->after('price');
                }
                if (!Schema::hasColumn('cart_items', 'arrangement')) {
                    $table->string('arrangement')->nullable()->after('delivery_fee');
                }
                if (!Schema::hasColumn('cart_items', 'event')) {
                    $table->string('event')->nullable()->after('arrangement');
                }
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

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_location', 
                'delivery_fee',
                'customization_id',
                'price',
                'details',
                'arrangement',
                'event'
            ]);
        });
    }
};
