<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

// Check login and require admin role
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

requireRole('admin');

// Ensure admin_logs table exists
$create_logs_table = "CREATE TABLE IF NOT EXISTS admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_name VARCHAR(100),
    user_role VARCHAR(50),
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    data JSON,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (action),
    INDEX (timestamp)
)";
$conn->query($create_logs_table);

$message = "";
$error = "";

// Handle backup request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['backup_database'])) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = "Security token expired.";
        } else {
            // Create backup directory if not exists
            $backup_dir = 'backups';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }

            $timestamp = date('Y-m-d_H-i-s');
            $backup_file = $backup_dir . '/resort_db_' . $timestamp . '.sql';

            // Create SQL dump
            $db_host = DB_HOST;
            $db_user = DB_USER;
            $db_pass = DB_PASS;
            $db_name = DB_NAME;

            $command = "mysqldump -h $db_host -u $db_user -p$db_pass $db_name > $backup_file";
            $output = array();
            $return_var = 0;

            exec($command, $output, $return_var);

            if ($return_var === 0) {
                logAdminAction('database_backup', 'Database backed up successfully', ['file' => $backup_file]);
                $message = "Database backup created successfully: " . basename($backup_file);
            } else {
                $error = "Error creating backup. Make sure mysqldump is installed and accessible.";
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
    <title>System Settings - Resort Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .settings-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }

        .settings-section h3 {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .settings-section h3 i {
            margin-right: 10px;
            color: #667eea;
            font-size: 24px;
        }

        .setting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-label {
            flex: 1;
        }

        .setting-label h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }

        .setting-label p {
            margin: 5px 0 0 0;
            color: #999;
            font-size: 13px;
        }

        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-danger-custom {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .alert-custom {
            border-radius: 10px;
            margin-bottom: 20px;
            border: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-box h6 {
            color: #666;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-box .stat-number {
            font-size: 28px;
            color: #667eea;
            font-weight: bold;
        }

        .admin-only-badge {
            display: inline-block;
            background: #f53c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
        }

        .confirmation-modal {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .log-table {
            font-size: 13px;
        }

        .log-table td {
            padding: 12px;
            vertical-align: middle;
        }

        .log-table tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'views/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1><i class="bi bi-gear"></i> System Settings <span class="admin-only-badge">Admin Only</span></h1>
                <p class="text-muted">Manage system configuration and maintenance</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-custom" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-custom" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- System Information -->
        <div class="settings-section">
            <h3><i class="bi bi-info-circle"></i> System Information</h3>
            
            <div class="stats-grid">
                <?php
                $users_count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
                $rooms_count = $conn->query("SELECT COUNT(*) as total FROM rooms")->fetch_assoc()['total'];
                $guests_count = $conn->query("SELECT COUNT(*) as total FROM guests")->fetch_assoc()['total'];
                $bookings_count = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'];
                ?>
                <div class="stat-box">
                    <h6>Total Staff Users</h6>
                    <div class="stat-number"><?php echo $users_count; ?></div>
                </div>
                <div class="stat-box">
                    <h6>Total Rooms</h6>
                    <div class="stat-number"><?php echo $rooms_count; ?></div>
                </div>
                <div class="stat-box">
                    <h6>Total Guests</h6>
                    <div class="stat-number"><?php echo $guests_count; ?></div>
                </div>
                <div class="stat-box">
                    <h6>Total Bookings</h6>
                    <div class="stat-number"><?php echo $bookings_count; ?></div>
                </div>
            </div>
        </div>

        <!-- Database Maintenance -->
        <div class="settings-section">
            <h3><i class="bi bi-database"></i> Database Maintenance</h3>

            <div class="setting-item">
                <div class="setting-label">
                    <h5>Database Backup</h5>
                    <p>Create a complete backup of the resort database</p>
                </div>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                    <button 
                        type="submit" 
                        name="backup_database" 
                        class="btn-custom"
                        onclick="return confirm('Create database backup? This may take a moment.');"
                    >
                        <i class="bi bi-download"></i> Backup Now
                    </button>
                </form>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <h5>Database Size</h5>
                    <p>Monitor your database storage usage</p>
                </div>
                <div>
                    <?php
                    $size_query = "SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'";
                    $size_result = $conn->query($size_query);
                    if ($size_result) {
                        $size_data = $size_result->fetch_assoc();
                        $size_bytes = $size_data['size'] ?? 0;
                        $size_mb = round($size_bytes / 1024 / 1024, 2);
                    } else {
                        $size_mb = 'N/A';
                    }
                    ?>
                    <span class="text-primary" style="font-weight: 600; font-size: 16px;">
                        <?php echo (is_numeric($size_mb)) ? $size_mb . ' MB' : $size_mb; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- System Settings -->
        <div class="settings-section">
            <h3><i class="bi bi-sliders"></i> System Settings</h3>

            <div class="setting-item">
                <div class="setting-label">
                    <h5>PHP Version</h5>
                    <p>Currently installed PHP version</p>
                </div>
                <span class="text-primary" style="font-weight: 600;"><?php echo phpversion(); ?></span>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <h5>Server Time</h5>
                    <p>Current server date and time</p>
                </div>
                <span class="text-primary" style="font-weight: 600;"><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>

            <div class="setting-item">
                <div class="setting-label">
                    <h5>Max Upload Size</h5>
                    <p>Maximum file upload size allowed</p>
                </div>
                <span class="text-primary" style="font-weight: 600;"><?php echo ini_get('upload_max_filesize'); ?></span>
            </div>
        </div>

        <!-- Admin Actions Log -->
        <div class="settings-section">
            <h3><i class="bi bi-clock-history"></i> Recent Admin Actions</h3>

            <div class="table-responsive">
                <table class="table table-hover log-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $logs_query = "
                            SELECT * FROM admin_logs 
                            ORDER BY timestamp DESC 
                            LIMIT 10
                        ";
                        $logs_result = $conn->query($logs_query);
                        
                        if ($logs_result && $logs_result->num_rows > 0) {
                            while ($log = $logs_result->fetch_assoc()) {
                                echo "
                                <tr>
                                    <td>
                                        <strong>" . htmlspecialchars($log['user_name']) . "</strong><br>
                                        <small class='text-muted'>" . htmlspecialchars($log['user_role']) . "</small>
                                    </td>
                                    <td><span class='badge bg-info'>" . htmlspecialchars($log['action']) . "</span></td>
                                    <td>" . htmlspecialchars($log['description'] ?? '-') . "</td>
                                    <td><small>" . htmlspecialchars($log['ip_address']) . "</small></td>
                                    <td><small>" . date('M d, Y H:i', strtotime($log['timestamp'])) . "</small></td>
                                </tr>
                                ";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center text-muted'>No admin actions logged yet</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dangerous Zone -->
        <div class="settings-section" style="border-left-color: #f53c3c;">
            <h3 style="color: #f53c3c;">
                <i class="bi bi-exclamation-triangle"></i> Danger Zone
            </h3>
            <p class="text-danger" style="margin-bottom: 20px;">
                <strong>Warning:</strong> The following actions are irreversible. Proceed with caution.
            </p>

            <div class="setting-item">
                <div class="setting-label">
                    <h5>System Usage Logs</h5>
                    <p>View system performance and error logs</p>
                </div>
                <button class="btn btn-outline-danger" onclick="alert('Logs feature coming soon');">
                    <i class="bi bi-eye"></i> View Logs
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid p-4"></div>
    <?php include 'views/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
