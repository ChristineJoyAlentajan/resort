<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Check if user is logged in, if not redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resort Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hotel"></i> Crizel's Resort
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    
                    <?php if (hasPermission('view_rooms')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="rooms.php"><i class="bi bi-door-closed"></i> Rooms</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('view_guests')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="guests.php"><i class="bi bi-people"></i> Guests</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('view_bookings')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php"><i class="bi bi-calendar-check"></i> Bookings</a>
                    </li>
                    <?php endif; ?>

                    <?php if (hasPermission('record_payments')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="payments.php"><i class="bi bi-cash-stack"></i> Payments</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('manage_services')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php"><i class="bi bi-bag-check"></i> Services</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('manage_staff')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php"><i class="bi bi-person"></i> Staff</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('view_reports')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Reports</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item dropdown ms-3">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span class="ms-2"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><span class="dropdown-item" style="color: #666; font-size: 12px;">
                                <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong><br>
                                <?php echo htmlspecialchars($_SESSION['user_email']); ?><br>
                                <span style="text-transform: uppercase; color: #0d6efd; font-weight: 600;">• <?php echo htmlspecialchars($_SESSION['user_role']); ?> •</span>
                            </span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="change-password.php"><i class="bi bi-key"></i> Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-door-open"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
