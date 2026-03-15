<?php
session_start();
require_once 'config/db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register_submit'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Security token expired. Please try again.";
        } else {
            $name = sanitize($_POST['name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];
            $role = sanitize($_POST['role']);

            // Validate input
            if (empty($name) || empty($email) || empty($password) || empty($password_confirm) || empty($role)) {
                $error = "Please fill in all required fields.";
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters long.";
            } elseif ($password !== $password_confirm) {
                $error = "Passwords do not match.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address.";
            } else {
                // Check if email already exists
                $check_email = "SELECT id FROM users WHERE email = '$email'";
                $result = $conn->query($check_email);

                if ($result && $result->num_rows > 0) {
                    $error = "Email address already registered.";
                } else {
                    // Validate role
                    $allowed_roles = ['admin', 'manager', 'staff'];
                    if (!in_array($role, $allowed_roles)) {
                        $error = "Invalid role selected.";
                    } else {
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Insert new user
                        $insert_query = "INSERT INTO users (name, email, password, phone, role, status) 
                                        VALUES ('$name', '$email', '$hashed_password', '$phone', '$role', 'active')";

                        if ($conn->query($insert_query) === TRUE) {
                            $success = "Account created successfully! <a href='login.php'>Click here to login</a>";
                            // Clear form fields
                            $_POST = array();
                        } else {
                            $error = "Error creating account: " . $conn->error;
                        }
                    }
                }
            }
        }
    }
}

generateToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Resort Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }

        .register-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .register-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .register-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .register-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .register-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #f5576c;
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
        }

        .password-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 42px;
            cursor: pointer;
            color: #f5576c;
            border: none;
            background: none;
            font-size: 18px;
            padding: 0;
        }

        .password-toggle:hover {
            color: #d4314f;
        }

        .password-toggle:focus {
            outline: none;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-success a {
            color: #155724;
            font-weight: 600;
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
            color: white;
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .register-footer {
            text-align: center;
            padding: 20px 40px;
            background-color: #f9f9f9;
            border-top: 1px solid #e0e0e0;
        }

        .register-footer p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }

        .register-footer a {
            color: #f5576c;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .register-footer a:hover {
            color: #d4314f;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            color: #999;
        }

        .password-strength.weak {
            color: #dc3545;
        }

        .password-strength.fair {
            color: #ffc107;
        }

        .password-strength.good {
            color: #28a745;
        }

        .required {
            color: #f5576c;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 10px;
            }

            .register-body {
                padding: 25px;
            }

            .register-header {
                padding: 30px 20px;
            }

            .register-footer {
                padding: 15px 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1><i class="bi bi-person-plus"></i> Create Account</h1>
                <p>Join the Resort Management Team</p>
            </div>

            <div class="register-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            placeholder="Enter your full name"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                            required
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                placeholder="your.email@example.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                placeholder="+1-800-000-0000"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role">User Role <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <option value="">-- Select Role --</option>
                            <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
                            <option value="manager" <?php echo (isset($_POST['role']) && $_POST['role'] === 'manager') ? 'selected' : ''; ?>>Manager</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-group password-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="At least 6 characters"
                            onkeyup="checkPasswordStrength()"
                            required
                        >
                        <button 
                            type="button" 
                            class="password-toggle" 
                            onclick="togglePassword('password', 'toggleIcon1')"
                            title="Show/Hide Password"
                        >
                            <i class="bi bi-eye" id="toggleIcon1"></i>
                        </button>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>

                    <div class="form-group password-group">
                        <label for="password_confirm">Confirm Password <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            placeholder="Re-enter your password"
                            required
                        >
                        <button 
                            type="button" 
                            class="password-toggle" 
                            onclick="togglePassword('password_confirm', 'toggleIcon2')"
                            title="Show/Hide Password"
                        >
                            <i class="bi bi-eye" id="toggleIcon2"></i>
                        </button>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                    <input type="hidden" name="register_submit" value="1">

                    <button type="submit" class="btn-register">
                        <i class="bi bi-person-check"></i> Create Account
                    </button>
                </form>
            </div>

            <div class="register-footer">
                <p>Already have an account? <a href="login.php">Login Here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('passwordStrength');

            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }

            let strength = 0;

            // Check length
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;

            // Check for uppercase
            if (/[A-Z]/.test(password)) strength++;

            // Check for lowercase
            if (/[a-z]/.test(password)) strength++;

            // Check for numbers
            if (/[0-9]/.test(password)) strength++;

            // Check for special characters
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;

            let strengthText = '';
            let strengthClass = '';

            if (strength <= 2) {
                strengthText = '⚠ Weak password';
                strengthClass = 'weak';
            } else if (strength <= 4) {
                strengthText = '• Fair password';
                strengthClass = 'fair';
            } else {
                strengthText = '✓ Strong password';
                strengthClass = 'good';
            }

            strengthDiv.textContent = strengthText;
            strengthDiv.className = 'password-strength ' + strengthClass;
        }

        // Auto-focus name field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('name').focus();
        });
    </script>
</body>
</html>
