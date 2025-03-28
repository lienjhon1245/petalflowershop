<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DeliveryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryTransactionController extends Controller
{
    /**
     * Get delivery man's transaction history
     */
    public function getTransactionHistory(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role !== 'delivery_man') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only delivery personnel can access transaction history.'
            ], 403);
        }

        $transactions = Order::with(['customer:id,name,address,contact_number', 'items.product'])
            ->where('delivery_man_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
    }

    /**
     * Update order status (accept, pickup, deliver, complete)
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|string|in:accepted,picked_up,on_the_way,delivered'
        ]);

        $user = Auth::user();
        
        if ($user->role !== 'delivery_man') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only delivery personnel can update orders.'
            ], 403);
        }

        $order = Order::where('id', $orderId)
            ->where(function($query) use ($user) {
                // Either assigned to this delivery person or unassigned
                $query->where('delivery_man_id', $user->id)
                    ->orWhereNull('delivery_man_id');
            })
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found or not assigned to you'
            ], 404);
        }

        // If order is being accepted, assign it to this delivery person
        if ($request->status === 'accepted' && $order->delivery_man_id === null) {
            $order->delivery_man_id = $user->id;
        }

        // Update the status
        $order->status = $request->status;
        $order->save();

        // Create a transaction record
        DeliveryTransaction::create([
            'order_id' => $order->id,
            'delivery_man_id' => $user->id,
            'status' => $request->status,
            'timestamp' => now(),
            'location' => $request->location ?? null,
            'notes' => $request->notes ?? null
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Get available orders for delivery
     */
    public function getAvailableOrders()
    {
        $user = Auth::user();
        
        if ($user->role !== 'delivery_man') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only delivery personnel can view available orders.'
            ], 403);
        }

        $availableOrders = Order::with(['customer:id,name,address,contact_number'])
            ->whereNull('delivery_man_id')
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $availableOrders
        ]);
    }
}