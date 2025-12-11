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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM jobs $where";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    $job_count = $count_result['total'];
    $count_stmt->close();
} else {
    $count_result = $conn->query($count_query)->fetch_assoc();
    $job_count = $count_result['total'];
}

$total_pages = ceil($job_count / $per_page);

// Get jobs with pagination
$order_limit = " ORDER BY deadline ASC, created_at DESC LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare("SELECT * FROM jobs $where $order_limit");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $jobs = $conn->query("SELECT * FROM jobs $where $order_limit")->fetch_all(MYSQLI_ASSOC);
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
                <a href="admin/login.php">Admin Login</a>
            </nav>
        </div>
    </header>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h2 class="hero-title">Join the National Commission on Indigenous Peoples</h2>
            <p class="hero-subtitle">Empowering Indigenous Cultural Communities nationwide through dedicated public service</p>
            <a href="#job-listings" class="btn btn-primary btn-hero">Browse Vacancies</a>
        </div>
    </section>
    
    <!-- Quick Info Bar -->
    <section class="quick-info-bar">
        <div class="container">
            <div class="quick-info-grid">
                <div class="quick-info-item">
                    <span class="quick-info-icon">🌍</span>
                    <span class="quick-info-text">Serving Indigenous Cultural Communities nationwide</span>
                </div>
                <div class="quick-info-item">
                    <span class="quick-info-icon">✅</span>
                    <span class="quick-info-text">Transparent Hiring Process</span>
                </div>
                <div class="quick-info-item">
                    <span class="quick-info-icon">📋</span>
                    <span class="quick-info-text">Government-mandated hiring guidelines</span>
                </div>
            </div>
        </div>
    </section>
    
    <div class="container">
        <div class="page-header">
            <div class="page-header-content">
                <h1>Available Job Vacancies</h1>
                <p>Apply for positions at the National Commission on Indigenous Peoples (NCIP)</p>
            </div>
        </div>
        
        <!-- Enhanced Search and Filter -->
        <div class="search-filter-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search job titles, keywords, or descriptions..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                <select name="department" class="search-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                <?php echo $department_filter == $dept['department'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['department']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-search">Search</button>
                <?php if (!empty($search) || !empty($department_filter)): ?>
                    <a href="index.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Job Listings -->
        <div class="jobs-grid" id="job-listings">
            <?php if (empty($jobs)): ?>
                <div class="no-results">
                    <p>No job vacancies available at the moment.</p>
                    <p>Please check back later or contact NCIP for more information.</p>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $index => $job): ?>
                    <div class="job-card" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <div class="job-card-header">
                            <div class="job-title-wrapper">
                                <span class="job-icon">💼</span>
                                <h3><?php echo htmlspecialchars($job['position_title']); ?></h3>
                            </div>
                            <span class="job-dept"><?php echo htmlspecialchars($job['department']); ?></span>
                        </div>
                        <div class="job-card-body">
                            <p class="job-description">
                                <?php echo htmlspecialchars(substr($job['job_description'], 0, 150)); ?>
                                <?php echo strlen($job['job_description']) > 150 ? '...' : ''; ?>
                            </p>
                            <div class="job-meta">
                                <span class="job-deadline">
                                    <span class="deadline-icon">📅</span>
                                    <span class="deadline-label">Deadline:</span>
                                    <span class="deadline-date"><?php echo formatDate($job['deadline']); ?></span>
                                </span>
                            </div>
                        </div>
                        <div class="job-card-footer">
                            <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-block">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <div class="pagination-info">
                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $job_count); ?> of <?php echo $job_count; ?> positions
                </div>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-secondary btn-sm">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-secondary btn-sm">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <footer class="public-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Information</h3>
                <p><strong>Hotline:</strong> (02) 8-373-97-77</p>
                <p><strong>Email:</strong> info@ncip.gov.ph</p>
                <p><strong>Address:</strong> 2F N. dela Merced Building, Quezon City</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="https://www.ncip.gov.ph" target="_blank">About NCIP</a></li>
                    <li><a href="https://www.ncip.gov.ph/regional-offices" target="_blank">Regional Offices</a></li>
                    <li><a href="https://www.ncip.gov.ph/foi" target="_blank">Freedom of Information</a></li>
                    <li><a href="index.php">Job Vacancies</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="https://www.facebook.com/NCIPOfficial" target="_blank" aria-label="Facebook" class="social-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> National Commission on Indigenous Peoples (NCIP). All rights reserved.</p>
        </div>
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

