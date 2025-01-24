<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    // ... other methods ...

    public function storeStaff(Request $request)
    {
        // ... validation logic ...

        try {
            Staff::create([
                // ... fields ...
            ]);

            return redirect()->route('staff.index')->with('success', 'Staff added successfully!');
        } catch (\Exception $e) {
            // ... error handling ...
        }
    }

    // ... other methods for update, delete, etc. ...
}