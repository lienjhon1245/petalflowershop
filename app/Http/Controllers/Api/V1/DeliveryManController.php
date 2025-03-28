<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryManController extends Controller
{
    public function show($id)
    {
        // Check if the authenticated user is the delivery man
        if (Auth::id() != $id || Auth::user()->role !== 'delivery_man') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        $deliveryMan = User::where('id', $id)
                          ->where('role', 'delivery_man')
                          ->first();

        if (!$deliveryMan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delivery man not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $deliveryMan
        ]);
    }

    public function update(Request $request, $id)
    {
        // Check if the authenticated user is the delivery man
        if (Auth::id() != $id || Auth::user()->role !== 'delivery_man') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $deliveryMan = User::where('id', $id)
                              ->where('role', 'delivery_man')
                              ->firstOrFail();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'address' => 'sometimes|string|max:255',
                'age' => 'sometimes|integer|min:18',
                'contact_number' => 'sometimes|string|max:20',
                'vehicle_type' => 'sometimes|string|in:motorcycle,bicycle,car',
                'license_number' => 'sometimes|string|max:255'
            ]);

            $deliveryMan->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $deliveryMan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
}