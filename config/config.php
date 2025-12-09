<?php
// Application Configuration
session_start();

// Base URL
define('BASE_URL', 'http://localhost/ojt/');

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 15 * 1024 * 1024); // 15MB
define('ALLOWED_EXTENSIONS', ['pdf']);

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Required document types
define('REQUIRED_DOCUMENTS', [
    'Personal Data Sheet',
    'Transcript of Records',
    'Certificate of Eligibility',
    'Work Experience Sheet',
    'Application Letter',
    'CS Form No. 9 Annex P'
]);

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

// Helper function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit();
    }
}

// Helper function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function to format date
function formatDate($date) {
    return date('F d, Y', strtotime($date));
}

// Helper function to check if job is expired
function isJobExpired($deadline) {
    return strtotime($deadline) < strtotime('today');
}

// Helper function to convert document type to form field name
function getDocumentFieldName($doc_type) {
    $field_name = strtolower($doc_type);
    $field_name = str_replace(' ', '_', $field_name);
    $field_name = str_replace('.', '', $field_name);  // Remove periods
    $field_name = str_replace('#', '', $field_name);   // Remove hash symbols
    $field_name = preg_replace('/[^a-z0-9_]/', '', $field_name); // Remove any other special chars
    return $field_name;
}

// Helper function to get admin base path
function getAdminBasePath() {
    $script_path = $_SERVER['PHP_SELF'];
    // Count how many directories deep we are from admin root
    if (strpos($script_path, '/admin/applications/') !== false || 
        strpos($script_path, '/admin/jobs/') !== false) {
        return '../';
    }
    return '';
}
?>

