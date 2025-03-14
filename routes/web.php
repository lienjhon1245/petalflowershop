<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\CartController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// Public route
Route::get('/', function () {
    return view('welcome');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminController::class, 'login']);
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard'); // Replace with actual admin dashboard view
        })->name('dashboard');

        Route::get('/account-management', [ManagerController::class, 'index'])->name('account-management');
        Route::get('/account-management/create', [ManagerController::class, 'create'])->name('account-management.create');
        Route::post('/account-management/store', [ManagerController::class, 'store'])->name('account-management.store');

        // Additional admin views
        Route::view('/activity-logs', 'admin.activity-logs')->name('activity-logs');
        Route::view('/orders-overview', 'admin.orders-overview')->name('orders-overview');
        Route::view('/file-management', 'admin.file-management')->name('file-management');

        // Manager-specific actions
        Route::post('/managers/{manager}/ban', [ManagerController::class, 'ban'])->name('managers.ban');
    });

    // Resource controller for managing managers
    Route::resource('managers', ManagerController::class);
});

// Manager Routes
Route::prefix('manager')->name('manager.')->group(function () {
    Route::get('/login', [ManagerController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [ManagerController::class, 'login']);
    Route::post('/logout', [ManagerController::class, 'logout'])->name('logout');

    Route::middleware('auth:manager')->group(function () {
        Route::get('/dashboard', function () {
            return view('manager.dashboard'); // Replace with actual manager dashboard view
        })->name('dashboard');

        // Inventory and reports
        Route::get('/manager/inventory', [InventoryController::class,])->name('manager.inventory');
        Route::get('/inventory', [ManagerController::class, 'inventory'])->name('inventory');
        Route::get('/inventory/view-stock', [ManagerController::class, 'viewStock'])->name('inventory.view-stock');
        Route::get('/inventory/update-stock', [ManagerController::class, 'updateStock'])->name('inventory.update-stock');
        Route::get('/inventory/generate-reports', [ManagerController::class, 'generateReports'])->name('inventory.generate-reports');

        Route::get('/sales-reports', [ManagerController::class, 'salesReports'])->name('sales-reports');
        Route::get('/attendance-reports', [ManagerController::class, 'attendanceReports'])->name('attendance-reports');
    });
});

// Staff Routes
Route::prefix('staff')->name('staff.')->group(function () {
    Route::get('/login', [StaffController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [StaffController::class, 'login']);
    Route::post('/logout', [StaffController::class, 'logout'])->name('logout');

    Route::middleware('auth:staff')->group(function () {
        Route::get('/dashboard', function () {
            return view('staff.dashboard'); // Replace with actual staff dashboard view
        })->name('dashboard');
    });
});

// Resource controller for staff
Route::resource('staff', StaffController::class);

require __DIR__.'/auth.php';
