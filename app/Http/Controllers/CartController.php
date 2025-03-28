<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\V1\CartController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::get('/cart', [CartController::class, 'viewCart']);
});

class CartController extends Controller
{
    // Add product to cart
    public function addToCart(Request $request)
    {
        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $cartItem = Cart::where('product_id', $product->id)->where('user_id', Auth::id())->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity ?? 1;
            $cartItem->save();
        } else {
            Cart::create([
                'product_id' => $product->id,
                'quantity' => $request->quantity ?? 1,
                'user_id' => Auth::id(),
            ]);
        }

        return response()->json(['message' => 'Product added to cart'], 200);
    }

    // View cart items
    public function viewCart()
    {
        $cartItems = Cart::where('user_id', Auth::id())->with('product')->get();
        return response()->json($cartItems, 200);
    }

    // Update cart item quantity
    public function updateCart(Request $request)
    {
        $cartItem = Cart::find($request->cart_id);

        if (!$cartItem || $cartItem->user_id !== Auth::id()) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return response()->json(['message' => 'Cart updated'], 200);
    }

    // Remove item from cart
    public function removeFromCart(Request $request)
    {
        $cartItem = Cart::find($request->cart_id);

        if (!$cartItem || $cartItem->user_id !== Auth::id()) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Product removed from cart'], 200);
    }

}
