<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

// Check login and view permission
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

requirePermission('view_rooms');

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'delete') {
    $room_id = sanitize($_POST['room_id']);
    
    if ($conn->query("DELETE FROM rooms WHERE id = $room_id")) {
        $message = '<div class="alert alert-success">Room deleted successfully!</div>';
        $action = 'list';
    } else {
        $message = '<div class="alert alert-danger">Error deleting room!</div>';
    }
}

// Handle ADD/UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($action == 'add' || $action == 'edit')) {
    $room_number = sanitize($_POST['room_number']);
    $room_type = sanitize($_POST['room_type']);
    $capacity = sanitize($_POST['capacity']);
    $price = sanitize($_POST['price_per_night']);
    $description = sanitize($_POST['description']);
    $floor = sanitize($_POST['floor']);
    $status = sanitize($_POST['status']);

    if ($action == 'add') {
        $sql = "INSERT INTO rooms (room_number, room_type, capacity, price_per_night, description, floor, status) 
                VALUES ('$room_number', '$room_type', $capacity, $price, '$description', $floor, '$status')";
        $success_msg = 'Room added successfully!';
    } else {
        $room_id = sanitize($_POST['room_id']);
        $sql = "UPDATE rooms SET room_number='$room_number', room_type='$room_type', capacity=$capacity, 
                price_per_night=$price, description='$description', floor=$floor, status='$status' 
                WHERE id=$room_id";
        $success_msg = 'Room updated successfully!';
    }

    if ($conn->query($sql)) {
        $message = '<div class="alert alert-success">' . $success_msg . '</div>';
        $action = 'list';
    } else {
        $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

// Get room for edit
$room = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $room_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM rooms WHERE id = $room_id");
    $room = $result->fetch_assoc();
}

// Get all rooms
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");

include 'views/header.php';
?>

<div class="mb-4">
    <h2><i class="fas fa-door-open"></i> Rooms</h2>
    <hr>
</div>

<?php echo $message; ?>

<?php if ($action == 'list'): ?>
    <div class="mb-3">
        <a href="rooms.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Room
        </a>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">All Rooms</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Room Number</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Price/Night</th>
                            <th>Floor</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($room = $rooms->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $room['room_number']; ?></strong></td>
                            <td><?php echo ucfirst($room['room_type']); ?></td>
                            <td><?php echo $room['capacity']; ?> person(s)</td>
                            <td>$<?php echo number_format($room['price_per_night'], 2); ?></td>
                            <td><?php echo $room['floor']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo getStatusColor($room['status']); ?>">
                                    <?php echo ucfirst($room['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="rooms.php?action=edit&id=<?php echo $room['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="setDeleteRoom(<?php echo $room['id']; ?>, 'Room <?php echo $room['room_number']; ?>')" data-bs-toggle="modal" data-bs-target="#deleteModal">
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
                    Are you sure you want to delete <strong id="deleteRoomName"></strong>?
                    <p class="text-danger small mt-2">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="rooms.php?action=delete" style="display:inline;">
                        <input type="hidden" name="room_id" id="deleteRoomId">
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?php echo ($action == 'add') ? 'Add New Room' : 'Edit Room'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" action="rooms.php?action=<?php echo $action; ?>">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Room Number *</label>
                        <input type="text" class="form-control" name="room_number" value="<?php echo $room['room_number'] ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Room Type *</label>
                        <select class="form-control" name="room_type" required>
                            <option value="">Select Type</option>
                            <option value="single" <?php echo ($room['room_type'] ?? '') == 'single' ? 'selected' : ''; ?>>Single</option>
                            <option value="double" <?php echo ($room['room_type'] ?? '') == 'double' ? 'selected' : ''; ?>>Double</option>
                            <option value="suite" <?php echo ($room['room_type'] ?? '') == 'suite' ? 'selected' : ''; ?>>Suite</option>
                            <option value="deluxe" <?php echo ($room['room_type'] ?? '') == 'deluxe' ? 'selected' : ''; ?>>Deluxe</option>
                            <option value="villa" <?php echo ($room['room_type'] ?? '') == 'villa' ? 'selected' : ''; ?>>Villa</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Capacity (Persons) *</label>
                        <input type="number" class="form-control" name="capacity" value="<?php echo $room['capacity'] ?? ''; ?>" min="1" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Price per Night ($) *</label>
                        <input type="number" class="form-control" name="price_per_night" value="<?php echo $room['price_per_night'] ?? ''; ?>" step="0.01" min="0" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Floor *</label>
                        <input type="number" class="form-control" name="floor" value="<?php echo $room['floor'] ?? ''; ?>" min="1" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-control" name="status" required>
                            <option value="available" <?php echo ($room['status'] ?? '') == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="occupied" <?php echo ($room['status'] ?? '') == 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                            <option value="maintenance" <?php echo ($room['status'] ?? '') == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo $room['description'] ?? ''; ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo ($action == 'add') ? 'Add Room' : 'Update Room'; ?>
                </button>
                <a href="rooms.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
function setDeleteRoom(roomId, roomName) {
    document.getElementById('deleteRoomId').value = roomId;
    document.getElementById('deleteRoomName').textContent = roomName;
}
</script>

<?php include 'views/footer.php'; ?>

<?php
function getStatusColor($status) {
    switch($status) {
        case 'available': return 'success';
        case 'occupied': return 'danger';
        case 'maintenance': return 'warning';
        default: return 'secondary';
    }
}
?>
