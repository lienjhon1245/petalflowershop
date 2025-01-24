<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Dashboard</title>
  <link rel="stylesheet" href="{{ asset('css/manager/dashboard.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    /* General Styling */
    body {
        margin: 0;
        font-family: 'Arial', sans-serif;
        background-color: #f8f9fa;
    }

    /* Sidebar Styling */
    .sidebar {
        width: 250px;
        min-height: 100vh;
        background-color: #007bff;
        color: #fff;
        position: fixed;
        top: 0;
        left: 0;
    }

    .sidebar-header h3 {
        font-size: 1.5rem;
        font-weight: bold;
    }

    .nav-item a {
        color: #fff;
        text-decoration: none;
        padding: 10px 15px;
        display: block;
        border-radius: 5px;
        transition: background-color 0.3s;
    }

    .nav-item a:hover {
        background-color: #0056b3;
    }

    /* Header Styling */
    .dashboard-header {
        margin-left: 250px; /* Adjust to match sidebar width */
        padding: 10px 20px;
        background-color: #f1f1f1;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .dashboard-header h1 {
        margin: 0;
        font-size: 1.25rem;
    }

    /* Main Content Styling */
    .dashboard-main {
        margin-left: 250px; /* Match sidebar width */
        padding: 20px;
        background-color: #fff;
        min-height: calc(100vh - 60px); /* Adjust based on header height */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    /* Button Styling */
    .btn-danger {
        color: #fff;
        background-color: #dc3545;
        border-color: #dc3545;
        border-radius: 4px;
        transition: background-color 0.3s, box-shadow 0.3s;
    }

    .btn-danger:hover {
        background-color: #bd2130;
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
        }

        .dashboard-header {
            margin-left: 0;
        }

        .dashboard-main {
            margin-left: 0;
        }
    }
  </style>
</head>
<body>
<div class="dashboard-container d-flex">
    <!-- Sidebar -->
    <aside class="sidebar bg-primary text-white p-3">
        <div class="sidebar-header text-center mb-4">
            <h3>Manager Panel</h3>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a href="{{ route('manager.inventory') }}">Manage Inventory</a>
            </li>
            <li class="nav-item mb-2">
                <a href="{{ route('manager.sales-reports') }}">Sales Reports</a>
            </li>
            <li class="nav-item mb-2">
                <a href="{{ route('manager.attendance-reports') }}">Attendance Reports</a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="content w-100">
        <!-- Header -->
        <header class="dashboard-header bg-light p-3 d-flex justify-content-between align-items-center">
            <h1 class="h4">Welcome, Manager!</h1>
            <a href="{{ route('manager.logout') }}" 
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
               class="btn btn-danger">
                Logout
            </a>
            <form id="logout-form" action="{{ route('manager.logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </header>

        <!-- Main Content -->
        <main class="dashboard-main p-4">
            <div class="row g-4">
                <!-- Content goes here -->
            </div>
        </main>
    </div>
</div>
</body>
</html>
