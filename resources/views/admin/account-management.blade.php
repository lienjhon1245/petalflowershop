<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management</title>
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
                <h1 class="h4">Account Management</h1>
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
                <div class="row">
                    <div class="col-lg-12">
                        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addManagerModal">Add New Manager</button>

                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Managers</h5>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Fullname</th>
                                            <th>Email</th>
                                            <th>Date of Birth</th>
                                            <th>Gender</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($managers as $manager)
                                            <tr>
                                                <td>{{ $manager->fullname }}</td>
                                                <td>{{ $manager->email }}</td>
                                                <td>{{ $manager->date_of_birth }}</td>
                                                <td>{{ ucfirst($manager->gender) }}</td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editManagerModal" data-id="{{ $manager->id }}" data-fullname="{{ $manager->fullname }}" data-email="{{ $manager->email }}" data-dob="{{ $manager->date_of_birth }}" data-gender="{{ $manager->gender }}">Edit</button>
                                                    <form action="{{ route('managers.destroy', $manager) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('Are you sure you want to delete this manager?')">Delete</button>
                                                    </form>
                                                    <form action="{{ route('managers.ban', $manager) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        <button class="btn btn-warning btn-sm" type="submit" onclick="return confirm('Are you sure you want to ban this manager?')">Ban</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>


    <!-- Add Manager Modal -->
    <div class="modal fade" id="addManagerModal" tabindex="-1" aria-labelledby="addManagerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addManagerModalLabel">Add New Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.account-management.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Fullname</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                        </div>
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Manager Modal -->
    <div class="modal fade" id="editManagerModal" tabindex="-1" aria-labelledby="editManagerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editManagerModalLabel">Edit Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('managers.update', 0) }}" id="editManagerForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" id="edit-manager-id" name="id">
                        <div class="mb-3">
                            <label for="edit-fullname" class="form-label">Fullname</label>
                            <input type="text" class="form-control" id="edit-fullname" name="fullname" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit-email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-date-of-birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="edit-date-of-birth" name="date_of_birth" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-gender" class="form-label">Gender</label>
                            <select class="form-control" id="edit-gender" name="gender" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editManagerModal = document.getElementById('editManagerModal');
            const editManagerForm = document.getElementById('editManagerForm');
            const editManagerId = document.getElementById('edit-manager-id');
            const editFullname = document.getElementById('edit-fullname');
            const editEmail = document.getElementById('edit-email');
            const editDateOfBirth = document.getElementById('edit-date-of-birth');
            const editGender = document.getElementById('edit-gender');
    
            editManagerModal.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const fullname = button.getAttribute('data-fullname');
                const email = button.getAttribute('data-email');
                const dob = button.getAttribute('data-dob');
                const gender = button.getAttribute('data-gender');
    
                editManagerForm.action = `/admin/managers/${id}`;
                editManagerId.value = id;
                editFullname.value = fullname;
                editEmail.value = email;
                editDateOfBirth.value = dob;
                editGender.value = gender;
            });
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    

</body>
</html>
