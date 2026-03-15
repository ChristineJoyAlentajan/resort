<?php
/**
 * Database Configuration
 * Configure your database connection here
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'resort_management');

// Attempt to create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8
    $conn->set_charset("utf8");
    
} catch(Exception $e) {
    echo "Database Error: " . $e->getMessage();
    die();
}

// Helper function to escape and sanitize input
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// Helper function to check if user is logged in (basic implementation)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// CSRF token generation
function generateToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Pagination helper
function getPagination($total, $page = 1, $limit = 10) {
    $pages = ceil($total / $limit);
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset,
        'total' => $total,
        'pages' => $pages
    ];
}

/**
 * Login user - Authenticate based on email and password
 * @param string $email User email
 * @param string $password User password
 * @return array Result with success status and message
 */
function loginUser($email, $password) {
    global $conn;
    
    $email = sanitize($email);
    
    // Check if user exists and is active
    $query = "SELECT id, name, email, password, role, status FROM users WHERE email = ? AND status = 'active'";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['login_time'] = time();
    
    return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
}

/**
 * Logout user - Clear session and redirect
 */
function logoutUser() {
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Check if user has required role for authorization
 * @param string|array $requiredRoles Single role or array of allowed roles
 * @return bool True if user is authorized, false otherwise
 */
function checkAuthorization($requiredRoles = []) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // If no specific roles required, just check if logged in
    if (empty($requiredRoles)) {
        return true;
    }
    
    // Convert single role to array
    if (is_string($requiredRoles)) {
        $requiredRoles = [$requiredRoles];
    }
    
    return in_array($_SESSION['user_role'], $requiredRoles);
}

/**
 * Redirect unauthorized users to login
 * @param string|array $requiredRoles Roles allowed to access the page
 */
function requireLogin($requiredRoles = []) {
    if (!checkAuthorization($requiredRoles)) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

/**
 * Get current logged-in user's information
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

/**
 * Hash password for secure storage
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}
?>
