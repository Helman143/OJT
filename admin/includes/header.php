<?php
// Calculate admin base path based on current script location
$script_path = $_SERVER['PHP_SELF'];
if (strpos($script_path, '/admin/applications/') !== false || 
    strpos($script_path, '/admin/jobs/') !== false) {
    $admin_base = '../';
    $assets_base = '../../';
} else {
    $admin_base = '';
    $assets_base = '../';
}
?>
<header class="admin-header">
    <div class="header-content">
        <div class="logo">
            <div class="logo-container">
                <img src="<?php echo $assets_base; ?>assets/images/ncip-logo.png" alt="NCIP Logo" class="header-logo" onerror="this.style.display='none'">
                <h2>NCIP JOB APPLICATION SYSTEM</h2>
            </div>
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
        <nav class="admin-nav">
            <a href="<?php echo $admin_base; ?>dashboard.php">Dashboard</a>
            <a href="<?php echo $admin_base; ?>jobs/index.php">Job Postings</a>
            <a href="<?php echo $admin_base; ?>applications/index.php">Applicants</a>
            <div class="user-menu">
                <button class="dark-mode-toggle" aria-label="Toggle dark mode" title="Toggle Dark Mode">
                    <span class="dark-mode-icon">🌙</span>
                </button>
                <div class="profile-dropdown">
                    <button class="profile-trigger" aria-label="Profile menu" aria-expanded="false">
                        <div class="profile-avatar">
                            <span><?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?></span>
                        </div>
                        <span class="profile-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        <span class="profile-arrow">▼</span>
                    </button>
                    <div class="profile-dropdown-menu">
                        <a href="<?php echo $admin_base; ?>dashboard.php" class="profile-menu-item">
                            <span class="menu-icon">🏠</span>
                            <span>Dashboard</span>
                        </a>
                        <a href="#" class="profile-menu-item">
                            <span class="menu-icon">⚙️</span>
                            <span>Settings</span>
                        </a>
                        <a href="#" class="profile-menu-item">
                            <span class="menu-icon">👤</span>
                            <span>Profile</span>
                        </a>
                        <div class="profile-menu-divider"></div>
                        <a href="<?php echo $admin_base; ?>logout.php" class="profile-menu-item profile-menu-logout">
                            <span class="menu-icon">🚪</span>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>

