<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->foreignId('user_id')->constrained();
            $table->unsignedBigInteger('delivery_man_id')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('price', 8, 2)->nullable();
            $table->string('status')->default('pending');
            $table->text('delivery_address');
            $table->text('contact_number');
            $table->string('payment_method');
            $table->string('proof')->nullable();
            $table->string('image')->nullable();
            $table->string('payment_status')->default('pending');
            $table->date('delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('users');
            $table->foreign('delivery_man_id')->references('id')->on('users')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
