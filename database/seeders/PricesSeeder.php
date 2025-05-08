<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PricesSeeder extends Seeder
{
    public function run()
    {
        // Clear existing records
        DB::table('arrangement_prices')->truncate();
        DB::table('event_packages')->truncate();

        // Seed arrangement prices
        DB::table('arrangement_prices')->insert([
            [
                'type' => 'bouquet',
                'name' => 'Bouquet',
                'price' => 49.99,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'vase arrangement',
                'name' => 'Vase Arrangement',
                'price' => 69.99,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'basket',
                'name' => 'Basket',
                'price' => 59.99,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'box',
                'name' => 'Box',
                'price' => 54.99,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // Seed event packages
        DB::table('event_packages')->insert([
            [
                'type' => 'none',
                'name' => 'None',
                'price' => 0,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'birthday',
                'name' => 'Birthday Celebration',
                'price' => 250,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'Graduation Congratulations',
                'name' => 'Graduation Congratulations',
                'price' => 400,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'Grand Opening',
                'name' => 'Grand Opening',
                'price' => 350,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'Wedding Arrangement',
                'name' => 'Wedding Arrangement',
                'price' => 430,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'Anniversary Special',
                'name' => 'Anniversary Special',
                'price' => 650,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'type' => 'Sympathy & Condolences',
                'name' => 'Sympathy & Condolences',
                'price' => 230,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}