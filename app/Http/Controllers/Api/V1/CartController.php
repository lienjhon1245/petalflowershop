<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart; // Model for the cart table
use App\Models\Product; // Model for the products table
use Illuminate\Support\Facades\Auth; // For getting the authenticated user
use App\Models\CartItem; // Model for the cart_items table
use Illuminate\Support\Facades\Log; // Add this for logging
use Illuminate\Validation\ValidationException; // Add this for validation exception handling
use App\Models\DeliveryFee; // Add this line

class CartController extends Controller
{
    /**
     * Add product to cart
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToCart(Request $request)
    {
        try {
            // Get or create active cart
            $cart = Cart::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'status' => 'active'
                ]
            );

            // Validate request data
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'price_at_time_of_addition' => 'required|numeric',
                'delivery_location' => 'required|string|in:Amlan,Tanjay,Bais,Siaton,Bayawan,Dumaguete',
                'delivery_date' => 'required|date',
                'custom_message' => 'nullable|string'
            ]);

            // Check if product already exists in cart with same delivery date and custom message
            $existingCartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $validated['product_id'])
                ->where('delivery_date', $validated['delivery_date'])
                ->where('delivery_location', $validated['delivery_location'])
                ->where(function ($query) use ($validated) {
                    $query->where('custom_message', $validated['custom_message'])
                        ->orWhereNull('custom_message');
                })
                ->first();

            if ($existingCartItem) {
                // Update existing cart item
                $existingCartItem->quantity += $validated['quantity'];
                $existingCartItem->save();

                $cartItem = $existingCartItem;
            } else {
                // Get delivery fee
                $deliveryFee = DeliveryFee::where('location', strtolower($validated['delivery_location']))->first();

                if (!$deliveryFee) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid delivery location. Available locations: amlan, tanjay, bais, siaton, bayawan',
                        'debug' => [
                            'requested_location' => $validated['delivery_location'],
                            'available_locations' => DeliveryFee::pluck('location')
                        ]
                    ]);
                }

                $product = Product::findOrFail($validated['product_id']);
                
                // Check if cart already has items with delivery fee
                $existingItemWithFee = CartItem::where('cart_id', $cart->id)
                    ->where('delivery_fee', '>', 0)
                    ->first();

                // Set delivery fee to 0 if another item already has it
                $appliedDeliveryFee = $existingItemWithFee ? 0 : $deliveryFee->fee;

                // Create new cart item
                $cartItemData = [
                    'cart_id' => $cart->id,
                    'user_id' => Auth::id(),
                    'product_id' => $validated['product_id'],
                    'name' => $product->name,
                    'image' => $product->image,
                    'quantity' => $validated['quantity'],
                    'price_at_time_of_addition' => $validated['price_at_time_of_addition'],
                    'custom_message' => $validated['custom_message'] ?? null,
                    'delivery_date' => $validated['delivery_date'],
                    'delivery_location' => $validated['delivery_location'],
                    'delivery_fee' => $appliedDeliveryFee
                ];

                $cartItem = CartItem::create($cartItemData);
            }

            // Calculate total without using closure
            $subtotal = CartItem::where('cart_id', $cart->id)
                ->selectRaw('SUM(quantity * price_at_time_of_addition) as total')
                ->value('total') ?? 0;

            // Get the single delivery fee from the first item that has it
            $cartDeliveryFee = CartItem::where('cart_id', $cart->id)
                ->where('delivery_fee', '>', 0)
                ->value('delivery_fee') ?? 0;

            return response()->json([
                'status' => 'success',
                'message' => $existingCartItem ? 'Cart item quantity updated' : 'Product added to cart',
                'data' => [
                    'cart_item' => $cartItem->load('product'),
                    'delivery_fee' => number_format($cartDeliveryFee, 2),
                    'total_with_delivery' => $subtotal + $cartDeliveryFee
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Cart add error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add item to cart: ' . $e->getMessage(),
                'debug' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function removeFromCart(Request $request)
    {
        try {
            // Add missing validation array
            $validated = $request->validate([
                'cart_item_id' => 'required|integer|exists:cart_items,id'
            ]);

            $cartItem = CartItem::where('id', $validated['cart_item_id'])
                ->whereHas('cart', function($query) {
                    $query->where('user_id', Auth::id())
                        ->where('status', 'active');
                })
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cart item not found in your active cart'
                ], 404);
            }

            $cartItem->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Item removed from cart successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Cart item removal error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error removing item from cart'
            ], 500);
        }
    }

    // Main method for viewing cart - replaces both viewCart and index methods
    public function index()
    {
        try {
            $cart = Cart::where('user_id', Auth::id())
                ->where('status', 'active')
                ->with(['items.product'])
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Your cart is empty',
                    'data' => [
                        'cart' => null,
                        'items' => []
                    ]
                ]);
            }

            $cartData = [
                'cart_id' => $cart->id,
                'items' => $cart->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price_at_time_of_addition,
                        'custom_message' => $item->custom_message,
                        'delivery_date' => $item->delivery_date,
                        'subtotal' => $item->quantity * $item->price_at_time_of_addition
                    ];
                }),
                'total' => $cart->items->sum(function($item) {
                    return $item->quantity * $item->price_at_time_of_addition;
                })
            ];

            return response()->json([
                'status' => 'success',
                'data' => $cartData
            ]);
            
        } catch (\Exception $e) {
            Log::error('View cart error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching cart'
            ], 500);
        }
    }

    // Keeping store method for backward compatibility but redirecting to addToCart
    public function store(Request $request)
    {
        // Validate the basic required fields
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);
        
        // Get product price for price_at_time_of_addition
        $product = Product::findOrFail($validated['product_id']);
        
        // Create a new request with all needed parameters
        $newRequest = new Request([
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'price_at_time_of_addition' => $product->price,
            'delivery_date' => $request->input('delivery_date', now()->addDay()->format('Y-m-d')),
            'custom_message' => $request->input('custom_message', '')
        ]);

        // Call addToCart with the properly formatted request
        return $this->addToCart($newRequest);
    }

    /**
     * Update the quantity of a specific cart item
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Request $request)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'id' => 'required|integer|exists:cart_items,id',
                'quantity' => 'required|integer|min:1'
            ]);

            // Find the cart item
            $cartItem = CartItem::where('id', $validated['id'])
                ->whereHas('cart', function($query) {
                    $query->where('user_id', Auth::id())
                        ->where('status', 'active');
                })
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cart item not found in your active cart'
                ], 404);
            }

            // Update quantity
            $cartItem->quantity = $validated['quantity'];
            $cartItem->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Cart item quantity updated successfully',
                'data' => $cartItem
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Cart quantity update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating cart item quantity'
            ], 500);
        }
    }

    /**
     * View all items in the user's cart
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewCart()
    {
        try {
            // Get active cart for the current user
            $cart = Cart::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'status' => 'active'
                ]
            );
            
            // Get all cart items with their products
            $cartItems = CartItem::where('cart_id', $cart->id)
                ->with('product')
                ->get();
                
            // Calculate total including delivery fees
            $total = $cartItems->sum(function($item) {
                return ($item->quantity * $item->price_at_time_of_addition) + ($item->delivery_fee ?? 0);
            });

            // Format cart items with delivery info
            $formattedItems = $cartItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'image' => $item->image,
                    'quantity' => $item->quantity,
                    'price' => $item->price_at_time_of_addition,
                    'custom_message' => $item->custom_message,
                    'delivery_date' => $item->delivery_date,
                    'delivery_location' => $item->delivery_location,
                    'delivery_fee' => number_format($item->delivery_fee ?? 0, 2),
                    'subtotal' => number_format($item->quantity * $item->price_at_time_of_addition, 2),
                    'total_with_delivery' => number_format(
                        ($item->quantity * $item->price_at_time_of_addition) + ($item->delivery_fee ?? 0),
                        2
                    ),
                    'product' => $item->product
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'message' => 'Cart items retrieved successfully',
                'data' => [
                    'items' => $formattedItems,
                    'total_items' => $cartItems->count(),
                    'total' => number_format($total, 2)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('View cart error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving cart items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fix null names in cart items
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function fixCartItemNames()
    {
        try {
            $cartItems = CartItem::whereNull('name')->get();
            $updated = 0;

            foreach ($cartItems as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $item->name = $product->name;
                    $item->save();
                    $updated++;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "$updated cart item names updated",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fixing cart item names: ' . $e->getMessage()
            ], 500);
        }
    }
}
