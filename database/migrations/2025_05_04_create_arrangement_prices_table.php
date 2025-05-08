<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('arrangement_prices', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('arrangement_prices');
    }
};