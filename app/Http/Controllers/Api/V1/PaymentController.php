<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Update payment status for an order
     */
    public function updateStatus(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'payment_status' => 'required|string',
                'status' => 'required|string'
            ]);

            $order = Order::findOrFail($request->order_id);
            
            // Update only the fields that exist in your database
            $order->payment_status = $request->payment_status;
            $order->status = $request->status;
            
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            Log::error('Payment status update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment status: ' . $e->getMessage()
            ], 500);
        }
    }
}
