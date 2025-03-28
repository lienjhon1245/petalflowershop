<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        try {
            $user = Auth::user();
            
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'address' => ['required', 'string', 'max:255'],
                'age' => ['required', 'integer', 'min:18'],
                'contact_number' => ['required', 'string', 'max:20']
            ]);

            $user->update($validated);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}