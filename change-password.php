<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['change_password'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Security token expired. Please try again.";
        } else {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate input
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = "Please fill in all fields.";
            } elseif (strlen($new_password) < 6) {
                $error = "New password must be at least 6 characters long.";
            } elseif ($new_password !== $confirm_password) {
                $error = "New passwords do not match.";
            } else {
                // Get user's current password from database
                $query = "SELECT password FROM users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                // Verify current password
                if (!password_verify($current_password, $user['password'])) {
                    $error = "Current password is incorrect.";
                } else {
                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update password
                    $update_query = "UPDATE users SET password = ? WHERE id = ?";
                    $stmt_update = $conn->prepare($update_query);
                    $stmt_update->bind_param("si", $hashed_password, $user_id);

                    if ($stmt_update->execute()) {
                        $message = "Password changed successfully!";
                        $_POST = array();
                    } else {
                        $error = "Error changing password: " . $conn->error;
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
    <title>Change Password - Resort Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .password-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .password-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 14px;
            position: relative;
        }

        .form-group input:focus {
            border-color: #f5576c;
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
            outline: none;
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

        .password-requirements {
            background-color: #f0f0f0;
            border-left: 4px solid #f5576c;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin-bottom: 5px;
            color: #666;
        }

        .btn-change {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-change:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
            color: white;
        }

        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'views/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-6 mx-auto">
                <div class="password-header">
                    <h1><i class="bi bi-key-fill"></i> Change Password</h1>
                    <p>Update your account password</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="password-card">
                    <div class="password-requirements">
                        <strong><i class="bi bi-info-circle"></i> Password Requirements:</strong>
                        <ul>
                            <li>Minimum 6 characters</li>
                            <li>Use a mix of uppercase and lowercase letters</li>
                            <li>Include numbers and special characters for better security</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <div class="form-group password-group">
                            <label for="current_password">Current Password</label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                class="form-control"
                                placeholder="Enter your current password"
                                required
                            >
                            <button 
                                type="button" 
                                class="password-toggle" 
                                onclick="togglePassword('current_password', 'toggleIcon1')"
                                title="Show/Hide Password"
                            >
                                <i class="bi bi-eye" id="toggleIcon1"></i>
                            </button>
                        </div>

                        <hr>

                        <div class="form-group password-group">
                            <label for="new_password">New Password</label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                class="form-control"
                                placeholder="Enter your new password"
                                onkeyup="checkPasswordStrength()"
                                required
                            >
                            <button 
                                type="button" 
                                class="password-toggle" 
                                onclick="togglePassword('new_password', 'toggleIcon2')"
                                title="Show/Hide Password"
                            >
                                <i class="bi bi-eye" id="toggleIcon2"></i>
                            </button>
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>

                        <div class="form-group password-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-control"
                                placeholder="Re-enter your new password"
                                required
                            >
                            <button 
                                type="button" 
                                class="password-toggle" 
                                onclick="togglePassword('confirm_password', 'toggleIcon3')"
                                title="Show/Hide Password"
                            >
                                <i class="bi bi-eye" id="toggleIcon3"></i>
                            </button>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                <input type="hidden" name="change_password" value="1">
                                <button type="submit" class="btn-change w-100">
                                    <i class="bi bi-check-lg"></i> Change Password
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="profile.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-left"></i> Back to Profile
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid p-4">
    </div>

    <?php include 'views/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
            const password = document.getElementById('new_password').value;
            const strengthDiv = document.getElementById('passwordStrength');

            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }

            let strength = 0;

            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
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

        // Add CSS for password strength styling
        const style = document.createElement('style');
        style.textContent = `
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
        `;
        document.head.appendChild(style);

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('current_password').focus();
        });
    </script>
</body>
</html>
