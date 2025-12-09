<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin Directory Debug</h1>";
echo "<p>Current file: " . __FILE__ . "</p>";
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script name: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>File Checks:</h2>";
$files_to_check = [
    'dashboard.php',
    'applications/index.php',
    'jobs/index.php',
    '../config/config.php',
    '../config/database.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    $exists = file_exists($full_path);
    echo "<p>" . ($exists ? "✓" : "✗") . " $file: " . ($exists ? "EXISTS" : "NOT FOUND") . "</p>";
    if ($exists) {
        echo "<p style='margin-left:20px;color:gray;'>Full path: $full_path</p>";
    }
}

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='dashboard.php'>dashboard.php</a></li>";
echo "<li><a href='applications/index.php'>applications/index.php</a></li>";
echo "<li><a href='jobs/index.php'>jobs/index.php</a></li>";
echo "<li><a href='test.php'>test.php</a></li>";
echo "</ul>";
?>

