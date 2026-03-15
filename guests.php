<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

// Check login and view permission
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

requirePermission('view_guests');

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'delete') {
    $guest_id = sanitize($_POST['guest_id']);
    
    if ($conn->query("DELETE FROM guests WHERE id = $guest_id")) {
        $message = '<div class="alert alert-success">Guest deleted successfully!</div>';
        $action = 'list';
    } else {
        $message = '<div class="alert alert-danger">Error deleting guest!</div>';
    }
}

// Handle ADD/UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($action == 'add' || $action == 'edit')) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $country = sanitize($_POST['country']);
    $id_type = sanitize($_POST['id_type']);
    $id_number = sanitize($_POST['id_number']);

    if ($action == 'add') {
        $sql = "INSERT INTO guests (first_name, last_name, email, phone, address, city, country, id_type, id_number) 
                VALUES ('$first_name', '$last_name', '$email', '$phone', '$address', '$city', '$country', '$id_type', '$id_number')";
        $success_msg = 'Guest added successfully!';
    } else {
        $guest_id = sanitize($_POST['guest_id']);
        $sql = "UPDATE guests SET first_name='$first_name', last_name='$last_name', email='$email', 
                phone='$phone', address='$address', city='$city', country='$country', 
                id_type='$id_type', id_number='$id_number' WHERE id=$guest_id";
        $success_msg = 'Guest updated successfully!';
    }

    if ($conn->query($sql)) {
        $message = '<div class="alert alert-success">' . $success_msg . '</div>';
        $action = 'list';
    } else {
        $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

// Get guest for edit
$guest = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $guest_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM guests WHERE id = $guest_id");
    $guest = $result->fetch_assoc();
}

// Get all guests
$guests = $conn->query("SELECT * FROM guests ORDER BY first_name ASC");

include 'views/header.php';
?>

<div class="mb-4">
    <h2><i class="fas fa-users"></i> Guests </h2>
    <hr>
</div>

<?php echo $message; ?>

<?php if ($action == 'list'): ?>
    <div class="mb-3">
        <a href="guests.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Guest
        </a>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">All Guests</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>Country</th>
                            <th>ID Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($guest = $guests->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $guest['first_name'] . ' ' . $guest['last_name']; ?></strong></td>
                            <td><?php echo $guest['email'] ?? 'N/A'; ?></td>
                            <td><?php echo $guest['phone'] ?? 'N/A'; ?></td>
                            <td><?php echo $guest['city'] ?? 'N/A'; ?></td>
                            <td><?php echo $guest['country'] ?? 'N/A'; ?></td>
                            <td><?php echo $guest['id_type'] ?? 'N/A'; ?></td>
                            <td>
                                <a href="guests.php?action=edit&id=<?php echo $guest['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="setDeleteGuest(<?php echo $guest['id']; ?>, '<?php echo $guest['first_name'] . ' ' . $guest['last_name']; ?>')" data-bs-toggle="modal" data-bs-target="#deleteModal">
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
                    Are you sure you want to delete <strong id="deleteGuestName"></strong>?
                    <p class="text-danger small mt-2">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="guests.php?action=delete" style="display:inline;">
                        <input type="hidden" name="guest_id" id="deleteGuestId">
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?php echo ($action == 'add') ? 'Add New Guest' : 'Edit Guest'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" action="guests.php?action=<?php echo $action; ?>">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="guest_id" value="<?php echo $guest['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control" name="first_name" value="<?php echo $guest['first_name'] ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control" name="last_name" value="<?php echo $guest['last_name'] ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo $guest['email'] ?? ''; ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo $guest['phone'] ?? ''; ?>">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" value="<?php echo $guest['address'] ?? ''; ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city" value="<?php echo $guest['city'] ?? ''; ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Country</label>
                        <input type="text" class="form-control" name="country" value="<?php echo $guest['country'] ?? ''; ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Type</label>
                        <select class="form-control" name="id_type">
                            <option value="">Select Type</option>
                            <option value="passport" <?php echo ($guest['id_type'] ?? '') == 'passport' ? 'selected' : ''; ?>>Passport</option>
                            <option value="driver_license" <?php echo ($guest['id_type'] ?? '') == 'driver_license' ? 'selected' : ''; ?>>Driver License</option>
                            <option value="national_id" <?php echo ($guest['id_type'] ?? '') == 'national_id' ? 'selected' : ''; ?>>National ID</option>
                            <option value="other" <?php echo ($guest['id_type'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Number</label>
                        <input type="text" class="form-control" name="id_number" value="<?php echo $guest['id_number'] ?? ''; ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo ($action == 'add') ? 'Add Guest' : 'Update Guest'; ?>
                </button>
                <a href="guests.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
function setDeleteGuest(guestId, guestName) {
    document.getElementById('deleteGuestId').value = guestId;
    document.getElementById('deleteGuestName').textContent = guestName;
}
</script>

<?php include 'views/footer.php'; ?>
