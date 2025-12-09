<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);

$conn = getDBConnection();
$document = $conn->query("SELECT * FROM application_documents WHERE id = $id")->fetch_assoc();

if (!$document || !file_exists($document['file_path'])) {
    header('Location: index.php');
    exit();
}

closeDBConnection($conn);

// Download file
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $document['file_name'] . '"');
header('Content-Length: ' . filesize($document['file_path']));
readfile($document['file_path']);
exit();
?>

