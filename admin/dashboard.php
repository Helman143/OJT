<?php
require_once '../config/config.php';
require_once '../config/database.php';
requireLogin();

$conn = getDBConnection();

// Get statistics
$stats = [];
$stats['total_jobs'] = $conn->query("SELECT COUNT(*) as count FROM jobs")->fetch_assoc()['count'];
$stats['open_jobs'] = $conn->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'Open'")->fetch_assoc()['count'];
$stats['total_applications'] = $conn->query("SELECT COUNT(*) as count FROM applications")->fetch_assoc()['count'];
$stats['unread_applications'] = $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'Unread'")->fetch_assoc()['count'];

// Get recent applications
$recent_applications = $conn->query("
    SELECT a.*, j.position_title 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    ORDER BY a.submitted_at DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get recent jobs
$recent_jobs = $conn->query("
    SELECT * FROM jobs 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NCIP Job Application System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="../assets/images/total-job-posting.svg" alt="Total Job Posting">
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_jobs']; ?></h3>
                    <p>Total Job Postings</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="../assets/images/open-position.svg" alt="Open Positions">
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['open_jobs']; ?></h3>
                    <p>Open Positions</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <img src="../assets/images/total-application.svg" alt="Total Applications">
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_applications']; ?></h3>
                    <p>Total Applications</p>
                </div>
            </div>
            
            <div class="stat-card stat-card-highlight">
                <div class="stat-icon">
                    <img src="../assets/images/unread-applications.svg" alt="Unread Applications">
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['unread_applications']; ?></h3>
                    <p>Unread Applications</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="jobs/create.php" class="btn btn-primary">+ Create New Job Posting</a>
            <a href="applications/index.php" class="btn btn-secondary">View All Applications</a>
            <a href="jobs/index.php" class="btn btn-secondary">Manage Job Postings</a>
        </div>
        
        <!-- Recent Applications -->
        <div class="dashboard-section">
            <h2>Recent Applications</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Email</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_applications)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No applications yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_applications as $app): ?>
                                <tr class="<?php echo strtolower($app['status']); ?>">
                                    <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['position_title']); ?></td>
                                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                                    <td><?php echo formatDate($app['submitted_at']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                            <?php echo $app['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="applications/view.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-right mt-2">
                <a href="applications/index.php" class="btn btn-link">View All Applications →</a>
            </div>
        </div>
        
        <!-- Recent Job Postings -->
        <div class="dashboard-section">
            <h2>Recent Job Postings</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Position Title</th>
                            <th>Department</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_jobs)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No job postings yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_jobs as $job): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($job['position_title']); ?></td>
                                    <td><?php echo htmlspecialchars($job['department']); ?></td>
                                    <td><?php echo formatDate($job['deadline']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($job['status']); ?>">
                                            <?php echo $job['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="jobs/edit.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <a href="jobs/view.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-right mt-2">
                <a href="jobs/index.php" class="btn btn-link">View All Jobs →</a>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

