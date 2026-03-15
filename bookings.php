<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

// Check login and view permission
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

requirePermission('view_bookings');

$action = $_GET['action'] ?? 'list';
$message = '';

// Generate booking number
function generateBookingNumber() {
    return 'BK' . date('YmdHis') . rand(1000, 9999);
}

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'delete') {
    $booking_id = sanitize($_POST['booking_id']);
    
    if ($conn->query("DELETE FROM bookings WHERE id = $booking_id")) {
        $message = '<div class="alert alert-success">Booking deleted successfully!</div>';
        $action = 'list';
    } else {
        $message = '<div class="alert alert-danger">Error deleting booking!</div>';
    }
}

// Handle ADD/UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($action == 'add' || $action == 'edit')) {
    $guest_id = sanitize($_POST['guest_id']);
    $room_id = sanitize($_POST['room_id']);
    $check_in = sanitize($_POST['check_in_date']);
    $check_out = sanitize($_POST['check_out_date']);
    $num_guests = sanitize($_POST['number_of_guests']);
    $status = sanitize($_POST['status']);
    $payment_status = sanitize($_POST['payment_status']);
    $special_requests = sanitize($_POST['special_requests']);

    // Check if room is already booked for the selected dates
    $booking_id = $action == 'edit' ? sanitize($_POST['booking_id']) : null;
    $exclude_condition = $booking_id ? " AND id != $booking_id" : "";
    
    $conflict_query = "SELECT COUNT(*) as count FROM bookings 
                       WHERE room_id = $room_id 
                       AND status != 'cancelled'
                       AND (
                           (check_in_date < '$check_out' AND check_out_date > '$check_in')
                       )
                       $exclude_condition";
    
    $conflict_result = $conn->query($conflict_query);
    $conflict_data = $conflict_result->fetch_assoc();
    
    if ($conflict_data['count'] > 0) {
        $message = '<div class="alert alert-danger">Error: This room is already booked for the selected dates!</div>';
    } else {
        // Calculate number of nights and total price
        $checkin_date = new DateTime($check_in);
        $checkout_date = new DateTime($check_out);
        $nights = $checkout_date->diff($checkin_date)->days;
        
        // Validate nights
        if ($nights <= 0) {
            $message = '<div class="alert alert-danger">Error: Check-out date must be after check-in date!</div>';
        } else {
            // Get room price
            $room_result = $conn->query("SELECT price_per_night FROM rooms WHERE id = $room_id");
            $room_data = $room_result->fetch_assoc();
            $total_price = $room_data['price_per_night'] * $nights;

            if ($action == 'add') {
                $booking_number = generateBookingNumber();
                $sql = "INSERT INTO bookings (booking_number, guest_id, room_id, check_in_date, check_out_date, number_of_guests, total_price, status, payment_status, special_requests) 
                        VALUES ('$booking_number', $guest_id, $room_id, '$check_in', '$check_out', $num_guests, $total_price, '$status', '$payment_status', '$special_requests')";
                $success_msg = 'Booking created successfully!';
            } else {
                $sql = "UPDATE bookings SET guest_id=$guest_id, room_id=$room_id, check_in_date='$check_in', 
                        check_out_date='$check_out', number_of_guests=$num_guests, total_price=$total_price, 
                        status='$status', payment_status='$payment_status', special_requests='$special_requests' 
                        WHERE id=$booking_id";
                $success_msg = 'Booking updated successfully!';
            }

            if ($conn->query($sql)) {
                $message = '<div class="alert alert-success">' . $success_msg . '</div>';
                $action = 'list';
            } else {
                $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
            }
        }
    }
}

// Get booking for edit
$booking = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM bookings WHERE id = $booking_id");
    $booking = $result->fetch_assoc();
}

// Get all bookings with guest and room info
$bookings = $conn->query("
    SELECT b.*, g.first_name, g.last_name, r.room_number, r.room_type
    FROM bookings b
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC
");

// Get guests and rooms for dropdown
$guests = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM guests ORDER BY first_name");
$rooms = $conn->query("SELECT id, room_number, room_type, price_per_night FROM rooms WHERE status != 'maintenance' ORDER BY room_number");

include 'views/header.php';
?>

<div class="mb-4">
    <h2><i class="fas fa-calendar-alt"></i> Booking</h2>
    <hr>
</div>

<?php echo $message; ?>

<?php if ($action == 'list'): ?>
    <div class="mb-3">
        <a href="bookings.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Booking
        </a>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">All Bookings</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Booking #</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($book = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $book['booking_number']; ?></strong></td>
                            <td><?php echo $book['first_name'] . ' ' . $book['last_name']; ?></td>
                            <td><?php echo $book['room_number'] . ' (' . ucfirst($book['room_type']) . ')'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($book['check_in_date'])); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($book['check_out_date'])); ?></td>
                            <td>$<?php echo number_format($book['total_price'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getStatusColor($book['status']); ?>">
                                    <?php echo ucfirst(str_replace('-', ' ', $book['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo getPaymentColor($book['payment_status']); ?>">
                                    <?php echo ucfirst($book['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="bookings.php?action=edit&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="setDeleteBooking(<?php echo $book['id']; ?>, '<?php echo $book['booking_number']; ?>')" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Reusable Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    Are you sure you want to delete booking <strong id="deleteBookingName"></strong>?
                    <p class="text-danger small mt-2">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="bookings.php?action=delete" style="display:inline;">
                        <input type="hidden" name="booking_id" id="deleteBookingId">
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?php echo ($action == 'add') ? 'Create New Booking' : 'Edit Booking'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" action="bookings.php?action=<?php echo $action; ?>">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Guest *</label>
                        <select class="form-control" name="guest_id" required>
                            <option value="">Select Guest</option>
                            <?php 
                            $guests->data_seek(0);
                            while($g = $guests->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $g['id']; ?>" <?php echo ($booking['guest_id'] ?? '') == $g['id'] ? 'selected' : ''; ?>>
                                <?php echo $g['name']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Room *</label>
                        <select class="form-control" name="room_id" required>
                            <option value="">Select Room</option>
                            <?php 
                            $rooms->data_seek(0);
                            while($r = $rooms->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo ($booking['room_id'] ?? '') == $r['id'] ? 'selected' : ''; ?>>
                                <?php echo $r['room_number'] . ' (' . ucfirst($r['room_type']) . ') - $' . $r['price_per_night'] . '/night'; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-in Date *</label>
                        <input type="date" class="form-control" name="check_in_date" value="<?php echo $booking['check_in_date'] ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-out Date *</label>
                        <input type="date" class="form-control" name="check_out_date" value="<?php echo $booking['check_out_date'] ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Number of Guests *</label>
                        <input type="number" class="form-control" name="number_of_guests" value="<?php echo $booking['number_of_guests'] ?? '1'; ?>" min="1" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Booking Status *</label>
                        <select class="form-control" name="status" required>
                            <option value="confirmed" <?php echo ($booking['status'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="checked-in" <?php echo ($booking['status'] ?? '') == 'checked-in' ? 'selected' : ''; ?>>Checked-in</option>
                            <option value="checked-out" <?php echo ($booking['status'] ?? '') == 'checked-out' ? 'selected' : ''; ?>>Checked-out</option>
                            <option value="cancelled" <?php echo ($booking['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Payment Status *</label>
                        <select class="form-control" name="payment_status" required>
                            <option value="pending" <?php echo ($booking['payment_status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo ($booking['payment_status'] ?? '') == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="refunded" <?php echo ($booking['payment_status'] ?? '') == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Special Requests</label>
                        <textarea class="form-control" name="special_requests" rows="3"><?php echo $booking['special_requests'] ?? ''; ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo ($action == 'add') ? 'Create Booking' : 'Update Booking'; ?>
                </button>
                <a href="bookings.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
function setDeleteBooking(bookingId, bookingName) {
    document.getElementById('deleteBookingId').value = bookingId;
    document.getElementById('deleteBookingName').textContent = bookingName;
}
</script>

<?php include 'views/footer.php'; ?>

<?php
function getStatusColor($status) {
    switch($status) {
        case 'confirmed': return 'info';
        case 'checked-in': return 'success';
        case 'checked-out': return 'secondary';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getPaymentColor($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'paid': return 'success';
        case 'refunded': return 'secondary';
        default: return 'secondary';
    }
}
?>
