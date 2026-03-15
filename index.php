<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get statistics
$rooms_count = $conn->query("SELECT COUNT(*) as total FROM rooms")->fetch_assoc()['total'];
$guests_count = $conn->query("SELECT COUNT(*) as total FROM guests")->fetch_assoc()['total'];
$bookings_count = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'];
$available_rooms = $conn->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available'")->fetch_assoc()['total'];

// Get recent bookings
$recent_bookings = $conn->query("
    SELECT b.*, g.first_name, g.last_name, r.room_number 
    FROM bookings b
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC 
    LIMIT 5
");

// Get upcoming check-ins
$upcoming_checkins = $conn->query("
    SELECT b.*, g.first_name, g.last_name, r.room_number, r.room_type
    FROM bookings b
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    WHERE DATE(b.check_in_date) >= CURDATE()
    AND b.status IN ('confirmed', 'checked-in')
    ORDER BY b.check_in_date ASC
    LIMIT 5
");
?>


<style>
body{
    background-image: url('resort.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
}

.card{
    background: rgba(255,255,255,0.9);
}
</style>


<?php include 'views/header.php'; ?>
<style>
/* Make cards transparent */
.card{
    background: transparent !important;
    border: none;
    box-shadow: none;
}

/* Make table background transparent */
.table{
    background: transparent;
}

/* Optional: make container transparent */
.container, .row, .col-md-6, .col-md-3{
    backdrop-filter: blur(5px); /* blur strength */
    -webkit-backdrop-filter: blur(12px); /* for Safari */
}
</style>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Rooms</h6>
                        <h2><?php echo $rooms_count; ?></h2>
                    </div>
                    <i class="fas fa-door-open fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Available Rooms</h6>
                        <h2><?php echo $available_rooms; ?></h2>
                    </div>
                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Guests</h6>
                        <h2><?php echo $guests_count; ?></h2>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Bookings</h6>
                        <h2><?php echo $bookings_count; ?></h2>
                    </div>
                    <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Recent Bookings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($booking = $recent_bookings->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $booking['first_name'] . ' ' . $booking['last_name']; ?></td>
                                <td><?php echo $booking['room_number']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($booking['check_in_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getBadgeColor($booking['status']); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <a href="bookings.php" class="btn btn-primary btn-sm">View All Bookings</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Upcoming Check-ins</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Check-in Date</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($checkin = $upcoming_checkins->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $checkin['first_name'] . ' ' . $checkin['last_name']; ?></td>
                                <td><?php echo $checkin['room_number']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($checkin['check_in_date'])); ?></td>
                                <td><?php echo ucfirst($checkin['room_type']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <a href="bookings.php" class="btn btn-success btn-sm">Manage Bookings</a>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>

<?php
function getBadgeColor($status) {
    switch($status) {
        case 'confirmed': return 'info';
        case 'checked-in': return 'success';
        case 'checked-out': return 'secondary';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>
