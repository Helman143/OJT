<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);

$conn = getDBConnection();
$job = $conn->query("SELECT * FROM jobs WHERE id = $id")->fetch_assoc();

if (!$job) {
    header('Location: index.php');
    exit();
}

// Get application count for this job
$app_count = $conn->query("SELECT COUNT(*) as count FROM applications WHERE job_id = $id")->fetch_assoc()['count'];

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Job Posting - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Job Posting Details</h1>
            <div>
                <a href="edit.php?id=<?php echo $job['id']; ?>" class="btn btn-secondary">Edit</a>
                <a href="index.php" class="btn btn-secondary">← Back</a>
            </div>
        </div>
        
        <div class="job-details-card">
            <div class="job-header">
                <h2><?php echo htmlspecialchars($job['position_title']); ?></h2>
                <span class="status-badge status-<?php echo strtolower($job['status']); ?>">
                    <?php echo $job['status']; ?>
                </span>
            </div>
            
            <div class="job-meta">
                <div class="meta-item">
                    <strong>Department:</strong> <?php echo htmlspecialchars($job['department']); ?>
                </div>
                <?php if ($job['salary_grade']): ?>
                    <div class="meta-item">
                        <strong>Salary Grade:</strong> <?php echo htmlspecialchars($job['salary_grade']); ?>
                    </div>
                <?php endif; ?>
                <div class="meta-item">
                    <strong>Deadline:</strong> <?php echo formatDate($job['deadline']); ?>
                </div>
                <div class="meta-item">
                    <strong>Applications Received:</strong> <?php echo $app_count; ?>
                </div>
            </div>
            
            <div class="job-section">
                <h3>Job Description</h3>
                <div class="job-content">
                    <?php echo nl2br(htmlspecialchars($job['job_description'])); ?>
                </div>
            </div>
            
            <div class="job-section">
                <h3>Qualifications</h3>
                <div class="job-content">
                    <?php echo nl2br(htmlspecialchars($job['qualifications'])); ?>
                </div>
            </div>
            
            <div class="job-section">
                <h3>Required Documents</h3>
                <div class="job-content">
                    <?php echo nl2br(htmlspecialchars($job['required_documents'])); ?>
                </div>
            </div>
            
            <div class="job-actions">
                <a href="../applications/index.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary">View Applications (<?php echo $app_count; ?>)</a>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

