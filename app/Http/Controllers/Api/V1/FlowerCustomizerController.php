<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\EventPackage;
use App\Models\DeliveryFee; // Add this import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FlowerCustomizerController extends Controller
{
    public function getAvailableFlowers()
    {
        try {
            $flowers = Product::where('stock', '>', 0)
                ->select([
                    'id',
                    'name',
                    'description',
                    'price',
                    'stock',
                    'image_url'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $flowers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching flowers'
            ], 500);
        }
    }

    public function getPrices()
    {
        try {
            $arrangementPrices = DB::table('arrangement_prices')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->type => [
                        'name' => $item->name,
                        'price' => '₱' . number_format($item->price, 2)
                    ]];
                })
                ->toArray();

            $eventPackages = DB::table('event_packages')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->type => [
                        'name' => $item->name,
                        'price' => $item->price
                    ]];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'arrangementPrices' => $arrangementPrices,
                    'eventPackages' => $eventPackages
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching prices'
            ], 500);
        }
    }

    public function customizeFlower(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selectedFlowers' => 'required|array',
            'selectedFlowers.*.id' => 'required|exists:products,id',
            'selectedFlowers.*.quantity' => 'required|integer|min:1',
            'arrangement' => 'required|string',
            'event' => 'required|string',
            'deliveryLocation' => 'required|string',
            'deliveryDate' => 'required|date',
            'message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get delivery fee based on location
            $deliveryFee = DeliveryFee::where('location', $request->deliveryLocation)
                ->first();

            $deliveryFeeAmount = $deliveryFee ? $deliveryFee->fee : 0.00;

            // Set a default user ID for testing
            $userId = 1; // Replace this with actual auth()->id() when authentication is implemented

            // Get or create active cart
            $cart = Cart::firstOrCreate([
                'user_id' => $userId,
                'status' => 'active'
            ]);

            $total = 0;
            $flowerDetails = [];

            foreach ($request->selectedFlowers as $flower) {
                $product = Product::select([
                    'id',
                    'name',
                    'description',
                    'price',
                    'stock',
                    'image'
                ])->find($flower['id']);

                if (!$product) {
                    throw new \Exception("Product {$flower['id']} not found");
                }

                if ($product->stock < $flower['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                $subtotal = $product->price * $flower['quantity'];
                $total += $subtotal;

                // Create cart item with user_id
                CartItem::create([
                    'user_id' => $userId, // Add user_id here
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image,
                    'quantity' => $flower['quantity'],
                    'price_at_time_of_addition' => $product->price,
                    'price' => $product->price,
                    'custom_message' => $request->message,
                    'delivery_date' => $request->deliveryDate,
                    'delivery_location' => $request->deliveryLocation,
                    'delivery_fee' => $deliveryFeeAmount,
                    'arrangement' => $request->arrangement,
                    'event' => $request->event,
                    'details' => [
                        'arrangement' => $request->arrangement,
                        'event' => $request->event,  // This should match the 'type' values from EventPackagesSeeder
                        'description' => $product->description,
                        'customization' => [
                            'flowers' => $flowerDetails,
                            'total_price' => $total,
                            'arrangement_type' => $request->arrangement
                        ],
                        'delivery' => [
                            'location' => $request->deliveryLocation,
                            'fee' => number_format($deliveryFeeAmount, 2, '.', ''),
                            'date' => $request->deliveryDate
                        ]
                    ]
                ]);

                $flowerDetails[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => number_format($product->price, 2),
                    'quantity' => $flower['quantity'],
                    'subtotal' => number_format($subtotal, 2),
                    'image' => $product->image
                ];
            }

            DB::commit();

            // Update the response to include delivery fee
            return response()->json([
                'success' => true,
                'data' => [
                    'cart_id' => $cart->id,
                    'flowers' => $flowerDetails,
                    'total_price' => number_format($total, 2),
                    'arrangement' => $request->arrangement,
                    'delivery_details' => [
                        'location' => $request->deliveryLocation,
                        'date' => $request->deliveryDate,
                        'message' => $request->message,
                        'fee' => number_format($deliveryFeeAmount, 2, '.', '')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customization_id' => 'required|string',
            'flowers' => 'required|array',
            'arrangement' => 'required|string',
            'total_price' => 'required|numeric',
            'delivery_date' => 'required|date',
            'delivery_location' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $cart = Cart::firstOrCreate([
                'user_id' => auth()->id(),
                'status' => 'active'
            ]);

            $cartItem = new CartItem([
                'cart_id' => $cart->id,
                'customization_id' => $request->customization_id,
                'price' => $request->total_price,
                'quantity' => 1,
                'details' => [
                    'flowers' => $request->flowers,
                    'arrangement' => $request->arrangement,
                    'delivery_date' => $request->delivery_date,
                    'delivery_location' => $request->delivery_location
                ]
            ]);

            $cart->items()->save($cartItem);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Added to cart successfully',
                'data' => [
                    'cart_id' => $cart->id,
                    'item_id' => $cartItem->id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error adding to cart'
            ], 500);
        }
    }
}