<?php
/**
 * Setup Verification Script
 * Run this file to check if your system is properly configured
 * Access: http://localhost/ojt/setup_check.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>NCIP Job Application System - Setup Check</h1>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;} ul{line-height:2;}</style>";

$checks = [];
$all_ok = true;

// Check PHP version
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.4.0', '>=');
$checks[] = [
    'name' => 'PHP Version',
    'status' => $php_ok,
    'message' => $php_ok ? "PHP $php_version (OK)" : "PHP $php_version (Requires 7.4+)"
];
if (!$php_ok) $all_ok = false;

// Check required PHP extensions
$required_extensions = ['mysqli', 'zip', 'gd'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    $checks[] = [
        'name' => "PHP Extension: $ext",
        'status' => $loaded,
        'message' => $loaded ? "Installed" : "Not installed"
    ];
    if (!$loaded) $all_ok = false;
}

// Check database connection
try {
    require_once 'config/database.php';
    $conn = getDBConnection();
    $checks[] = [
        'name' => 'Database Connection',
        'status' => true,
        'message' => 'Connected successfully'
    ];
    
    // Check if tables exist
    $tables = ['admins', 'jobs', 'applications', 'application_documents'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = $result && $result->num_rows > 0;
        $checks[] = [
            'name' => "Table: $table",
            'status' => $exists,
            'message' => $exists ? "Exists" : "Missing - Will be created automatically"
        ];
        // Don't fail if table doesn't exist yet - it will be auto-created
    }
    
    closeDBConnection($conn);
} catch (Exception $e) {
    $checks[] = [
        'name' => 'Database Connection',
        'status' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
    $all_ok = false;
}

// Check uploads directory
$uploads_dir = __DIR__ . '/uploads';
$uploads_exists = is_dir($uploads_dir);
$uploads_writable = $uploads_exists && is_writable($uploads_dir);

$checks[] = [
    'name' => 'Uploads Directory',
    'status' => $uploads_exists,
    'message' => $uploads_exists ? "Exists" : "Will be created automatically"
];

$checks[] = [
    'name' => 'Uploads Directory Writable',
    'status' => $uploads_writable,
    'message' => $uploads_writable ? "Writable" : "Not writable - Check permissions"
];
if (!$uploads_writable && $uploads_exists) $all_ok = false;

// Check file upload settings
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$checks[] = [
    'name' => 'PHP upload_max_filesize',
    'status' => true,
    'message' => $upload_max . " (Recommended: 15M+)"
];

$checks[] = [
    'name' => 'PHP post_max_size',
    'status' => true,
    'message' => $post_max . " (Recommended: 20M+)"
];

// Display results
echo "<h2>System Check Results</h2>";
echo "<ul>";
foreach ($checks as $check) {
    $class = $check['status'] ? 'ok' : 'error';
    $icon = $check['status'] ? '✓' : '✗';
    echo "<li class='$class'><strong>$icon {$check['name']}:</strong> {$check['message']}</li>";
}
echo "</ul>";

if ($all_ok) {
    echo "<h2 class='ok'>✓ All checks passed! Your system is ready.</h2>";
    echo "<p><a href='index.php'>Go to Job Listings</a> | <a href='admin/login.php'>Admin Login</a></p>";
} else {
    echo "<h2 class='error'>✗ Some checks failed. Please fix the issues above.</h2>";
    echo "<p>Common fixes:</p>";
    echo "<ul>";
    echo "<li>The database and tables will be created automatically on first access</li>";
    echo "<li>Or manually import <code>database.sql</code> if you prefer</li>";
    echo "<li>Check database credentials in <code>config/database.php</code></li>";
    echo "<li>Ensure PHP extensions are installed (mysqli, zip)</li>";
    echo "<li>Set proper permissions on the uploads directory</li>";
    echo "</ul>";
}
?>

