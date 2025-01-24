<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Custom Styling */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            background-color: #007bff;
            color: #fff;
            padding: 20px;
        }

        .sidebar-header h3 {
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .nav-item {
            margin-bottom: 15px;
        }

        .nav-link {
            text-decoration: none;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            display: block;
            transition: background-color 0.3s;
        }

        .nav-link:hover {
            background-color: #0056b3;
        }

        /* Content Area */
        .content {
            flex-grow: 1;
            padding: 20px;
        }

        .dashboard-header {
            background-color: #f1f1f1;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2rem;
            color: #007bff;
            margin-bottom: 20px;
        }

        .btn-primary {
            transition: background-color 0.3s, box-shadow 0.3s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
        }

        .card {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Manager Panel</h3>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="{{ route('manager.dashboard') }}" class="nav-link">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('manager.inventory') }}" class="nav-link">Manage Inventory</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('manager.sales-reports') }}" class="nav-link">Sales Reports</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('manager.attendance-reports') }}" class="nav-link">Attendance Reports</a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="content">
            <header class="dashboard-header">
                <h1>Manage Inventory</h1>
            </header>
            <main>
                <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addItemModal">Add New Item</button>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Inventory Items</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Stock Quantity</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inventory as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->category }}</td>
                                        <td>{{ $item->stock_quantity }}</td>
                                        <td>${{ number_format($item->price, 2) }}</td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editItemModal" data-id="{{ $item->id }}" data-name="{{ $item->name }}" data-category="{{ $item->category }}" data-quantity="{{ $item->stock_quantity }}" data-price="{{ $item->price }}">Edit</button>
                                            <form action="{{ route('inventory.destroy', $item) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No inventory items found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>
