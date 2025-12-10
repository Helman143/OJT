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

// Get weekly stats
$week_start = date('Y-m-d', strtotime('-7 days'));
$stats['new_applications_week'] = $conn->query("SELECT COUNT(*) as count FROM applications WHERE submitted_at >= '$week_start'")->fetch_assoc()['count'];
$stats['applications_this_month'] = $conn->query("SELECT COUNT(*) as count FROM applications WHERE MONTH(submitted_at) = MONTH(CURDATE()) AND YEAR(submitted_at) = YEAR(CURDATE())")->fetch_assoc()['count'];

// Get most applied job
$most_applied = $conn->query("
    SELECT j.position_title, COUNT(a.id) as app_count 
    FROM jobs j 
    LEFT JOIN applications a ON j.id = a.job_id 
    GROUP BY j.id 
    ORDER BY app_count DESC 
    LIMIT 1
")->fetch_assoc();

// Get jobs approaching deadline (within 3 days)
$deadline_alerts = $conn->query("
    SELECT *, DATEDIFF(deadline, CURDATE()) as days_left 
    FROM jobs 
    WHERE status = 'Open' 
    AND deadline >= CURDATE() 
    AND deadline <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ORDER BY deadline ASC
")->fetch_all(MYSQLI_ASSOC);

// Get recent applications with filters
$app_search = $_GET['app_search'] ?? '';
$app_status_filter = $_GET['app_status'] ?? '';

$app_where = "1=1";
$app_params = [];
$app_types = "";

if (!empty($app_search)) {
    $app_where .= " AND (a.full_name LIKE ? OR a.email LIKE ? OR j.position_title LIKE ?)";
    $app_search_term = "%$app_search%";
    $app_params = [$app_search_term, $app_search_term, $app_search_term];
    $app_types = "sss";
}

if (!empty($app_status_filter)) {
    $app_where .= " AND a.status = ?";
    $app_params[] = $app_status_filter;
    $app_types .= "s";
}

$app_query = "
    SELECT a.*, j.position_title 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE $app_where
    ORDER BY a.submitted_at DESC 
    LIMIT 10
";

if (!empty($app_params)) {
    $app_stmt = $conn->prepare($app_query);
    $app_stmt->bind_param($app_types, ...$app_params);
    $app_stmt->execute();
    $recent_applications = $app_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $app_stmt->close();
} else {
    $recent_applications = $conn->query($app_query)->fetch_all(MYSQLI_ASSOC);
}

// Get recent jobs with filters
$job_status_filter = $_GET['job_status'] ?? '';
$job_where = "1=1";
$job_params = [];
$job_types = "";

if (!empty($job_status_filter)) {
    $job_where .= " AND status = ?";
    $job_params[] = $job_status_filter;
    $job_types = "s";
}

$job_query = "
    SELECT *, DATEDIFF(deadline, CURDATE()) as days_left 
    FROM jobs 
    WHERE $job_where
    ORDER BY created_at DESC 
    LIMIT 5
";

if (!empty($job_params)) {
    $job_stmt = $conn->prepare($job_query);
    $job_stmt->bind_param($job_types, ...$job_params);
    $job_stmt->execute();
    $recent_jobs = $job_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $job_stmt->close();
} else {
    $recent_jobs = $conn->query($job_query)->fetch_all(MYSQLI_ASSOC);
}

// Get recent activity (simulated - in real app, you'd have an activity log table)
$recent_activity = [];
if (!empty($recent_applications)) {
    foreach (array_slice($recent_applications, 0, 3) as $app) {
        $recent_activity[] = [
            'type' => 'application',
            'message' => "New application from " . htmlspecialchars($app['full_name']) . " for " . htmlspecialchars($app['position_title']),
            'time' => $app['submitted_at']
        ];
    }
}
if (!empty($recent_jobs)) {
    foreach (array_slice($recent_jobs, 0, 2) as $job) {
        $recent_activity[] = [
            'type' => 'job',
            'message' => "Job posted: " . htmlspecialchars($job['position_title']),
            'time' => $job['created_at']
        ];
    }
}
usort($recent_activity, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$recent_activity = array_slice($recent_activity, 0, 5);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NCIP Job Application System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        // Dark mode initialization
        (function() {
            const darkMode = localStorage.getItem('darkMode') === 'true';
            if (darkMode) {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
</head>
<body class="dashboard-body">
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
            </div>
        </div>
        
        <!-- Quick Stats Header Bar -->
        <div class="quick-stats-bar">
            <div class="quick-stats-content">
                <div class="quick-stat-item">
                    <span class="quick-stat-icon">📅</span>
                    <span class="quick-stat-text"><?php echo date('l, F j, Y'); ?></span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-icon">📊</span>
                    <span class="quick-stat-text"><?php echo $stats['new_applications_week']; ?> New Applications This Week</span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-icon">⚠️</span>
                    <span class="quick-stat-text"><?php echo count($deadline_alerts); ?> Jobs Approaching Deadline</span>
                </div>
            </div>
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
            <a href="jobs/create.php" class="btn btn-primary btn-action-primary">
                <span class="btn-icon">➕</span>
                <span>Create New Job Posting</span>
            </a>
            <a href="applications/index.php" class="btn btn-secondary btn-action-secondary">
                <span class="btn-icon">📋</span>
                <span>View All Applications</span>
            </a>
            <a href="jobs/index.php" class="btn btn-secondary btn-action-secondary">
                <span class="btn-icon">💼</span>
                <span>Manage Job Postings</span>
            </a>
        </div>
        
        <!-- Deadline Alerts -->
        <?php if (!empty($deadline_alerts)): ?>
            <div class="deadline-alerts">
                <h3 class="deadline-alerts-title">⚠️ Deadline Alerts</h3>
                <div class="deadline-alerts-grid">
                    <?php foreach ($deadline_alerts as $alert): ?>
                        <?php 
                        $days_left = $alert['days_left'];
                        $alert_class = $days_left == 0 ? 'deadline-today' : ($days_left <= 1 ? 'deadline-urgent' : 'deadline-warning');
                        ?>
                        <div class="deadline-alert-item <?php echo $alert_class; ?>">
                            <span class="deadline-alert-job"><?php echo htmlspecialchars($alert['position_title']); ?></span>
                            <span class="deadline-alert-badge">
                                <?php if ($days_left == 0): ?>
                                    Deadline Today
                                <?php elseif ($days_left == 1): ?>
                                    1 day left
                                <?php else: ?>
                                    <?php echo $days_left; ?> days left
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Analytics Section -->
        <div class="dashboard-section analytics-section">
            <h2>📈 Analytics Overview</h2>
            <div class="analytics-grid">
                <div class="analytics-card">
                    <div class="analytics-label">Applications This Month</div>
                    <div class="analytics-value"><?php echo $stats['applications_this_month']; ?></div>
                    <div class="analytics-change">+<?php echo $stats['new_applications_week']; ?> this week</div>
                </div>
                <div class="analytics-card">
                    <div class="analytics-label">Most Applied Position</div>
                    <div class="analytics-value"><?php echo htmlspecialchars($most_applied['position_title'] ?? 'N/A'); ?></div>
                    <div class="analytics-change"><?php echo $most_applied['app_count'] ?? 0; ?> applications</div>
                </div>
                <div class="analytics-card">
                    <div class="analytics-label">Open Positions</div>
                    <div class="analytics-value"><?php echo $stats['open_jobs']; ?></div>
                    <div class="analytics-change">of <?php echo $stats['total_jobs']; ?> total</div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity Log -->
        <div class="dashboard-section activity-section">
            <h2>📝 Recent Activity</h2>
            <div class="activity-list">
                <?php if (empty($recent_activity)): ?>
                    <div class="activity-item">
                        <p class="text-muted">No recent activity</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php echo $activity['type'] == 'application' ? '📄' : '💼'; ?>
                            </div>
                            <div class="activity-content">
                                <p class="activity-message"><?php echo $activity['message']; ?></p>
                                <span class="activity-time"><?php 
                                    $time_diff = time() - strtotime($activity['time']);
                                    if ($time_diff < 3600) {
                                        echo floor($time_diff / 60) . ' minutes ago';
                                    } elseif ($time_diff < 86400) {
                                        echo floor($time_diff / 3600) . ' hours ago';
                                    } else {
                                        echo floor($time_diff / 86400) . ' days ago';
                                    }
                                ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Applications -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Applications</h2>
                <div class="section-filters">
                    <form method="GET" class="inline-form">
                        <input type="text" name="app_search" placeholder="Search applications..." value="<?php echo htmlspecialchars($app_search); ?>" class="filter-search">
                        <select name="app_status" class="filter-select">
                            <option value="">All Status</option>
                            <option value="Unread" <?php echo $app_status_filter == 'Unread' ? 'selected' : ''; ?>>Unread</option>
                            <option value="Viewed" <?php echo $app_status_filter == 'Viewed' ? 'selected' : ''; ?>>Viewed</option>
                            <option value="Archived" <?php echo $app_status_filter == 'Archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                        <?php if (!empty($app_search) || !empty($app_status_filter)): ?>
                            <a href="dashboard.php" class="btn btn-sm btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
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
                                        <a href="applications/view.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary" title="View Application">
                                            <span class="btn-icon">👁️</span>
                                            <span>View</span>
                                        </a>
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
            <div class="section-header">
                <h2>Recent Job Postings</h2>
                <div class="section-filters">
                    <form method="GET" class="inline-form">
                        <input type="hidden" name="app_search" value="<?php echo htmlspecialchars($app_search); ?>">
                        <input type="hidden" name="app_status" value="<?php echo htmlspecialchars($app_status_filter); ?>">
                        <select name="job_status" class="filter-select">
                            <option value="">All Status</option>
                            <option value="Open" <?php echo $job_status_filter == 'Open' ? 'selected' : ''; ?>>Open</option>
                            <option value="Closed" <?php echo $job_status_filter == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                            <option value="Archived" <?php echo $job_status_filter == 'Archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                        <?php if (!empty($job_status_filter)): ?>
                            <a href="dashboard.php?app_search=<?php echo urlencode($app_search); ?>&app_status=<?php echo urlencode($app_status_filter); ?>" class="btn btn-sm btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
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
                                    <td>
                                        <?php echo formatDate($job['deadline']); ?>
                                        <?php if (isset($job['days_left']) && $job['days_left'] >= 0 && $job['days_left'] <= 3): ?>
                                            <?php 
                                            $days_left = $job['days_left'];
                                            $deadline_class = $days_left == 0 ? 'deadline-badge-today' : ($days_left <= 1 ? 'deadline-badge-urgent' : 'deadline-badge-warning');
                                            ?>
                                            <span class="deadline-badge <?php echo $deadline_class; ?>">
                                                <?php if ($days_left == 0): ?>
                                                    ⚠️ Today
                                                <?php elseif ($days_left == 1): ?>
                                                    ⚠️ 1 day
                                                <?php else: ?>
                                                    ⚠️ <?php echo $days_left; ?> days
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($job['status']); ?>">
                                            <?php echo $job['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="jobs/edit.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-secondary" title="Edit Job">
                                            <span class="btn-icon">✏️</span>
                                            <span>Edit</span>
                                        </a>
                                        <a href="jobs/view.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-primary" title="View Job">
                                            <span class="btn-icon">👁️</span>
                                            <span>View</span>
                                        </a>
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

