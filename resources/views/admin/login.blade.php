<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petal Perfection | Admin </title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&family=Great+Vibes&display=swap" rel="stylesheet">
    <style>
        /* General Styling */
        body {
            background-color: #f4f4f9;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(to bottom right, #e8effa, #f4f4f9);
        }

        .login-container {
            background: #ffffff;
            border: 1px ridge #000000;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-radius: 15px 50px 30px; 
            padding: 40px;
            width: 100%;
            max-width: 360px;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .branding {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0px;
            margin-bottom: 24px;
        }

        .branding-logo {
            width: 50px;
        }

        .brand-name {
            font-family: 'Great Vibes', cursive;
            font-size: 32px;
            color: #FFC0CB;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #5c90ff;
            box-shadow: 0 0 10px rgba(92, 144, 255, 0.2);
        }

        .input-group-text {
            background-color: #f4f4f9;
            border-radius: 10px 0 0 10px;
            border: 1px solid #dee2e6;
        }

        .btn-primary {
            background-color: #5c90ff;
            border-color: #5c90ff;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #3c6edb;
            border-color: #3c6edb;
        }

        .error-feedback {
            color: red;
            font-size: 0.85rem;
            text-align: left;
            margin-top: 2px;
            margin-left: 5px;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .footer {
            margin-top: 20px;
            font-size: 0.7rem;
            color: #888;
            text-align: center;
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 20px;
            }

            .brand-name {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>

    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="login-container">
            <!-- Branding -->
            <div class="branding">
                <img src="{{ asset('storage/images/logo.png') }}" alt="Petal Flower Shop Logo" class="branding-logo">
                <span class="brand-name">etal Flower Shop</span>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('admin.login') }}" class="login-form" onsubmit="return validateForm()" novalidate>
                @csrf
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Email" required aria-label="Email">
                    </div>
                    <div class="error-feedback" id="emailError">Please enter a valid email address.</div>
                </div>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required aria-label="Password">
                        <span class="input-group-text toggle-password" onclick="togglePasswordVisibility()" aria-label="Toggle Password Visibility">
                            <i id="toggleIcon" class="bi bi-eye-slash"></i>
                        </span>
                    </div>
                    <div class="error-feedback" id="passwordError">Password is required.</div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>

            <!-- Footer -->
            <div class="footer">
               Copyright © 2024 Petal Perfection
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            }
        }

        function validateForm() {
            let isValid = true;

            const email = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            const password = document.getElementById('password');
            const passwordError = document.getElementById('passwordError');

            // Validate email
            if (!email.value || !email.value.includes('@')) {
                emailError.style.display = 'block';
                isValid = false;
            } else {
                emailError.style.display = 'none';
            }

            // Validate password
            if (!password.value) {
                passwordError.style.display = 'block';
                isValid = false;
            } else {
                passwordError.style.display = 'none';
            }

            return isValid;
        }
    </script>
</body>
</html>
