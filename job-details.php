<?php
require_once 'config/config.php';
require_once 'config/database.php';

$id = intval($_GET['id'] ?? 0);

$conn = getDBConnection();

// Auto-close expired jobs
$conn->query("UPDATE jobs SET status = 'Closed' WHERE status = 'Open' AND deadline < CURDATE()");

$job = $conn->query("SELECT * FROM jobs WHERE id = $id")->fetch_assoc();

if (!$job || $job['status'] != 'Open') {
    header('Location: index.php');
    exit();
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['position_title']); ?> - NCIP</title>
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
        <div class="breadcrumb">
            <a href="index.php">← Back to Job Listings</a>
        </div>
        
        <div class="job-details-card">
            <div class="job-header">
                <h1><?php echo htmlspecialchars($job['position_title']); ?></h1>
                <div class="job-meta-info">
                    <span class="job-dept"><?php echo htmlspecialchars($job['department']); ?></span>
                    <?php if ($job['salary_grade']): ?>
                        <span class="job-salary">Salary: <strong><?php echo htmlspecialchars($job['salary_grade']); ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="job-section">
                <h2>Job Description</h2>
                <div class="job-content">
                    <?php echo nl2br(htmlspecialchars($job['job_description'])); ?>
                </div>
            </div>
            
            <div class="job-section">
                <h2>Qualifications</h2>
                <div class="job-content">
                    <?php echo nl2br(htmlspecialchars($job['qualifications'])); ?>
                </div>
            </div>
            
            <div class="job-section">
                <h2>Required Documents</h2>
                <div class="job-content">
                    <?php echo nl2br(htmlspecialchars($job['required_documents'])); ?>
                </div>
            </div>
            
            <div class="job-deadline-box">
                <strong>Application Deadline:</strong> <?php echo formatDate($job['deadline']); ?>
            </div>
            
            <div class="apply-section">
                <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary btn-large">Apply Now</a>
            </div>
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

