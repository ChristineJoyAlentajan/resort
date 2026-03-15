<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

// Require login and admin role
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

requireRole('admin');

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle ADD NEW USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['role'])) {
        $error = 'All fields are required.';
    } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else if (strlen($_POST['password']) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $password = hashPassword($_POST['password']);
        $role = sanitize($_POST['role']);
        $phone = sanitize($_POST['phone'] ?? '');
        
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Email already exists in the system.';
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, status) VALUES (?, ?, ?, ?, ?, 'active')");
            
            if (!$stmt) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $status = 'active';
                $stmt->bind_param('sssss', $name, $email, $password, $role, $phone);
                
                if ($stmt->execute()) {
                    $message = 'User created successfully!';
                    $action = 'list';
                } else {
                    $error = 'Error creating user: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Handle DELETE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    if (!isset($_POST['csrf_token']) || !verifyToken($_POST['csrf_token'])) {
        $error = 'Invalid request.';
    } else {
        $user_id = sanitize($_POST['user_id']);
        
        // Prevent deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            $error = 'You cannot delete your own account.';
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            
            if ($stmt->execute()) {
                $message = 'User deleted successfully!';
                $action = 'list';
            } else {
                $error = 'Error deleting user: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle UPDATE USER ROLE/STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    if (!isset($_POST['csrf_token']) || !verifyToken($_POST['csrf_token'])) {
        $error = 'Invalid request.';
    } else {
        $user_id = sanitize($_POST['user_id']);
        $role = sanitize($_POST['role']);
        $status = sanitize($_POST['status']);
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone'] ?? '');
        
        // Prevent changing your own role or status
        if ($user_id == $_SESSION['user_id'] && ($role != $_SESSION['user_role'] || $status !== 'active')) {
            $error = 'You cannot change your own role or deactivate your account.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, role = ?, status = ?, phone = ? WHERE id = ?");
            $stmt->bind_param('ssssi', $name, $role, $status, $phone, $user_id);
            
            if ($stmt->execute()) {
                $message = 'User updated successfully!';
                $action = 'list';
            } else {
                $error = 'Error updating user: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Get CSRF token
$csrf_token = generateToken();

// Get all users
$users_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<?php include 'views/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-users"></i> User Management</h2>
    </div>
    <div class="col-md-4 text-end">
        <?php if ($action !== 'add'): ?>
            <a href="users.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New User
            </a>
        <?php else: ?>
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ADD NEW USER FORM -->
<?php if ($action === 'add'): ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Create New User</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="users.php?action=add">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Enter full name">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="Enter email">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Minimum 6 characters">
                            <small class="text-muted">Password will be securely hashed</small>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter phone number">
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role *</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="admin">👑 Admin - Full system access</option>
                                <option value="manager">📋 Manager - Operational management</option>
                                <option value="staff">👤 Staff - Limited access</option>
                            </select>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Clear
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Role Information -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Role Descriptions</h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-2">👑 Admin</h6>
                    <p class="text-muted">Full system access including user management, system settings, and all operational features.</p>

                    <h6 class="mb-2">📋 Manager</h6>
                    <p class="text-muted">Can manage rooms, bookings, guests, and view reports. Cannot manage users or system settings.</p>

                    <h6 class="mb-2">👤 Staff</h6>
                    <p class="text-muted">Limited access to view bookings, process check-ins/check-outs. Cannot delete records or manage users.</p>
                </div>
            </div>
        </div>
    </div>

<!-- LIST USERS -->
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <?php if ($users_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-info">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal" 
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                onclick="deleteUser(<?php echo $user['id']; ?>, <?php echo htmlspecialchars(json_encode($user['name'])); ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No users found. <a href="users.php?action=add">Create the first user</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="users.php?action=update">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="edit-phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="edit-role" class="form-label">Role</label>
                        <select class="form-control" id="edit-role" name="role">
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-status" class="form-label">Status</label>
                        <select class="form-control" id="edit-status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <input type="hidden" id="edit-user-id" name="user_id">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user: <strong id="delete-user-name"></strong>?</p>
                <p class="text-danger"><i class="fas fa-warning"></i> This action cannot be undone!</p>
            </div>
            <form method="POST" action="users.php?action=delete">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <input type="hidden" id="delete-user-id" name="user_id">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit-user-id').value = user.id;
    document.getElementById('edit-name').value = user.name;
    document.getElementById('edit-phone').value = user.phone || '';
    document.getElementById('edit-role').value = user.role;
    document.getElementById('edit-status').value = user.status;
}

function deleteUser(userId, userName) {
    document.getElementById('delete-user-id').value = userId;
    document.getElementById('delete-user-name').textContent = userName;
}
</script>

<?php include 'views/footer.php'; ?>
