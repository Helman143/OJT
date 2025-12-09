<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);

$conn = getDBConnection();
$document = $conn->query("SELECT * FROM application_documents WHERE id = $id")->fetch_assoc();

if (!$document || !file_exists($document['file_path'])) {
    die('File not found');
}

closeDBConnection($conn);

// Preview PDF
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $document['file_name'] . '"');
readfile($document['file_path']);
exit();
?>

