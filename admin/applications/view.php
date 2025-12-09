<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);

$conn = getDBConnection();

// Mark as viewed when opened
$conn->query("UPDATE applications SET status = 'Viewed' WHERE id = $id AND status = 'Unread'");

// Get application details
$application = $conn->query("
    SELECT a.*, j.position_title, j.department, j.job_description 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE a.id = $id
")->fetch_assoc();

if (!$application) {
    header('Location: index.php');
    exit();
}

// Get documents
$documents = $conn->query("SELECT * FROM application_documents WHERE application_id = $id ORDER BY document_type")->fetch_all(MYSQLI_ASSOC);

// Handle download all as ZIP
if (isset($_GET['download_all'])) {
    $zip = new ZipArchive();
    $zip_name = 'application_' . $id . '_' . time() . '.zip';
    $zip_path = sys_get_temp_dir() . '/' . $zip_name;
    
    if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
        foreach ($documents as $doc) {
            if (file_exists($doc['file_path'])) {
                $zip->addFile($doc['file_path'], $doc['document_type'] . '_' . $doc['file_name']);
            }
        }
        $zip->close();
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_name . '"');
        header('Content-Length: ' . filesize($zip_path));
        readfile($zip_path);
        unlink($zip_path);
        exit();
    }
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Application Details</h1>
            <div>
                <a href="?id=<?php echo $id; ?>&download_all=1" class="btn btn-primary">Download All as ZIP</a>
                <a href="index.php" class="btn btn-secondary">← Back to Applications</a>
            </div>
        </div>
        
        <div class="application-view-card">
            <!-- Application Status -->
            <div class="application-status-bar">
                <span class="status-badge status-<?php echo strtolower($application['status']); ?>">
                    <?php echo $application['status']; ?>
                </span>
                <span class="application-date">Submitted: <?php echo formatDate($application['submitted_at']); ?></span>
            </div>
            
            <!-- Applicant Information -->
            <div class="application-section">
                <h2>Applicant Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Full Name:</strong>
                        <span><?php echo htmlspecialchars($application['full_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Email:</strong>
                        <span><?php echo htmlspecialchars($application['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Phone Number:</strong>
                        <span><?php echo htmlspecialchars($application['phone_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Address:</strong>
                        <span><?php echo nl2br(htmlspecialchars($application['address'])); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Position Information -->
            <div class="application-section">
                <h2>Position Applied For</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Position:</strong>
                        <span><?php echo htmlspecialchars($application['position_title']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Department:</strong>
                        <span><?php echo htmlspecialchars($application['department']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Documents Section -->
            <div class="application-section">
                <h2>Submitted Documents</h2>
                <?php if (empty($documents)): ?>
                    <p class="text-muted">No documents uploaded.</p>
                <?php else: ?>
                    <div class="documents-list">
                        <?php foreach ($documents as $doc): ?>
                            <div class="document-item">
                                <div class="document-info">
                                    <strong><?php echo htmlspecialchars($doc['document_type']); ?></strong>
                                    <span class="document-meta">
                                        <?php echo htmlspecialchars($doc['file_name']); ?> 
                                        (<?php echo number_format($doc['file_size'] / 1024, 2); ?> KB)
                                    </span>
                                </div>
                                <div class="document-actions">
                                    <a href="download.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-primary">Download</a>
                                    <?php if (file_exists($doc['file_path'])): ?>
                                        <a href="preview.php?id=<?php echo $doc['id']; ?>" target="_blank" class="btn btn-sm btn-secondary">Preview</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <div class="application-actions">
                <?php if ($application['status'] != 'Archived'): ?>
                    <a href="index.php?action=archive&id=<?php echo $id; ?>" 
                       class="btn btn-warning" 
                       onclick="return confirm('Archive this application?')">Archive Application</a>
                <?php else: ?>
                    <a href="index.php?action=restore&id=<?php echo $id; ?>" 
                       class="btn btn-success">Restore Application</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

