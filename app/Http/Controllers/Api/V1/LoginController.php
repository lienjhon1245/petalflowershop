<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; 
use Illuminate\Support\Facades\Hash; 

class LoginController extends Controller
{
    //
    public function store(Request $request) {

        $request->validate([
            'email' => ['required', 'string' , 'email'],
            'password' => ['required','string']
        ]);

        // Find the user by email
    $user = User::where('email', $request->email)->first();

    // Check if user exists and verify the password
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Bad Credentials'
        ], 401);
    }

    // Create token for the user
    $token = $user->createToken('auth_token')->plainTextToken;

    // Return successful response with token and user data
    return response()->json([
        'message' => 'Logged in successfully',
        'token' => $token,
        'user' => $user
    ]);
    }
}
