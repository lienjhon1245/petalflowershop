<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DeliveryController extends Controller
{
    public function getOrders()
    {
        $orders = Order::where('delivery_man_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getOrderDetails($order_id)
    {
        try {
            // Find the order
            $order = Order::with(['orderItems.product'])
                ->findOrFail($order_id);
            
            // Check if the authenticated user is the delivery person assigned to this order
            // or if they have admin access
            $user = auth()->user();
            
            // Simple permission check without using hasRole()
            $isDeliveryPerson = $order->delivery_man_id === $user->id;
            $isAdmin = $user->id === 1; // Temporary simple admin check, replace with your admin check logic
            
            if (!$isDeliveryPerson && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this order'
                ], 403);
            }
            
            return response()->json([
                'success' => true,
                'data' => $order
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving order details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateOrderStatus(Request $request, $order_id)
    {
        // Find the order
        $order = Order::findOrFail($order_id);
        
        // Validate input
        $validated = $request->validate([
            'status' => 'required|string|in:pending,processing,delivering,delivered,cancelled',
            'notes' => 'nullable|string'
        ]);
        
        // Update order status
        $order->status = $validated['status'];
        
        // Add notes if provided
        if (isset($validated['notes'])) {
            $order->notes = $validated['notes'];
        }
        
        $order->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    public function uploadPaymentProof(Request $request, $order_id)
    {
        $validated = $request->validate([
            'proof' => 'required|image|max:2048',
            'payment_status' => 'required|in:pending,paid,failed'
        ]);

        $order = Order::where('id', $order_id)
            ->where('delivery_man_id', Auth::id())
            ->firstOrFail();

        if ($request->hasFile('proof')) {
            // Delete old proof if exists
            if ($order->proof) {
                Storage::disk('public')->delete($order->proof);
            }

            // Store new proof
            $path = $request->file('proof')->store('payment_proofs', 'public');
            $order->proof = $path;
        }

        $order->payment_status = $validated['payment_status'];
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment proof uploaded successfully',
            'data' => $order
        ]);
    }

    /**
     * Mark an order as delivered with delivery date
     */
    public function markAsDelivered(Request $request, $order_id)
    {
        // Find the order
        $order = Order::findOrFail($order_id);
        
        // Validate input
        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);
        
        // Update order
        $order->status = 'delivered';
        $order->delivery_date = $validated['delivery_date'];
        
        // Add notes if provided
        if (isset($validated['notes'])) {
            $order->notes = $validated['notes'];
        }
        
        $order->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Order marked as delivered successfully',
            'data' => $order
        ]);
    }

    public function getStats(Request $request)
    {
        $period = $request->query('period', 'daily');
        $deliveryManId = Auth::id();

        switch ($period) {
            case 'weekly':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                break;
            
            case 'monthly':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
            
            case 'daily':
            default:
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
                break;
        }

        $stats = [
            'total_deliveries' => Order::where('delivery_man_id', $deliveryManId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
                
            'completed_deliveries' => Order::where('delivery_man_id', $deliveryManId)
                ->where('status', 'delivered')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
                
            'pending_deliveries' => Order::where('delivery_man_id', $deliveryManId)
                ->whereIn('status', ['pending', 'confirmed', 'processing', 'delivering'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
                
            'total_revenue' => Order::where('delivery_man_id', $deliveryManId)
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_amount')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function getPendingDeliveries()
    {
        $orders = Order::where('delivery_man_id', Auth::id())
            ->whereIn('status', ['pending', 'confirmed', 'processing', 'delivering'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getCompletedDeliveries()
    {
        $orders = Order::where('delivery_man_id', Auth::id())
            ->where('status', 'delivered')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function assignDeliveryPerson(Request $request, $order_id)
    {
        try {
            // Find the order
            $order = Order::findOrFail($order_id);
            
            // Assign the authenticated user as the delivery person
            $order->delivery_man_id = auth()->id();
            $order->save();
            
            return response()->json([
                'success' => true,
                'message' => 'You have been assigned to this order',
                'data' => $order
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning delivery person',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCustomerOrders(Request $request)
    {
        try {
            $user = auth()->user();
            $customer_id = $request->query('customer_id');
            
            // For customers: only allow them to see their own orders
            if ($user->role === 'customer') {
                $customer_id = $user->id; // Force to see only their orders
            }
            
            // Start with base query
            $query = Order::with(['orderItems.product']);
            
            // Apply customer filter
            if ($customer_id) {
                $query->where('customer_id', $customer_id);
            }
            
            // Get orders sorted by newest first
            $orders = $query->latest()->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'count' => $orders->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving customer orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function index(Request $request)
    {
        // Get filter parameters
        $status = $request->query('status');
        $customerId = $request->query('customer_id');
        
        // Build query
        $query = Order::with(['orderItems.product']);
        
        // Apply filters if provided
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        
        // Get orders sorted by newest first
        $orders = $query->latest()->get();
        
        // Format data
        $formattedOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number ?? sprintf('%08d', $order->id),
                'total_amount' => $order->total_amount,
                'status' => $order->status,
                'delivery_address' => $order->delivery_address,
                'contact_number' => $order->contact_number,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'notes' => $order->notes,
                'delivery_man_id' => $order->delivery_man_id,
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'price' => $item->price,
                            'image' => $item->product->image
                        ],
                        'quantity' => $item->quantity,
                        'subtotal' => number_format($item->price * $item->quantity, 2),
                        'custom_message' => $item->custom_message,
                        'delivery_date' => $item->delivery_date
                    ];
                })
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedOrders
        ]);
    }
}