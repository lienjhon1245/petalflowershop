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
                'name' => 'nullable|string',
                'image' => 'nullable|string',  // Image can be a URL string
                'custom_message' => 'nullable|string',
                'delivery_date' => 'required|date'
            ]);

            // Get the product with complete details
            $product = Product::findOrFail($validated['product_id']);

            // Populate name and image from product if not provided
            $cartItemData = [
                'cart_id' => $cart->id,
                'user_id' => Auth::id(),
                'product_id' => $product->id,
                'name' => $validated['name'] ?? $product->name,
                'image' => $validated['image'] ?? $product->image, // Get image from product
                'quantity' => $validated['quantity'],
                'price_at_time_of_addition' => $validated['price_at_time_of_addition'],
                'custom_message' => $validated['custom_message'] ?? null,
                'delivery_date' => $validated['delivery_date']
            ];

            // Check if item already exists in cart
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existingItem) {
                // Update quantity if item already exists
                $existingItem->quantity += $validated['quantity'];
                
                // Update other fields if not set but now available
                if ($existingItem->name === null && isset($cartItemData['name'])) {
                    $existingItem->name = $cartItemData['name'];
                }
                
                if ($existingItem->image === null && isset($cartItemData['image'])) {
                    $existingItem->image = $cartItemData['image'];
                }
                
                $existingItem->save();
                
                // Load the product relationship
                $existingItem->load('product');
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Cart item quantity updated',
                    'data' => $existingItem
                ]);
            }

            // Create new cart item
            $cartItem = CartItem::create($cartItemData);
            
            // Load the product relationship for the response
            $cartItem->load('product');

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to cart',
                'data' => $cartItem
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
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
                
            // Calculate total
            $total = $cartItems->sum(function($item) {
                return $item->quantity * $item->price_at_time_of_addition;
            });
            
            return response()->json([
                'status' => 'success',
                'message' => 'Cart items retrieved successfully',
                'data' => [
                    'items' => $cartItems,
                    'total' => $total
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
