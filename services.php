<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

// Check login and require admin permission
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

requirePermission('manage_services');

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'delete') {
    $service_id = sanitize($_POST['service_id']);
    
    if ($conn->query("DELETE FROM services WHERE id = $service_id")) {
        $message = '<div class="alert alert-success">Service deleted successfully!</div>';
        $action = 'list';
    } else {
        $message = '<div class="alert alert-danger">Error deleting service!</div>';
    }
}

// Handle ADD/UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($action == 'add' || $action == 'edit')) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = sanitize($_POST['price']);
    $status = sanitize($_POST['status']);

    if ($action == 'add') {
        $sql = "INSERT INTO services (name, description, price, status) 
                VALUES ('$name', '$description', $price, '$status')";
        $success_msg = 'Service added successfully!';
    } else {
        $service_id = sanitize($_POST['service_id']);
        $sql = "UPDATE services SET name='$name', description='$description', price=$price, status='$status' 
                WHERE id=$service_id";
        $success_msg = 'Service updated successfully!';
    }

    if ($conn->query($sql)) {
        $message = '<div class="alert alert-success">' . $success_msg . '</div>';
        $action = 'list';
    } else {
        $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
    }
}

// Get service for edit
$service = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $service_id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM services WHERE id = $service_id");
    $service = $result->fetch_assoc();
}

// Get all services
$services = $conn->query("SELECT * FROM services ORDER BY name ASC");

include 'views/header.php';
?>

<div class="mb-4">
    <h2><i class="fas fa-concierge-bell"></i> Services</h2>
    <hr>
</div>

<?php echo $message; ?>

<?php if ($action == 'list'): ?>
    <div class="mb-3">
        <a href="services.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Service
        </a>
    </div>

    <div class="row">
        <?php while($serv = $services->fetch_assoc()): ?>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $serv['name']; ?></h5>
                    <p class="card-text"><?php echo $serv['description'] ?? 'No description'; ?></p>
                    <p class="card-text">
                        <strong>Price: $<?php echo number_format($serv['price'], 2); ?></strong>
                    </p>
                    <p class="card-text">
                        <span class="badge bg-<?php echo $serv['status'] == 'available' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($serv['status']); ?>
                        </span>
                    </p>
                </div>
                <div class="card-footer bg-light">
                    <a href="services.php?action=edit&id=<?php echo $serv['id']; ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button type="button" class="btn btn-sm btn-danger" onclick="setDeleteService(<?php echo $serv['id']; ?>, '<?php echo $serv['name']; ?>')" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
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
                    Are you sure you want to delete service <strong id="deleteServiceName"></strong>?
                    <p class="text-danger small mt-2">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="services.php?action=delete" style="display:inline;">
                        <input type="hidden" name="service_id" id="deleteServiceId">
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?php echo ($action == 'add') ? 'Add New Service' : 'Edit Service'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" action="services.php?action=<?php echo $action; ?>">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Service Name *</label>
                        <input type="text" class="form-control" name="name" value="<?php echo $service['name'] ?? ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Price ($) *</label>
                        <input type="number" class="form-control" name="price" value="<?php echo $service['price'] ?? ''; ?>" step="0.01" min="0" required>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"><?php echo $service['description'] ?? ''; ?></textarea>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-control" name="status" required>
                            <option value="available" <?php echo ($service['status'] ?? '') == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="unavailable" <?php echo ($service['status'] ?? '') == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo ($action == 'add') ? 'Add Service' : 'Update Service'; ?>
                </button>
                <a href="services.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
function setDeleteService(serviceId, serviceName) {
    document.getElementById('deleteServiceId').value = serviceId;
    document.getElementById('deleteServiceName').textContent = serviceName;
}
</script>

<?php include 'views/footer.php'; ?>
