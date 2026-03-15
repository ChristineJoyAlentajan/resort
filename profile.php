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

// Fetch user data
$query = "SELECT * FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Security token expired. Please try again.";
        } else {
            $name = sanitize($_POST['name']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);

            // Check if email is already taken by another user
            $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt_check = $conn->prepare($check_email);
            $stmt_check->bind_param("si", $email, $user_id);
            $stmt_check->execute();
            $check_result = $stmt_check->get_result();

            if ($check_result->num_rows > 0) {
                $error = "Email address already taken.";
            } else {
                // Update user profile
                $update_query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
                $stmt_update = $conn->prepare($update_query);
                $stmt_update->bind_param("sssi", $name, $email, $phone, $user_id);

                if ($stmt_update->execute()) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $message = "Profile updated successfully!";
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $user['phone'] = $phone;
                } else {
                    $error = "Error updating profile: " . $conn->error;
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
    <title>My Profile - Resort Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background-color: rgba(255, 255, 255, 0.3);
            border: 3px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
        }

        .profile-info {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .info-row {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            min-width: 150px;
        }

        .info-value {
            color: #666;
            flex: 1;
        }

        .role-badge {
            display: inline-block;
            padding: 8px 15px;
            background-color: #667eea;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        }

        .form-group input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .btn-update {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
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
            <div class="col-lg-8 mx-auto">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                    <span class="role-badge"><?php echo htmlspecialchars($user['role']); ?></span>
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

                <div class="profile-info">
                    <h3 class="mb-4"><i class="bi bi-pencil-square"></i> Edit Profile</h3>

                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-control"
                                value="<?php echo htmlspecialchars($user['name']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control"
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="form-control"
                                value="<?php echo htmlspecialchars($user['phone']); ?>"
                            >
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                <input type="hidden" name="update_profile" value="1">
                                <button type="submit" class="btn-update">
                                    <i class="bi bi-check-lg"></i> Update Profile
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="change-password.php" class="btn btn-outline-primary">
                                    <i class="bi bi-key"></i> Change Password
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
</body>
</html>
