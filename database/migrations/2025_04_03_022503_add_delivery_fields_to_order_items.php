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
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'delivery_location')) {
                $table->string('delivery_location')->nullable()->after('delivery_date');
            }
            if (!Schema::hasColumn('order_items', 'delivery_fee')) {
                $table->decimal('delivery_fee', 10, 2)->nullable()->after('delivery_location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['delivery_location', 'delivery_fee']);
        });
    }
};
