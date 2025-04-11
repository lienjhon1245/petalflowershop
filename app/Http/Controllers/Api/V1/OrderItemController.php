<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class OrderItemController extends Controller
{
    /**
     * Get all order items for a specific order
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($orderId)
    {
        try {
            $order = Order::with('items.product')->findOrFail($orderId);
            
            // Check if user is authorized to view this order's items
            $isAdmin = Auth::user()->role === 'admin';
            $isOwner = Auth::id() === $order->user_id;
            $isCustomer = Auth::id() === $order->customer_id;
            
            if (!$isAdmin && !$isOwner && !$isCustomer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to view these order items'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Format order items response
            $formattedItems = $order->items->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'image' => $item->image ?? ($item->product ? $item->product->image : null),
                    'quantity' => (int)$item->quantity,
                    'price' => number_format((float)$item->price, 2, '.', ''),
                    'total_price' => number_format((float)$item->total_price, 2, '.', ''),
                    'custom_message' => $item->custom_message,
                    'delivery_date' => $item->delivery_date,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'image' => $item->product->image,
                        'price' => number_format((float)$item->product->price, 2, '.', '')
                    ] : null
                ];
            });
            
            // Calculate total amount
            $totalAmount = $order->items->sum('total_price');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order items retrieved successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'reference_number' => $order->reference_number,
                        'name' => $order->name,
                        'status' => $order->status,
                        'total_amount' => number_format((float)$totalAmount, 2, '.', ''),
                        'delivery_address' => $order->delivery_address,
                        'contact_number' => $order->contact_number,
                        'created_at' => $order->created_at->format('Y-m-d H:i:s')
                    ],
                    'items' => $formattedItems
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order items',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Get a specific order item
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $orderItem = OrderItem::with('product', 'order')->findOrFail($id);
            
            // Check if user is authorized to view this order item
            $isAdmin = Auth::user()->role === 'admin';
            $isOwner = Auth::id() === $orderItem->order->user_id;
            $isCustomer = Auth::id() === $orderItem->order->customer_id;
            
            if (!$isAdmin && !$isOwner && !$isCustomer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to view this order item'
                ], Response::HTTP_FORBIDDEN);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order item retrieved successfully',
                'data' => $orderItem
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order item',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Create a new order item
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Only admins can create order items directly
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to create order items directly'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
                'product_id' => 'required|exists:products,id',
                'name' => 'nullable|string|max:255',
                'image' => 'nullable|string',
                'quantity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'total_price' => 'nullable|numeric|min:0',
                'custom_message' => 'nullable|string',
                'delivery_date' => 'nullable|date'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $validated = $validator->validated();
            
            // Get product if needed
            $product = Product::find($validated['product_id']);
            
            // Set name from product if not provided
            if (!isset($validated['name']) || empty($validated['name'])) {
                $validated['name'] = $product->name;
            }
            
            // Set image from product if not provided
            if (!isset($validated['image']) || empty($validated['image'])) {
                $validated['image'] = $product->image;
            }
            
            // Calculate total price if not provided
            $validated['total_price'] = $validated['price'] * $validated['quantity'];
            
            // Create order item
            $orderItem = OrderItem::create($validated);
            
            // Update order total
            $order = Order::find($validated['order_id']);
            $newTotal = OrderItem::where('order_id', $order->id)->sum('total_price');
            $order->total_amount = $newTotal;
            $order->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order item created successfully',
                'data' => $orderItem
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order item',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Update an order item
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Only admins can update order items
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to update order items'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $orderItem = OrderItem::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'image' => 'nullable|string',
                'quantity' => 'nullable|integer|min:1',
                'price' => 'nullable|numeric|min:0',
                'total_price' => 'nullable|numeric|min:0',
                'custom_message' => 'nullable|string',
                'delivery_date' => 'nullable|date'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            // Update order item
            $orderItem->update($request->all());
            
            // Always recalculate total_price
            $price = $request->has('price') ? $request->price : $orderItem->price;
            $quantity = $request->has('quantity') ? $request->quantity : $orderItem->quantity;
            $orderItem->total_price = $price * $quantity;
            $orderItem->save();
            
            // Update order total
            $order = Order::find($orderItem->order_id);
            $newTotal = OrderItem::where('order_id', $order->id)->sum('total_price');
            $order->total_amount = $newTotal;
            $order->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order item updated successfully',
                'data' => $orderItem
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update order item',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Delete an order item
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // Only admins can delete order items
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to delete order items'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $orderItem = OrderItem::findOrFail($id);
            $orderId = $orderItem->order_id;
            
            // Delete the order item
            $orderItem->delete();
            
            // Update order total
            $order = Order::find($orderId);
            $newTotal = OrderItem::where('order_id', $orderId)->sum('total_price');
            $order->total_amount = $newTotal;
            $order->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order item deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete order item',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Get items by order (keeping your original method for compatibility)
     */
    public function getItemsByOrder($order_id)
    {
        $order = Order::findOrFail($order_id);
        $orderItems = $order->orderItems()->with('product')->get();

        return response()->json([
            'success' => true,
            'data' => $orderItems
        ]);
    }

    /**
     * Public endpoint to view order items by order ID
     * 
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewOrderItems($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            
            $orderItems = OrderItem::where('order_id', $orderId)
                ->with('product')
                ->get();
            
            // Format the response data with explicit type casting
            $formattedItems = $orderItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    // Make sure image is properly passed through
                    'image' => $item->image ?? ($item->product ? $item->product->image : null),
                    'quantity' => (int)$item->quantity,
                    // Format prices as strings with 2 decimal places
                    'price' => number_format((float)$item->price, 2, '.', ''),
                    'total_price' => number_format((float)$item->total_price, 2, '.', ''),
                    'custom_message' => $item->custom_message,
                    'delivery_date' => $item->delivery_date,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        // Make sure product image is properly passed through
                        'image' => $item->product->image,
                        'price' => number_format((float)$item->product->price, 2, '.', '')
                    ] : null
                ];
            });
            
            // Calculate total amount with proper formatting
            $totalAmount = number_format($orderItems->sum('total_price'), 2, '.', '');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order items retrieved successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'reference_number' => $order->reference_number, // Add reference number
                        'name' => $order->name,
                        // Make sure order image is properly passed through
                        'image' => $order->image,
                        'status' => $order->status,
                        'total_amount' => number_format((float)$order->total_amount, 2, '.', ''),
                        'delivery_address' => $order->delivery_address,
                        'contact_number' => $order->contact_number,
                        'created_at' => $order->created_at
                    ],
                    'items' => $formattedItems,
                    'total_amount' => $totalAmount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order items',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Public endpoint to view a single order item
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewOrderItem($id)
    {
        try {
            $orderItem = OrderItem::with('product')->findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order item retrieved successfully',
                'data' => [
                    'id' => $orderItem->id,
                    'name' => $orderItem->name,
                    'image' => $orderItem->image ?? ($orderItem->product ? $orderItem->product->image : null),
                    'quantity' => (int)$orderItem->quantity,
                    'price' => number_format((float)$orderItem->price, 2, '.', ''),
                    'total_price' => number_format((float)$orderItem->total_price, 2, '.', ''),
                    'custom_message' => $orderItem->custom_message,
                    'delivery_date' => $orderItem->delivery_date,
                    'product' => $orderItem->product ? [
                        'id' => $orderItem->product->id,
                        'name' => $orderItem->product->name,
                        'image' => $orderItem->product->image,
                        'price' => number_format((float)$orderItem->product->price, 2, '.', '')
                    ] : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order item',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all order items with user information
     */
    public function getAllWithUser()
    {
        $orderItems = OrderItem::with(['user:id,name'])->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $orderItems
        ]);
    }

    /**
     * Get specific order item with user information
     */
    public function getWithUser($id)
    {
        $orderItem = OrderItem::with(['user:id,name'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $orderItem
        ]);
    }

    /**
     * Get all items with minimal information
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllItems()
    {
        $items = OrderItem::with(['product', 'order.user'])->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $items->map(function($item) {
                return [
                    'id' => $item->id,
                    'order_id' => $item->order_id,
                    'product_name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total_price' => $item->total_price,
                    'delivery_date' => $item->delivery_date,
                    'delivery_location' => $item->delivery_location,
                    'customer_name' => $item->order->user->name ?? 'N/A',
                    'created_at' => $item->created_at->format('Y-m-d H:i:s')
                ];
            })
        ]);
    }

    /**
     * Get specific item with minimal information
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItem($id)
    {
        $item = OrderItem::with(['product', 'order.user'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'product_name' => $item->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total_price' => $item->total_price,
                'delivery_date' => $item->delivery_date,
                'delivery_location' => $item->delivery_location,
                'customer_name' => $item->order->user->name ?? 'N/A',
                'created_at' => $item->created_at->format('Y-m-d H:i:s')
            ]
        ]);
    }
}