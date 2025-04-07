<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeliveryFee;

class DeliveryFeeSeeder extends Seeder
{
    public function run()
    {
        $fees = [
            ['location' => 'amlan', 'fee' => 100.00],
            ['location' => 'tanjay', 'fee' => 150.00],
            ['location' => 'bais', 'fee' => 200.00],
            ['location' => 'siaton', 'fee' => 250.00],
            ['location' => 'bayawan', 'fee' => 300.00],
            ['location' => 'dumaguete', 'fee' => 0.00], // Free delivery for Dumaguete
            ['location' => 'dauin', 'fee' => 0.00], // Free delivery for Dauin
            ['location' => 'sibulan', 'fee' => 0.00], // Free delivery for Sibulan
            ['location' => 'valencia', 'fee' => 0.00], // Free delivery for Valencia
            ['location' => 'bacong', 'fee' => 0.00], // Free delivery for Bacong
            
        ];

        foreach ($fees as $fee) {
            DeliveryFee::updateOrCreate(
                ['location' => $fee['location']],
                ['fee' => $fee['fee']]
            );
        }
    }
}
