<?php

namespace App\Http\Controllers;

use App\Models\Manager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ManagerController extends Controller
{
    public function inventory()
    {
        // Your logic for handling inventory
        return view('manager.inventory');
    }
    

    /**
     * Display a listing of managers.
     */
    public function index()
    {
        $managers = Manager::all();
        return view('admin.account-management', compact('managers'));
    }
    

    public function show($id)
    {
        // Add your logic here (e.g., fetching a manager or dashboard details)
        return view('manager.dashboard', ['id' => $id]);
    }


    public function showLoginForm()
    {
        return view('manager.login'); // Points to the admin login blade file
    }


    /**
     * Store a newly created manager.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|unique:managers',
            'password' => 'required|string|min:6',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
        ]);

        try {
            Manager::create([
                'fullname' => $request->fullname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
            ]);

            return redirect()->route('admin.account-management')->with('success', 'Manager added successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to add manager. Please try again later.']);
        }
    }

    /**
     * Show the manager details for editing (AJAX support).
     */
    public function edit(Manager $manager)
    {
        return response()->json($manager);
    }

    /**
     * Update the specified manager.
     */
    public function update(Request $request, Manager $manager)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|unique:managers,email,' . $manager->id,
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
        ]);

        try {
            $manager->update($request->only(['fullname', 'email', 'date_of_birth', 'gender']));

            return redirect()->route('admin.account-management')->with('success', 'Manager updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update manager. Please try again later.']);
        }
    }

    /**
     * Remove the specified manager (soft delete).
     */
    public function destroy(Manager $manager)
    {
        try {
            $manager->delete();

            return redirect()->route('admin.account-management')->with('success', 'Manager deleted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete manager. Please try again later.']);
        }
    }

    /**
     * Ban a manager.
     */
    public function ban(Manager $manager)
    {
        try {
            $manager->update(['banned_at' => now()]);

            return redirect()->route('admin.account-management')->with('success', 'Manager banned successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to ban manager. Please try again later.']);
        }
    }

    /**
     * Unban a manager.
     */
    public function unban(Manager $manager)
    {
        try {
            $manager->update(['banned_at' => null]);

            return redirect()->route('admin.account-management')->with('success', 'Manager unbanned successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to unban manager. Please try again later.']);
        }
    }

 
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('manager')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/manager/dashboard'); // Admin dashboard route
        }

        return back()->withErrors([
            'email' => 'Invalid credentials. Please try again.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('manager')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/manager/login');
    }
}
