<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $inventory = Inventory::all(); // Fetch all inventory data
        return view('manager.inventory', compact('inventory')); // Pass data to the view
    }
}
