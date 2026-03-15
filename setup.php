<?php
/**
 * Resort Management System - Setup Helper
 * This file helps verify your installation
 */

// Check if we're in the right location
if (!file_exists('config/db.php')) {
    die('Error: config/db.php not found. Make sure all files are uploaded correctly.');
}

require_once 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Resort Management System - Setup Checker</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .container { background: white; border-radius: 10px; box-shadow: 0 10px 50px rgba(0,0,0,0.3); padding: 30px; max-width: 600px; }
        .check-item { padding: 15px; margin-bottom: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .check-success { background-color: #d4edda; border-left: 4px solid #28a745; }
        .check-error { background-color: #f8d7da; border-left: 4px solid #dc3545; }
        .check-warning { background-color: #fff3cd; border-left: 4px solid #ffc107; }
        h1 { color: #667eea; margin-bottom: 30px; text-align: center; }
        .badge { padding: 8px 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1><i class='fas fa-check-circle'></i> System Check</h1>";

// Check 1: PHP Version
echo "<div class='check-item check-" . (version_compare(PHP_VERSION, '7.4.0') >= 0 ? 'success' : 'error') . "'>";
echo "<span>PHP Version: " . PHP_VERSION . "</span>";
echo "<span class='badge bg-" . (version_compare(PHP_VERSION, '7.4.0') >= 0 ? 'success' : 'danger') . "'>" . (version_compare(PHP_VERSION, '7.4.0') >= 0 ? 'OK' : 'FAIL') . "</span>";
echo "</div>";

// Check 2: MySQL Extension
echo "<div class='check-item check-" . (extension_loaded('mysqli') ? 'success' : 'error') . "'>";
echo "<span>MySQL Extension (mysqli)</span>";
echo "<span class='badge bg-" . (extension_loaded('mysqli') ? 'success' : 'danger') . "'>" . (extension_loaded('mysqli') ? 'OK' : 'FAIL') . "</span>";
echo "</div>";

// Check 3: Database Connection
echo "<div class='check-item check-" . ($conn && !$conn->connect_error ? 'success' : 'error') . "'>";
echo "<span>Database Connection</span>";
if ($conn && !$conn->connect_error) {
    echo "<span class='badge bg-success'>Connected</span>";
} else {
    echo "<span class='badge bg-danger'>Failed</span>";
}
echo "</div>";

// Check 4: Database Tables
if ($conn && !$conn->connect_error) {
    $result = $conn->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    $row = $result->fetch_assoc();
    $table_count = $row['table_count'];
    
    echo "<div class='check-item check-" . ($table_count >= 8 ? 'success' : 'warning') . "'>";
    echo "<span>Database Tables ({$table_count} found, 8 required)</span>";
    echo "<span class='badge bg-" . ($table_count >= 8 ? 'success' : 'warning') . "'>" . ($table_count >= 8 ? 'OK' : 'INCOMPLETE') . "</span>";
    echo "</div>";
    
    // Check 5: File Permissions
    $file_checks = [
        'config/db.php' => 'Database Config',
        'sql/resort_db.sql' => 'SQL Schema',
        'views/header.php' => 'Header View',
        'css/style.css' => 'CSS Stylesheet',
        'js/main.js' => 'JavaScript File'
    ];
    
    echo "<hr>";
    echo "<h5 style='color: #667eea; margin-top: 20px;'>Required Files:</h5>";
    
    foreach ($file_checks as $file => $label) {
        $exists = file_exists($file);
        echo "<div class='check-item check-" . ($exists ? 'success' : 'error') . "'>";
        echo "<span>{$label} ({$file})</span>";
        echo "<span class='badge bg-" . ($exists ? 'success' : 'danger') . "'>" . ($exists ? 'EXISTS' : 'MISSING') . "</span>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<div style='text-align: center; margin-top: 30px;'>";

if ($conn && !$conn->connect_error && $table_count >= 8) {
    echo "<h3 style='color: #28a745;'><i class='fas fa-check-circle'></i> Setup Complete!</h3>";
    echo "<p>Your resort management system is ready to use.</p>";
    echo "<a href='index.php' class='btn btn-success btn-lg'>Go to Dashboard</a>";
} else {
    echo "<h3 style='color: #dc3545;'><i class='fas fa-exclamation-circle'></i> Setup Incomplete</h3>";
    echo "<p>Please complete the following:</p>";
    echo "<ul style='text-align: left;'>";
    
    if (!extension_loaded('mysqli')) {
        echo "<li>Enable MySQL extension (mysqli) in your PHP configuration</li>";
    }
    
    if (!$conn || $conn->connect_error) {
        echo "<li>Fix database connection - check credentials in config/db.php</li>";
        echo "<li>Ensure MySQL server is running</li>";
        echo "<li>Create database 'resort_management'</li>";
    } elseif ($table_count < 8) {
        echo "<li>Import database schema from sql/resort_db.sql</li>";
        echo "<li>Use phpMyAdmin or MySQL command line to import the SQL file</li>";
    }
    
    echo "</ul>";
    echo "<a href='#' class='btn btn-primary btn-lg' onclick='location.reload()'>Retry</a>";
}

echo "</div>";
echo "</div>
</body>
</html>";
?>
