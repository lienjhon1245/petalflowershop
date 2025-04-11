<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\LogoutController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\DeliveryManController;
use App\Http\Controllers\Api\V1\DeliveryTransactionController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\OrderController as ApiOrderController;
use App\Http\Controllers\Api\DeliveryOrderController;
use App\Http\Controllers\Api\V1\OrderItemController;

// Public routes
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

// Make products public
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Public routes for viewing order items
Route::get('/order-items/all', [OrderItemController::class, 'getAllItems']);
Route::get('/order-items/{id}', [OrderItemController::class, 'getItem']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Product management (create, update, delete) remains protected
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    
    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/place', [OrderController::class, 'placeOrder']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    // Other protected routes...
});

Route::post('/login',[LoginController::class,'store']);
Route::post('/registration',[RegisterController::class,'store']);
Route::post('/logout',[LogoutController::class,'logout'])->middleware('auth:sanctum');
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    // ...existing routes...
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders/cancel', [OrderController::class, 'cancelOrder']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/orders/place', [OrderController::class, 'store']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [OrderController::class, 'viewOrders']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
    });
});
Route::middleware('auth:sanctum')->get('/products', 'ProductController@index');
Route::middleware('auth:sanctum')->group(function () {
    // ... existing routes ...
    Route::post('/orders/place', [OrderController::class, 'placeOrder']);
    Route::middleware('auth:sanctum')->group(function () {
        // ...existing code...
        Route::post('/cart/remove', [CartController::class, 'removeFromCart']);
    });
    Route::post('/profile/update', [ProfileController::class, 'update']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function (Request $request) {
        return response()->json($request->user());
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    });
});
Route::post('/check-email', 'AuthController@checkEmail');

// Delivery Man Profile Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/delivery-profile/{id}', [DeliveryManController::class, 'show']);
    Route::put('/delivery-profile/update/{id}', [DeliveryManController::class, 'update']);
});

// Delivery Man Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/delivery/transactions', [DeliveryTransactionController::class, 'getTransactionHistory']);
    Route::get('/delivery/available-orders', [DeliveryTransactionController::class, 'getAvailableOrders']);
    Route::post('/delivery/orders/{orderId}/status', [DeliveryTransactionController::class, 'updateOrderStatus']);
});

// Add these routes for OrderController
Route::prefix('v1')->middleware('auth:api')->group(function () {
    // Order routes
    Route::post('/orders', 'App\Http\Controllers\Api\V1\OrderController@placeOrder');
    Route::get('/orders', 'App\Http\Controllers\Api\V1\OrderController@index');
    Route::post('/orders/cancel', 'App\Http\Controllers\Api\V1\OrderController@cancelOrder');
});

Route::post('register', [RegisterController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('cart', [CartController::class, 'index']);
    // ...existing routes...
});

// Replace all cart-related routes with this single group
Route::middleware('auth:sanctum')->group(function () {
    // Cart routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::delete('/cart/remove', [CartController::class, 'removeFromCart']);
    Route::put('/cart/update-quantity', [App\Http\Controllers\Api\V1\CartController::class, 'updateQuantity']);
    Route::get('/cart/fix-names', [App\Http\Controllers\Api\V1\CartController::class, 'fixCartItemNames']);
});

// Add these routes for OrderItemController
Route::middleware('auth:sanctum')->group(function () {
    // Order Item routes
    Route::post('/order-items', [App\Http\Controllers\Api\V1\OrderItemController::class, 'store']);
    Route::get('/order-items/{id}', [App\Http\Controllers\Api\V1\OrderItemController::class, 'show']);
    Route::put('/order-items/{id}', [App\Http\Controllers\Api\V1\OrderItemController::class, 'update']);
    Route::delete('/order-items/{id}', [App\Http\Controllers\Api\V1\OrderItemController::class, 'destroy']);
    Route::get('/orders/{order_id}/items', [App\Http\Controllers\Api\V1\OrderItemController::class, 'getItemsByOrder']);
});

// Create a temporary route in your api.php file to check available orders
Route::get('/check-orders', function() {
    return response()->json([
        'orders' => App\Models.Order::all(['id', 'created_at'])
    ]);
});

// Delivery Man Routes
Route::middleware('auth:sanctum')->prefix('delivery')->group(function () {
    // Orders management
    Route::get('/orders', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getAssignedOrders']);
    Route::get('/orders/{id}', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getOrderDetails']);
    Route::put('/orders/{id}/status', [App\Http\Controllers\Api\V1\DeliveryController::class, 'updateOrderStatus']);
    Route::post('/orders/{id}/proof', [App\Http\Controllers\Api\V1\DeliveryController::class, 'uploadPaymentProof']);
    Route::put('/orders/{id}/delivered', [App\Http\Controllers\Api\V1\DeliveryController::class, 'markAsDelivered']);
    
    // Dashboard
    Route::get('/stats', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getDeliveryStats']);
    Route::get('/pending', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getPendingDeliveries']);
    Route::get('/completed', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getCompletedDeliveries']);
    
    Route::put('/orders/{order_id}', [App\Http\Controllers\Api\V1\DeliveryController::class, 'updateDeliveryOrder']);
});

// Add or update the route to support PUT method for delivery orders
Route::middleware(['auth:sanctum'])->group(function () {
    // Allow GET and PUT methods for delivery orders
    Route::get('/delivery/orders/{order_id}', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getOrder']);
    Route::put('/delivery/orders/{order_id}', [App\Http\Controllers\Api\V1\DeliveryController::class, 'updateOrder']);
});

Route::get('/orders/{id}', [App\Http\Controllers\OrderController::class, 'show']);

Route::get('/find-order/{id}', [App\Http\Controllers\OrderController::class, 'findOrder']);

// Remove the following lines
// Route::get('/delivery/orders/{order_id}', [App\Http\Controllers\OrdersController::class, 'getDeliveryOrder']);

// Remove the following lines
// Route::get('delivery/orders/{order}', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getOrder'])
//     ->middleware('auth:sanctum');
// Add these routes for delivery management
Route::middleware('auth:sanctum')->group(function () {
    // Delivery routes
    Route::get('/delivery/orders', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getOrders']);
    Route::get('/delivery/orders/{order_id}', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getOrderDetails']);
    Route::put('/delivery/orders/{order_id}/status', [App\Http\Controllers\Api\V1\DeliveryController::class, 'updateOrderStatus']);
    Route::post('/delivery/orders/{order_id}/proof', [App\Http\Controllers\Api\V1\DeliveryController::class, 'uploadPaymentProof']);
    Route::put('/delivery/orders/{order_id}/delivered', [App\Http\Controllers\Api\V1\DeliveryController::class, 'markAsDelivered']);
    Route::get('/delivery/stats', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getStats']);
    Route::get('/delivery/pending', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getPendingDeliveries']);
    Route::get('/delivery/completed', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getCompletedDeliveries']);
});

Route::get('/check-all-orders', function() {
    return response()->json([
        'orders' => App\Models.Order::all(['id', 'customer_id', 'user_id', 'delivery_man_id', 'status', 'created_at'])
    ]);
});

Route::get('/create-test-order', function() {
    $order = new App\Models\Order();
    $order->user_id = 1; // Admin/owner user ID
    $order->customer_id = 1; // Customer ID
    $order->delivery_man_id = 2; // Change this to your delivery man user ID
    $order->total_amount = 500;
    $order->status = 'pending';
    $order->delivery_address = '123 Test Street';
    $order->contact_number = '1234567890';
    $order->payment_method = 'cod';
    $order->payment_status = 'pending';
    $order->notes = 'Test order';
    $order->save();
    
    return response()->json([
        'success' => true,
        'message' => 'Test order created',
        'order_id' => $order->id
    ]);
});

// Add this to your routes/api.php file
Route::middleware('auth:sanctum')->group(function () {
    // Update order status
    Route::put('/delivery/orders/{order_id}/status', [App\Http\Controllers\Api\V1\DeliveryController::class, 'updateOrderStatus']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/delivery/orders/{order_id}/delivered', [App\Http\Controllers\Api\V1\DeliveryController::class, 'markAsDelivered']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Order details for delivery person
    Route::get('/delivery/orders/{order_id}', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getOrderDetails']);
    
    // Existing routes
    Route::put('/delivery/orders/{order_id}/status', [App\Http\Controllers\Api\V1\DeliveryController::class, 'updateOrderStatus']);
    Route::put('/delivery/orders/{order_id}/delivered', [App\Http\Controllers\Api\V1\DeliveryController::class, 'markAsDelivered']);
    // Assign delivery person
    Route::post('/delivery/orders/{order_id}/assign', [App\Http\Controllers\Api\V1\DeliveryController::class, 'assignDeliveryPerson']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Get all customer orders
    Route::get('/delivery/customer-orders', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getCustomerOrders']);
    
    // Existing routes
    Route::post('/delivery/orders/{order_id}/assign', [App\Http\Controllers\Api\V1\DeliveryController::class, 'assignDeliveryPerson']);
    Route::get('/delivery/orders/{order_id}', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getOrderDetails']);
    Route::put('/delivery/orders/{order_id}/status', [App\Http\Controllers\Api\V1\DeliveryController::class, 'updateOrderStatus']);
    Route::put('/delivery/orders/{order_id}/delivered', [App\Http\Controllers\Api\V1\DeliveryController::class, 'markAsDelivered']);
});

Route::middleware('auth:sanctum')->group(function () {
    // These should both work with the same authentication
    Route::get('/delivery/customer-orders', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getCustomerOrders']);
    
    // Additional parameters should not cause authentication issues
    // The query parameter should not affect the route matching
});

// In routes/api.php
Route::middleware('auth:sanctum')->group(function () {

    // Delivery personnel endpoints - add role check middleware if needed
    Route::get('/delivery/customer-orders', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getCustomerOrders']);
    
    // Add this new route - will work for both customer and delivery users
    Route::get('/orders/list', [App\Http\Controllers\Api\V1\OrderController::class, 'getOrdersList']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders/list', [App\Http\Controllers\Api\V1\OrderController::class, 'getOrdersList']);
});

// Add this to your routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Delivery orders endpoint with proper filtering
    Route::get('/delivery/orders', [App\Http\Controllers\Api\V1\DeliveryController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Existing routes
    Route::get('/delivery/orders', [App\Http\Controllers\Api\V1\DeliveryController::class, 'index']);
    Route::put('/delivery/orders/{order_id}/status', [App\Http\Controllers\Api\V1\DeliveryController::class, 'updateOrderStatus']);
    Route::put('/delivery/orders/{order_id}/delivered', [App\Http\Controllers\Api\V1\DeliveryController::class, 'markAsDelivered']);
    
    // Add this new route for getting a single order
    Route::get('/delivery/order/{id}', [App\Http\Controllers\Api\V1\DeliveryController::class, 'getOrderDetail']);
});

// Payment routes
Route::put('/payment/update-status', [App\Http\Controllers\Api\V1\PaymentController::class, 'updateStatus']);
// You can also support POST for the same endpoint if needed
Route::post('/payment/update-status', [App\Http\Controllers\Api\V1\PaymentController::class, 'updateStatus']);

// Add this with your other order routes
Route::post('/orders/{id}/upload-proof', [App\Http\Controllers\Api\V1\OrderController::class, 'uploadProof']);

// Add these routes to your routes/api.php file, not in the controller
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cart/add', [App\Http\Controllers\Api\V1\CartController::class, 'addToCart']);
    Route::get('/cart', [App\Http\Controllers\Api\V1\CartController::class, 'viewCart']);
    Route::post('/cart/update', [App\Http\Controllers\Api\V1\CartController::class, 'updateCart']);
    Route::post('/cart/remove', [App\Http\Controllers\Api\V1\CartController::class, 'removeFromCart']);
});

// Order Items Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('orders/{order_id}/items', [App\Http\Controllers\Api\V1\OrderItemController::class, 'index']);
    Route::get('order-items/{id}', [App\Http\Controllers\Api\V1\OrderItemController::class, 'show']);
    Route::post('order-items', [App\Http\Controllers\Api\V1\OrderItemController::class, 'store']);
    Route::put('order-items/{id}', [App\Http\Controllers\Api\V1\OrderItemController::class, 'update']);
    Route::delete('order-items/{id}', [App\Http\Controllers\Api\V1\OrderItemController::class, 'destroy']);
    
    // Keep legacy route for compatibility
    Route::get('orders/{order_id}/get-items', [App\Http\Controllers\Api\V1\OrderItemController::class, 'getItemsByOrder']);
    
    // Add these routes within your existing routes
    Route::get('/order-items-with-user', [OrderItemController::class, 'getAllWithUser']);
    Route::get('/order-items-with-user/{id}', [OrderItemController::class, 'getWithUser']);
    
    // Protected routes for order items
    Route::get('/customer/order-items', [OrderItemController::class, 'getCustomerItems']);
    Route::get('/order-items/details/{id}', [OrderItemController::class, 'getItemDetails']);
});

// Public route for viewing order items
Route::get('orders/{orderId}/view-items', [App\Http\Controllers\Api\V1\OrderItemController::class, 'viewOrderItems']);

// Public route for viewing a single order item
Route::get('order-items/{id}/view', [App\Http\Controllers\Api\V1\OrderItemController::class, 'viewOrderItem']);

// Add this line to your routes
Route::get('orders/view-items', [App\Http\Controllers\Api\V1\OrderController::class, 'viewAllOrders']);

// Protected route (only authenticated users can access)
Route::middleware('auth:sanctum')->get('orders/view-items', [App\Http\Controllers\Api\V1\OrderController::class, 'viewAllOrders']);

// Add this as a completely new route - not inside any resource or group
Route::get('all-orders', [App\Http\Controllers\Api\V1\OrderController::class, 'getAllOrders']);

// Add this to your routes/api.php file
Route::middleware('auth:sanctum')->get('all-orders', [App\Http\Controllers\Api\V1\OrderController::class, 'getAllOrders']);