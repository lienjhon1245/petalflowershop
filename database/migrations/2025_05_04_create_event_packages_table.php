<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Drop existing table if it exists to avoid constraint conflicts
        Schema::dropIfExists('event_packages');

        Schema::create('event_packages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100)->unique();
            $table->string('name', 255);
            $table->decimal('price', 10, 2)->default(0.00);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Insert default event packages
        DB::table('event_packages')->insert([
            [
                'type' => 'none',
                'name' => 'None',
                'price' => 0.00,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'birthday',
                'name' => 'Birthday Celebration',
                'price' => 250.00,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            // ...add other event packages here...
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('event_packages');
    }
};