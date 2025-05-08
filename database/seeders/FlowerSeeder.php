<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Flower;

class FlowerSeeder extends Seeder
{
    public function run()
    {
        $flowers = [
            ['type' => 'rose', 'price' => 7.99],
            ['type' => 'tulip', 'price' => 6.49],
            ['type' => 'lily', 'price' => 8.99],
            // Add all other flowers...
        ];

        foreach ($flowers as $flower) {
            Flower::create($flower);
        }
    }
}