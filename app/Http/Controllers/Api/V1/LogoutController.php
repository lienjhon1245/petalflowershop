<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        // Enhanced logging
        Log::info('Logout request received', [
            'user_id' => $request->user()->id,
            'user_type' => $request->user()->type ?? 'unknown',
            'token_id' => $request->user()->currentAccessToken()->id ?? null,
            'request_path' => $request->path(),
            'method' => $request->method()
        ]);
        
        $request->user()->currentAccessToken()->delete();
        
        // Log successful logout
        Log::info('User logged out successfully', [
            'user_id' => $request->user()->id
        ]);
        
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}