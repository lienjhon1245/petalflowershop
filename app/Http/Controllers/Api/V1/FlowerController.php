<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\EventPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FlowerController extends Controller
{
    public function getPrices()
    {
        // Get flower prices from products table
        $flowerPrices = Product::where('category', 'flower')
            ->where('status', 'active')
            ->pluck('price', 'name')
            ->toArray();

        // Get event packages
        $eventPackages = EventPackage::where('status', 'active')
            ->get()
            ->mapWithKeys(function ($package) {
                return [
                    $package->key => [
                        'name' => $package->name,
                        'price' => $package->price
                    ]
                ];
            })
            ->toArray();

        return response()->json([
            'flower_prices' => $flowerPrices,
            'event_packages' => $eventPackages
        ]);
    }

    public function getAvailableFlowers()
    {
        $flowers = Product::where('category', 'flower')
            ->where('status', 'active')
            ->select('id', 'name', 'price', 'image', 'description', 'stock')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $flowers
        ]);
    }

    public function customizeFlower(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selectedFlowers' => 'required|array',
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
            // Start database transaction
            DB::beginTransaction();

            // Calculate total price using actual product prices
            $total = 0;
            foreach ($request->selectedFlowers as $flower) {
                $product = Product::where('name', $flower['type'])
                    ->where('category', 'flower')
                    ->where('status', 'active')
                    ->first();

                if (!$product) {
                    throw new \Exception("Flower {$flower['type']} not found or unavailable");
                }

                if ($product->stock < $flower['quantity']) {
                    throw new \Exception("Insufficient stock for {$flower['type']}");
                }

                $total += $product->price * $flower['quantity'];
            }

            // Add event package price
            $eventPackage = EventPackage::where('key', $request->event)
                ->where('status', 'active')
                ->first();

            if ($eventPackage) {
                $total += $eventPackage->price;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_price' => $total,
                    'arrangement' => $request->arrangement,
                    'event_package' => $eventPackage ? [
                        'name' => $eventPackage->name,
                        'price' => $eventPackage->price
                    ] : null,
                    'delivery_details' => [
                        'location' => $request->deliveryLocation,
                        'date' => $request->deliveryDate
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

    public function checkStock($flowerId, $quantity)
    {
        $product = Product::find($flowerId);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'available' => $product->stock >= $quantity,
            'current_stock' => $product->stock
        ]);
    }
}