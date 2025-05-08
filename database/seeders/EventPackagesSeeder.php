<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventPackagesSeeder extends Seeder
{
    public function run()
    {
        try {
            // Clear existing records
            DB::table('event_packages')->truncate();

            // Insert event packages
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
                    'type' => 'Birthday Celebration',
                    'name' => 'Birthday Celebration',
                    'price' => 250.00,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'type' => 'Graduation Congratulations',
                    'name' => 'Graduation Congratulations',
                    'price' => 400.00,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'type' => 'Grand Opening',
                    'name' => 'Grand Opening',
                    'price' => 350.00,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'type' => 'Wedding Arrangement',
                    'name' => 'Wedding Arrangement',
                    'price' => 430.00,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'type' => 'Anniversary Special',
                    'name' => 'Anniversary Special',
                    'price' => 650.00,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'type' => 'Sympathy & Condolences',
                    'name' => 'Sympathy & Condolences',
                    'price' => 230.00,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('EventPackagesSeeder failed: ' . $e->getMessage());
            throw $e;
        }
    }
}