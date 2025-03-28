<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}

class OrderController extends Controller
{
    /**
     * Get a specific order by ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
                'error' => 'The order with ID ' . $id . ' does not exist.'
            ], 404);
        }
        
        return response()->json([
            'message' => 'Order retrieved successfully',
            'data' => $order
        ]);
    }
}
