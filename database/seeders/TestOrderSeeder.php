<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class CompleteTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test user if doesn't exist
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => Carbon::now(),
            ]
        );

        // Create test customer if your app has a Customer model
        // If 'customer_id' references the users table, we can use the user we just created
        $customerId = $user->id;
        
        // Create a test order
        Order::create([
            'customer_id' => $customerId,
            'user_id' => $user->id,
            'delivery_man_id' => null,
            'total_amount' => 99.99,
            'status' => 'pending',
            'delivery_address' => '123 Test Street, Test City',
            'contact_number' => '555-123-4567',
            'payment_method' => 'cash_on_delivery',
            'proof' => null,
            'payment_status' => 'unpaid',
            'delivery_date' => Carbon::now()->addDays(2),
            'notes' => 'This is a test order',
        ]);
    }
}