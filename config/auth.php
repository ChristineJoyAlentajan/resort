<?php
/**
 * Role-Based Access Control (RBAC) Helper Functions
 */

// Define role permissions
const ROLE_PERMISSIONS = [
    'admin' => [
        // Staff Management
        'manage_staff' => true,
        'add_staff' => true,
        'edit_staff' => true,
        'delete_staff' => true,
        'view_staff' => true,
        
        // Rooms & Cottages
        'manage_rooms' => true,
        'add_rooms' => true,
        'edit_rooms' => true,
        'delete_rooms' => true,
        'view_rooms' => true,
        
        // Pricing & Services
        'manage_pricing' => true,
        'set_prices' => true,
        'manage_services' => true,
        'add_services' => true,
        'edit_services' => true,
        'delete_services' => true,
        
        // Bookings & Reservations
        'view_all_bookings' => true,
        'manage_bookings' => true,
        'approve_bookings' => true,
        'delete_bookings' => true,
        'view_bookings' => true,
        
        // Guests
        'view_guests' => true,
        'manage_guests' => true,
        'add_guests' => true,
        'edit_guests' => true,
        'delete_guests' => true,
        
        // Reports & Analytics
        'view_reports' => true,
        'generate_reports' => true,
        'view_income_reports' => true,
        
        // Payments
        'record_payments' => true,
        
        // System Settings
        'manage_settings' => true,
        'backup_database' => true,
        'maintain_database' => true,
        'view_system_logs' => true,
        
        // Dashboard
        'view_dashboard' => true,
    ],
    
    'manager' => [
        // Bookings & Reservations
        'view_all_bookings' => true,
        'manage_bookings' => true,
        'approve_bookings' => true,
        'view_bookings' => true,
        'view_booking_schedules' => true,
        
        // Room Availability
        'view_rooms' => true,
        'monitor_room_availability' => true,
        
        // Guests
        'view_guests' => true,
        'check_guest_info' => true,
        'edit_guests' => true,
        
        // Staff Management (Limited)
        'view_staff' => true,
        'manage_staff_schedules' => true,
        
        // Reports & Sales
        'view_reports' => true,
        'view_sales_reports' => true,
        'view_income_reports' => true,

        // Payments
        'record_payments' => true,
        
        // Customer Service
        'handle_customer_concerns' => true,
        
        // Dashboard
        'view_dashboard' => true,
    ],
    
    'staff' => [
        // Bookings & Reservations (Read Only)
        'view_bookings' => true,
        'check_guest_reservations' => true,
        
        // Guests
        'view_guests' => true,
        'check_guest_info' => true,
        'edit_guests' => true,
        'register_walkin_guests' => true,
        'add_guests' => true,
        
        // Rooms (Limited)
        'view_rooms' => true,
        'update_room_status' => true,
        
        // Payments
        'record_payments' => true,
        
        // Check-in/Check-out
        'checkin_checkout' => true,
        
        // Dashboard (Limited)
        'view_dashboard' => true,
    ]
];

/**
 * Get current user role
 * @return string User role or empty string
 */
function getUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
}

/**
 * Check if current user has specific permission
 * @param string $permission Permission to check
 * @return bool
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = getUserRole();
    
    if (!isset(ROLE_PERMISSIONS[$role])) {
        return false;
    }
    
    return isset(ROLE_PERMISSIONS[$role][$permission]) && 
           ROLE_PERMISSIONS[$role][$permission] === true;
}

/**
 * Check if current user has any of the given permissions
 * @param array $permissions Array of permissions to check
 * @return bool
 */
function hasAnyPermission($permissions = []) {
    foreach ($permissions as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if current user has all of the given permissions
 * @param array $permissions Array of permissions to check
 * @return bool
 */
function hasAllPermissions($permissions = []) {
    foreach ($permissions as $permission) {
        if (!hasPermission($permission)) {
            return false;
        }
    }
    return true;
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return getUserRole() === 'admin';
}

/**
 * Check if user is manager
 * @return bool
 */
function isManager() {
    return getUserRole() === 'manager';
}

/**
 * Check if user is staff
 * @return bool
 */
function isStaff() {
    return getUserRole() === 'staff';
}

/**
 * Require specific permission, redirect if not granted
 * @param string $permission Permission to require
 * @param string $redirectTo Page to redirect to if denied
 */
function requirePermission($permission, $redirectTo = 'access-denied.php') {
    if (!hasPermission($permission)) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Require any of the given permissions
 * @param array $permissions Permissions to check
 * @param string $redirectTo Page to redirect to if denied
 */
function requireAnyPermission($permissions = [], $redirectTo = 'access-denied.php') {
    if (!hasAnyPermission($permissions)) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Require specific role
 * @param string $role Role to require
 * @param string $redirectTo Page to redirect to if denied
 */
function requireRole($role, $redirectTo = 'access-denied.php') {
    if (getUserRole() !== $role) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Require one of the given roles
 * @param array $roles Roles to check
 * @param string $redirectTo Page to redirect to if denied
 */
function requireAnyRole($roles = [], $redirectTo = 'access-denied.php') {
    if (!in_array(getUserRole(), $roles)) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Get all permissions for current user
 * @return array
 */
function getCurrentUserPermissions() {
    $role = getUserRole();
    return isset(ROLE_PERMISSIONS[$role]) ? ROLE_PERMISSIONS[$role] : [];
}

/**
 * Get permissions for specific role
 * @param string $role Role name
 * @return array
 */
function getRolePermissions($role) {
    return isset(ROLE_PERMISSIONS[$role]) ? ROLE_PERMISSIONS[$role] : [];
}

/**
 * Get all available roles
 * @return array
 */
function getAllRoles() {
    return array_keys(ROLE_PERMISSIONS);
}

/**
 * Get role display name
 * @param string $role Role identifier
 * @return string
 */
function getRoleDisplayName($role) {
    $roleNames = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'staff' => 'Staff'
    ];
    return isset($roleNames[$role]) ? $roleNames[$role] : ucfirst($role);
}

/**
 * Get role badge HTML
 * @param string $role Role identifier
 * @return string HTML badge
 */
function getRoleBadge($role) {
    $badges = [
        'admin' => '<span class="badge bg-danger"><i class="bi bi-shield-lock"></i> Administrator</span>',
        'manager' => '<span class="badge bg-warning"><i class="bi bi-person-badge"></i> Manager</span>',
        'staff' => '<span class="badge bg-info"><i class="bi bi-person-fill"></i> Staff</span>'
    ];
    return isset($badges[$role]) ? $badges[$role] : '<span class="badge bg-secondary">' . ucfirst($role) . '</span>';
}

/**
 * Check if action requires confirmation
 * @param string $action Action to check
 * @return bool
 */
function requiresConfirmation($action) {
    $confirmActions = [
        'delete_staff',
        'delete_rooms',
        'delete_services',
        'delete_bookings',
        'delete_guests',
        'backup_database',
        'maintain_database'
    ];
    return in_array($action, $confirmActions);
}

/**
 * Log admin action
 * @param string $action Action performed
 * @param string $description Action description
 * @param array $data Additional data
 */
function logAdminAction($action, $description = '', $data = []) {
    if (!isLoggedIn()) {
        return;
    }
    
    global $conn;
    
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'];
    $user_role = $_SESSION['user_role'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    $data_json = json_encode($data);
    
    // Create admin_logs table if it doesn't exist
    $createTableQuery = "CREATE TABLE IF NOT EXISTS admin_logs (
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
    
    $conn->query($createTableQuery);
    
    // Insert log entry
    $query = "INSERT INTO admin_logs (user_id, user_name, user_role, action, description, ip_address, data) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssss", $user_id, $user_name, $user_role, $action, $description, $ip_address, $data_json);
    $stmt->execute();
}

?>
