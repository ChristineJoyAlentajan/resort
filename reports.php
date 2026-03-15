<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

// Check login and permission
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

requirePermission('view_reports');

$report_type = isset($_GET['type']) ? sanitize($_GET['type']) : 'daily';
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');

$daily_income = 0;
$weekly_income = 0;
$monthly_income = 0;
$total_bookings = 0;
$completed_bookings = 0;
$pending_payments = 0;

// Get daily income
$query_daily = "
    SELECT COALESCE(SUM(total_price), 0) as total 
    FROM bookings 
    WHERE DATE(check_in_date) = CURDATE()
";
$result = $conn->query($query_daily);
$daily_income = $result->fetch_assoc()['total'];

// Get weekly income
$query_weekly = "
    SELECT COALESCE(SUM(total_price), 0) as total 
    FROM bookings 
    WHERE WEEK(check_in_date) = WEEK(CURDATE())
";
$result = $conn->query($query_weekly);
$weekly_income = $result->fetch_assoc()['total'];

// Get monthly income
$query_monthly = "
    SELECT COALESCE(SUM(total_price), 0) as total 
    FROM bookings 
    WHERE MONTH(check_in_date) = MONTH(CURDATE())
    AND YEAR(check_in_date) = YEAR(CURDATE())
";
$result = $conn->query($query_monthly);
$monthly_income = $result->fetch_assoc()['total'];

// Get total bookings
$query_bookings = "
    SELECT COUNT(*) as total 
    FROM bookings 
    WHERE DATE(check_in_date) BETWEEN '$start_date' AND '$end_date'
";
$result = $conn->query($query_bookings);
$total_bookings = $result->fetch_assoc()['total'];

// Get completed bookings
$query_completed = "
    SELECT COUNT(*) as total 
    FROM bookings 
    WHERE DATE(check_in_date) BETWEEN '$start_date' AND '$end_date'
    AND status = 'checked-out'
";
$result = $conn->query($query_completed);
$completed_bookings = $result->fetch_assoc()['total'];

// Get pending payments
$query_pending = "
    SELECT COUNT(*) as total 
    FROM bookings 
    WHERE payment_status = 'pending'
";
$result = $conn->query($query_pending);
$pending_payments = $result->fetch_assoc()['total'];

// Get detailed booking reports
$query_details = "
    SELECT b.*, g.first_name, g.last_name, r.room_number 
    FROM bookings b
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    WHERE DATE(b.check_in_date) BETWEEN '$start_date' AND '$end_date'
    ORDER BY b.check_in_date DESC
";
$bookings_report = $conn->query($query_details);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Resort Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-change {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #667eea;
            font-weight: 600;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        table {
            margin-bottom: 0;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge-custom {
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'views/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1><i class="bi bi-graph-up"></i> Reports & Analytics</h1>
                <p class="text-muted">View detailed reports and performance metrics</p>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="bi bi-cash-coin"></i> Daily Income</h3>
                    <div class="stat-value">₱<?php echo number_format($daily_income, 2); ?></div>
                    <div class="stat-change">Today</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="bi bi-calendar-check"></i> Weekly Income</h3>
                    <div class="stat-value">₱<?php echo number_format($weekly_income, 2); ?></div>
                    <div class="stat-change">This week</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="bi bi-calendar2-month"></i> Monthly Income</h3>
                    <div class="stat-value">₱<?php echo number_format($monthly_income, 2); ?></div>
                    <div class="stat-change">This month</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><i class="bi bi-exclamation-circle"></i> Pending Payments</h3>
                    <div class="stat-value" style="color: #ff6b6b;"><?php echo $pending_payments; ?></div>
                    <div class="stat-change">Awaiting payment</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input 
                        type="date" 
                        id="start_date" 
                        name="start_date" 
                        class="form-control"
                        value="<?php echo $start_date; ?>"
                    >
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input 
                        type="date" 
                        id="end_date" 
                        name="end_date" 
                        class="form-control"
                        value="<?php echo $end_date; ?>"
                    >
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Report Type</label>
                    <select id="type" name="type" class="form-control">
                        <option value="daily" <?php echo ($report_type === 'daily') ? 'selected' : ''; ?>>Daily</option>
                        <option value="weekly" <?php echo ($report_type === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                        <option value="monthly" <?php echo ($report_type === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary form-control">
                        <i class="bi bi-search"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Booking Summary -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-list-check"></i> Booking Summary (<?php echo $total_bookings; ?> Bookings)
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Total Bookings</h6>
                                <p style="font-size: 22px; color: #667eea; font-weight: bold;">
                                    <?php echo $total_bookings; ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h6>Completed Bookings</h6>
                                <p style="font-size: 22px; color: #28a745; font-weight: bold;">
                                    <?php echo $completed_bookings; ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h6>Completion Rate</h6>
                                <p style="font-size: 22px; color: #ff9800; font-weight: bold;">
                                    <?php echo ($total_bookings > 0) ? round(($completed_bookings / $total_bookings) * 100, 1) : 0; ?>%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Bookings Report -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-file-text"></i> Detailed Bookings Report
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Booking #</th>
                                    <th>Guest Name</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($booking = $bookings_report->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                    <td><strong>₱<?php echo number_format($booking['total_price'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $status_colors = [
                                            'confirmed' => 'info',
                                            'checked-in' => 'warning',
                                            'checked-out' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $color = isset($status_colors[$booking['status']]) ? $status_colors[$booking['status']] : 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo ucfirst(str_replace('-', ' ', $booking['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $payment_colors = ['pending' => 'warning', 'paid' => 'success', 'refunded' => 'danger'];
                                        $p_color = isset($payment_colors[$booking['payment_status']]) ? $payment_colors[$booking['payment_status']] : 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $p_color; ?>">
                                            <?php echo ucfirst($booking['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid p-4"></div>
    <?php include 'views/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
