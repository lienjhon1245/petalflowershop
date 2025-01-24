<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\LogoutController;
use App\Http\Controllers\Api\V1\ProductController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login',[LoginController::class,'store']);
Route::post('/registration',[RegisterController::class,'store']);
Route::post('/logout',[LogoutController::class,'logout'])->middleware('auth:sanctum');

Route::get('/products', [ProductController::class, 'index']);