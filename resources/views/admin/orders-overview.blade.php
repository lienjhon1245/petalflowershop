<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Overview</title>
    <link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="dashboard-container d-flex">
        <!-- Sidebar -->
        <aside class="sidebar bg-primary text-white p-3">
            <div class="sidebar-header text-center mb-4">
                <h3>Admin Panel</h3>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.activity-logs') }}" class="nav-link text-white">Activity Logs</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.account-management') }}" class="nav-link text-white">Account Management</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.orders-overview') }}" class="nav-link text-white">Orders Overview</a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.file-management') }}" class="nav-link text-white">File Management</a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="content w-100">
            <!-- Header -->
            <header class="dashboard-header bg-light p-3 d-flex justify-content-between align-items-center">
                <h1 class="h4">Orders Overview</h1>
                <a href="{{ route('admin.logout') }}" 
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                   class="btn btn-danger">
                    Logout
                </a>
                <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </header>

            <!-- Main Content -->
            <main class="dashboard-main p-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Orders Overview</h5>
                        <p class="card-text">Track and manage recent orders.</p>
                        <button class="btn btn-primary">View Orders</button>
                        <button class="btn btn-secondary">Pending Approvals</button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
