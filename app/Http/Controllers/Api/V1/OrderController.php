<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{   
    /**
     * Place a new order
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function placeOrder(Request $request)
    {
        try {
            // Update validation rules to include proof
            $validated = $request->validate([
                'customer_id' => 'required|exists:users,id',
                'name' => 'nullable|string|max:255',
                'delivery_address' => 'required|string',
                'contact_number' => 'required|string',
                'payment_method' => 'required|string',
                'payment_status' => 'required|string',
                'total_amount' => 'required|numeric',
                'price' => 'nullable|numeric', // Add validation for price
                'image' => 'nullable|string',  // Add validation for image
                'proof' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048', // Add this line
                'notes' => 'nullable|string'
            ]);

            DB::beginTransaction();

            // Handle proof upload
            $proofPath = null;
            if ($request->hasFile('proof')) {
                $file = $request->file('proof');
                $filename = time() . '_proof.' . $file->getClientOriginalExtension();
                $file->move('proofs', $filename);
                $proofPath = $filename;
            }

            // Get active cart
            $cart = Cart::where('user_id', Auth::id())
                ->where('status', 'active')
                ->first();

            if (!$cart || !$cart->items()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items in cart to place order'
                ], 400);
            }

            // Get cart items
            $cartItems = $cart->items()->with('product')->get();

            // IMPROVED: Generate order name based strictly on product names in cart
            // If user provided a name, use it, otherwise generate from cart items
            if (!isset($validated['name']) || empty($validated['name'])) {
                $productNames = [];
                
                // Collect all product names from cart items
                foreach ($cartItems as $item) {
                    // Use cart item name or product name, prioritizing cart item name
                    $itemName = $item->name ?? $item->product->name;
                    if (!empty($itemName)) {
                        $productNames[] = $itemName;
                    }
                }
                
                // If we have product names, use them for the order name
                if (count($productNames) > 0) {
                    if (count($productNames) === 1) {
                        // If only one product, use its name directly
                        $validated['name'] = $productNames[0];
                    } else {
                        // If multiple products, combine the names (up to 3)
                        $mainProducts = array_slice($productNames, 0, 3);
                        $remainingCount = count($productNames) - 3;
                        
                        $validated['name'] = implode(', ', $mainProducts);
                        
                        if ($remainingCount > 0) {
                            $validated['name'] .= ' (+' . $remainingCount . ' more)';
                        }
                    }
                } else {
                    // Fallback if no product names found
                    $validated['name'] = 'Order #' . time();
                }
            }

            // Get image from first cart item if not provided
            if (!isset($validated['image']) || empty($validated['image'])) {
                if ($cartItems->count() > 0) {
                    $validated['image'] = $cartItems[0]->image ?? $cartItems[0]->product->image;
                }
            }

            // Calculate order price if not provided (use first item's price)
            if (!isset($validated['price']) || empty($validated['price'])) {
                if ($cartItems->count() > 0) {
                    $validated['price'] = $cartItems[0]->price_at_time_of_addition;
                }
            }

            // Calculate total amount including delivery fee
            $totalAmount = $cartItems->sum(function($item) {
                return ($item->price_at_time_of_addition * $item->quantity) + $item->delivery_fee;
            });

            // Create order
            $order = Order::create([
                'name' => $validated['name'],
                'user_id' => Auth::id(),
                'customer_id' => $validated['customer_id'],
                'delivery_address' => $validated['delivery_address'],
                'contact_number' => $validated['contact_number'],
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_status'],
                'total_amount' => $totalAmount,
                'price' => $validated['price'] ?? null, // Add price field
                'image' => $validated['image'] ?? null, // Add image field
                'proof' => $proofPath, // Add this line
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'delivery_location' => $cartItems->first()->delivery_location,
                'delivery_fee' => $cartItems->first()->delivery_fee
                // No need to set reference_number - it's handled by the boot method
            ]);

            // Copy cart items to order items
            foreach ($cartItems as $cartItem) {
                $itemPrice = $cartItem->price_at_time_of_addition;
                $itemQuantity = $cartItem->quantity;
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'name' => $cartItem->name ?? $cartItem->product->name,
                    'image' => $cartItem->image ?? $cartItem->product->image ?? null, // Copy image
                    'quantity' => $itemQuantity,
                    'price' => $itemPrice,
                    'total_price' => $itemPrice * $itemQuantity, // Calculate total price
                    'custom_message' => $cartItem->custom_message,
                    'delivery_date' => $cartItem->delivery_date
                ]);
            }

            // Empty the cart
            $cart->items()->delete();
            
            // Mark cart as processed
            $cart->status = 'processed';
            $cart->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'order' => array_merge($order->toArray(), [
                        'proof' => $order->proof ? asset('proofs/' . $order->proof) : null
                    ]),
                    'reference_number' => $order->reference_number // Include the auto-generated reference
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order placement error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error placing order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelOrder(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'reason' => 'nullable|string|max:255'
            ]);

            DB::beginTransaction();

            // Find order and check ownership
            $order = Order::with('items.product')
                ->where('id', $request->order_id)
                ->where('user_id', Auth::id())
                ->first();

            // Check if order exists
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or you do not have permission to cancel it'
                ], 404);
            }

            // Check if order can be cancelled
            if (!in_array($order->status, ['pending', 'processing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled - current status: ' . $order->status
                ], 400);
            }

            // Restore stock for each item
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            // Update order status and add cancellation reason
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
                'notes' => $request->reason ?? 'Cancelled by customer'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => $order->fresh()
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'customer_id' => 'required|exists:users,id',
            'delivery_address' => 'required|string',
            'contact_number' => 'required|string',  // Add validation for contact_number
            'payment_method' => 'required|in:cod,gcash',
            'payment_status' => 'required|in:pending,paid',
            'total_amount' => 'required|numeric',
            'notes' => 'nullable|string'
        ]);

        $orderData = array_merge($validated, [
            'user_id' => auth()->id(),
            'status' => 'pending'
        ]);

        $order = Order::create($orderData);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_name' => $product->name,
            'quantity' => $cartItem->quantity,
            'price' => $cartItem->price,
            'total_price' => $cartItem->quantity * $cartItem->price,
            'delivery_date' => $request->delivery_date,
            'delivery_location' => $order->delivery_location, // Make sure this is set
            'customer_name' => auth()->user()->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }

    public function viewOrders()
    {
        try {
            $orders = Order::where('user_id', Auth::id())
                ->with(['items.product'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'message' => 'No orders found'
                ], 404);
            }

            $ordersData = $orders->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => str_pad($order->id, 8, '0', STR_PAD_LEFT),
                    'total_amount' => number_format($order->total_amount, 2),
                    'status' => $order->status,
                    'delivery_address' => $order->delivery_address,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'proof' => $order->proof ? asset('storage/' . $order->proof) : null,
                    'notes' => $order->notes,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'price' => number_format($item->price, 2),
                                'image' => $item->product->image ? asset('storage/' . $item->product->image) : null,
                            ],
                            'quantity' => $item->quantity,
                            'subtotal' => number_format($item->quantity * $item->price, 2),
                            'custom_message' => $item->custom_message,
                            'delivery_date' => $item->delivery_date
                        ];
                    })
                ];
            });

            return response()->json([
                'message' => 'Orders fetched successfully',
                'data' => $ordersData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching orders'
            ], 500);
        }
    }

    public function index()
    {
        try {
            $orders = Order::where('user_id', Auth::id())
                ->with(['items.product'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'reference_number' => $order->reference_number, // Changed from order_number
                    'total_amount' => number_format($order->total_amount, 2),
                    'status' => $order->status,
                    'delivery_address' => $order->delivery_address,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'proof' => $order->proof ? asset('storage/' . $order->proof) : null,
                    'notes' => $order->notes,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'price' => number_format($item->price, 2),
                                'image' => $item->product->image ? asset('storage/' . $item->product->image) : null,
                            ],
                            'quantity' => $item->quantity,
                            'subtotal' => number_format($item->quantity * $item->price, 2),
                            'custom_message' => $item->custom_message,
                            'delivery_date' => $item->delivery_date
                        ];
                    })
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Orders retrieved successfully',
                'data' => $formattedOrders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update delivery order status and notes.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDeliveryOrder(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
                'error' => "The order with ID {$id} does not exist."
            ], Response::HTTP_NOT_FOUND);
        }

        // Validate request
        $request->validate([
            'status' => 'sometimes|string|in:pending,processing,delivering,completed,cancelled',
            'notes' => 'sometimes|nullable|string|max:255',
        ]);

        // Update order
        $order->status = $request->input('status', $order->status);
        
        if ($request->has('notes')) {
            $order->notes = $request->input('notes');
        }

        $order->save();

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order
        ], Response::HTTP_OK);
    }

    public function getOrdersList(Request $request)
    {
        $user = auth()->user();
        $query = Order::with(['orderItems.product']);
        
        // Filter by customer_id if provided in the request
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        // If user is a customer, only show their own orders
        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        }
        
        $orders = $query->latest()->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'count' => $orders->count()
            ]
        ]);
    }

    /**
     * Upload proof of payment image for an order
     * 
     * @param Request $request
     * @param int $id Order ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProof(Request $request, $id)
    {
        try {
            // Debug request
            Log::info('Upload proof request:', [
                'files' => $request->allFiles(),
                'order_id' => $id
            ]);

            // Validate the request data
            $request->validate([
                'proof' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            ]);

            // Find the order
            $order = Order::findOrFail($id);
            
            // Debug order
            Log::info('Order found:', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'auth_id' => Auth::id()
            ]);
            
            // Check if user is authorized to update this order
            if ($order->customer_id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update this order',
                    'customer_id' => $order->customer_id,
                    'auth_id' => Auth::id()
                ], 403);
            }

            if (!$request->hasFile('proof')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No proof file was uploaded'
                ], 400);
            }

            $file = $request->file('proof');
            
            // Debug file
            Log::info('File details:', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize()
            ]);

            // Create proofs directory if it doesn't exist
            $proofPath = public_path('proofs');
            if (!file_exists($proofPath)) {
                mkdir($proofPath, 0777, true);
            }

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Move file
            try {
                $file->move($proofPath, $filename);
            } catch (\Exception $e) {
                Log::error('File upload failed:', [
                    'error' => $e->getMessage(),
                    'path' => $proofPath,
                    'filename' => $filename
                ]);
                throw $e;
            }

            // Update order
            $order->proof = $filename;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Proof uploaded successfully',
                'data' => [
                    'order_id' => $order->id,
                    'proof_url' => asset('proofs/' . $filename)
                ]
            ]);

        } catch (ValidationException $e) {
            Log::error('Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Proof upload error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error uploading proof: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View all orders with their items
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewAllOrders()
    {
        try {
            // Get all orders with their items and related products
            $orders = Order::with(['items.product'])->latest()->get();
            
            $formattedOrders = [];
            
            foreach ($orders as $order) {
                $items = [];
                
                // Format each item in the order
                foreach ($order->items as $item) {
                    $items[] = [
                        'id' => $item->id,
                        'name' => $item->name,
                        'image' => $item->image,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total_price' => $item->total_price,
                        'custom_message' => $item->custom_message,
                        'delivery_date' => $item->delivery_date,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'image' => $item->product->image
                        ] : null
                    ];
                }
                
                // Format the order
                $formattedOrders[] = [
                    'id' => $order->id,
                    'name' => $order->name,
                    'customer_id' => $order->customer_id,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'delivery_address' => $order->delivery_address,
                    'contact_number' => $order->contact_number,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'image' => $order->image,
                    'created_at' => $order->created_at,
                    'items' => $items,
                    'items_count' => count($items)
                ];
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Orders retrieved successfully',
                'data' => $formattedOrders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all orders with their items
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllOrders()
    {
        try {
            // Get all orders with their items and related products
            $orders = Order::with(['items.product'])->latest()->get();
            
            $formattedOrders = [];
            
            foreach ($orders as $order) {
                $items = [];
                
                // Format each item in the order
                foreach ($order->items as $item) {
                    $items[] = [
                        'id' => $item->id,
                        'name' => $item->name,
                        'image' => $item->image,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total_price' => $item->total_price,
                        'custom_message' => $item->custom_message,
                        'delivery_date' => $item->delivery_date,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'image' => $item->product->image
                        ] : null
                    ];
                }
                
                // Format the order
                $formattedOrders[] = [
                    'id' => $order->id,
                    'reference_number' => $order->reference_number, // Add this line
                    'name' => $order->name,
                    'customer_id' => $order->customer_id,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'delivery_address' => $order->delivery_address,
                    'contact_number' => $order->contact_number,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'image' => $order->image,
                    'created_at' => $order->created_at,
                    'items' => $items,
                    'items_count' => count($items)
                ];
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Orders retrieved successfully',
                'data' => $formattedOrders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order items with customer information
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderItemsWithCustomerInfo()
    {
        try {
            // In your method where you fetch order items
            $orderItems = OrderItem::with(['order.user'])->get();

            // When returning the data, include the user information
            return response()->json([
                'status' => 'success',
                'data' => $orderItems->map(function($item) {
                    return [
                        // ...existing order item fields...
                        'customer_name' => $item->order->user->name,
                        'customer_email' => $item->order->user->email,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order items: ' . $e->getMessage()
            ], 500);
        }
    }
}




