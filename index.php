<?php
require_once 'config/config.php';
require_once 'config/database.php';

$conn = getDBConnection();

// Auto-close expired jobs
$conn->query("UPDATE jobs SET status = 'Closed' WHERE status = 'Open' AND deadline < CURDATE()");

// Get active/open jobs
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';

$where = "WHERE status = 'Open'";
$params = [];
$types = "";

if (!empty($search)) {
    $where .= " AND (position_title LIKE ? OR department LIKE ? OR job_description LIKE ?)";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term];
    $types = "sss";
}

if (!empty($department_filter)) {
    $where .= " AND department = ?";
    $params[] = $department_filter;
    $types .= "s";
}

if (!empty($params)) {
    $stmt = $conn->prepare("SELECT * FROM jobs $where ORDER BY deadline ASC, created_at DESC");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $jobs = $conn->query("SELECT * FROM jobs $where ORDER BY deadline ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

// Get unique departments for filter
$departments = $conn->query("SELECT DISTINCT department FROM jobs WHERE status = 'Open' ORDER BY department")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Vacancies - NCIP</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="public-header">
        <div class="header-content">
            <div class="logo-container">
                <img src="assets/images/ncip-logo.png" alt="NCIP Logo" class="header-logo" onerror="this.style.display='none'">
                <h1>NCIP JOB APPLICATION SYSTEM</h1>
            </div>
            <button class="menu-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <polygon points="7,2 17,2 22,7 22,17 17,22 7,22 2,17 2,7"
                             fill="#F7F2EB"
                             stroke="currentColor"
                             stroke-width="2"
                             stroke-linejoin="round"></polygon>
                    <line class="menu-line" x1="7" y1="9" x2="17" y2="9"></line>
                    <line class="menu-line" x1="7" y1="12" x2="17" y2="12"></line>
                    <line class="menu-line" x1="7" y1="15" x2="17" y2="15"></line>
                </svg>
            </button>
            <nav>
                <a href="index.php">Job Vacancies</a>
                <a href="admin/login.php">Admin Login</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <h1>Available Job Vacancies</h1>
            <p>Apply for positions at the National Commission on Indigenous Peoples (NCIP)</p>
        </div>
        
        <!-- Search and Filter -->
        <div class="search-filter-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                <?php echo $department_filter == $dept['department'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['department']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search) || !empty($department_filter)): ?>
                    <a href="index.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Job Listings -->
        <div class="jobs-grid">
            <?php if (empty($jobs)): ?>
                <div class="no-results">
                    <p>No job vacancies available at the moment.</p>
                    <p>Please check back later or contact NCIP for more information.</p>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-card-header">
                            <h3><?php echo htmlspecialchars($job['position_title']); ?></h3>
                            <span class="job-dept"><?php echo htmlspecialchars($job['department']); ?></span>
                        </div>
                        <div class="job-card-body">
                            <p class="job-description">
                                <?php echo htmlspecialchars(substr($job['job_description'], 0, 150)); ?>
                                <?php echo strlen($job['job_description']) > 150 ? '...' : ''; ?>
                            </p>
                            <div class="job-meta">
                                <span class="job-deadline">📅 Deadline: <?php echo formatDate($job['deadline']); ?></span>
                            </div>
                        </div>
                        <div class="job-card-footer">
                            <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-block">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <footer class="public-footer">
        <p>&copy; <?php echo date('Y'); ?> National Commission on Indigenous Peoples (NCIP). All rights reserved.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.menu-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const header = btn.closest('.header-content');
                if (!header) return;
                const nav = header.querySelector('nav, .admin-nav');
                if (!nav) return;
                const isOpen = nav.classList.toggle('nav-open');
                btn.setAttribute('aria-expanded', isOpen.toString());
            });
        });
    });
    </script>
</body>
</html>

