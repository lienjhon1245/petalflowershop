<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrdersController extends Controller
{
    /**
     * Find an order by ID and return details or error message
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function findOrder($id): JsonResponse
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
                'error' => "The order with ID {$id} does not exist."
            ], 404);
        }
        
        return response()->json([
            'order' => $order
        ], 200);
    }

    /**
     * Get delivery order details by ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getDeliveryOrder($id): JsonResponse
    {
        $order = Order::where('id', $id)->first();
        
        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
                'error' => "The order with ID {$id} does not exist."
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $order
        ], 200);
    }

    /**
     * Update delivery order status and notes
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateDeliveryOrder(Request $request, $id): JsonResponse
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
                'error' => "The order with ID {$id} does not exist."
            ], 404);
        }
        
        // Validate request
        $request->validate([
            'status' => 'sometimes|string|in:pending,processing,delivering,completed,cancelled',
            'notes' => 'sometimes|nullable|string|max:255',
        ]);
        
        // Update order fields
        if ($request->has('status')) {
            $order->status = $request->status;
        }
        
        if ($request->has('notes')) {
            $order->notes = $request->notes;
        }
        
        $order->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order
        ], 200);
    }
}
