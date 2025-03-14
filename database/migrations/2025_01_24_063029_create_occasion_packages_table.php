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
        Schema::create('occasion_packages', function (Blueprint $table) {
            $table->id();
            $table->string('package_name');
            $table->enum('occasion_type', [
                'Wedding',
                'Funeral', 
                'Birthday',
                'Anniversary',
                'Graduation',
                'Get Well',
                'Sympathy'
            ]);
            $table->decimal('base_price', 10, 2);
            $table->text('description')->nullable();
            $table->integer('recommended_items_count')->nullable();
            $table->enum('complexity_level', [
                'Basic',
                'Standard',
                'Premium',
                'Deluxe'
            ]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occasion_packages');
    }
};
