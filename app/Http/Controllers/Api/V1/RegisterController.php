<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'address' => 'required|string|max:255',
                'age' => 'required|integer|min:18',
                'contact_number' => 'required|string|max:20',
                'role' => 'required|string|in:customer,delivery_man'
            ]);

            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->address = $request->address;
            $user->age = $request->age;
            $user->contact_number = $request->contact_number;
            $user->role = $request->role;
            
            // Add delivery man specific fields if role is delivery_man
            if ($request->role === 'delivery_man') {
                $request->validate([
                    'vehicle_type' => 'required|string|in:motorcycle,bicycle,car',
                    'license_number' => 'required|string|max:255'
                ]);
                
                $user->vehicle_type = $request->vehicle_type;
                $user->license_number = $request->license_number;
            }
            
            $user->save();

            event(new Registered($user));

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => ucfirst($request->role) . ' registered successfully',
                'token' => $token,
                'user' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
