<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('flowers', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->decimal('price', 8, 2);
            $table->boolean('available')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('flowers');
    }
};