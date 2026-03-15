<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Resort Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .error-container {
            text-align: center;
            color: white;
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .error-title {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .error-message {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .error-description {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid white;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .btn-back {
            background: white;
            color: #667eea;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            color: #667eea;
        }

        .btn-logout {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: transform 0.2s, background 0.2s;
            border: 1px solid white;
            margin-left: 10px;
            cursor: pointer;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="bi bi-shield-exclamation"></i>
        </div>

        <h1 class="error-title">Access Denied</h1>
        <p class="error-message">You don't have permission to access this page.</p>

        <div class="error-description">
            <p style="margin: 0;">
                Your current role <strong>(<?php echo htmlspecialchars(ucfirst($_SESSION['user_role'])); ?>)</strong> 
                does not have the required permissions to view this resource.
            </p>
        </div>

        <div class="user-info">
            <p style="margin: 0; font-weight: 600;">Current User Information</p>
            <p style="margin: 5px 0 0 0;">
                <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
                (<?php echo htmlspecialchars($_SESSION['user_email']); ?>)
            </p>
        </div>

        <div>
            <a href="index.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <a href="logout.php" class="btn-logout">
                <i class="bi bi-door-open"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>
