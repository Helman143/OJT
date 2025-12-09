<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$conn = getDBConnection();

// Handle archive/restore actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'archive') {
        $stmt = $conn->prepare("UPDATE jobs SET status = 'Archived' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header('Location: index.php?archived=1');
        exit();
    } elseif ($action == 'restore') {
        $stmt = $conn->prepare("UPDATE jobs SET status = 'Open' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header('Location: index.php?restored=1');
        exit();
    } elseif ($action == 'close') {
        $stmt = $conn->prepare("UPDATE jobs SET status = 'Closed' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header('Location: index.php?closed=1');
        exit();
    }
}

// Auto-close expired jobs
$conn->query("UPDATE jobs SET status = 'Closed' WHERE status = 'Open' AND deadline < CURDATE()");

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = "";
if ($filter == 'open') {
    $where = "WHERE status = 'Open'";
} elseif ($filter == 'closed') {
    $where = "WHERE status = 'Closed'";
} elseif ($filter == 'archived') {
    $where = "WHERE status = 'Archived'";
}

// Search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $search_term = "%$search%";
    $where .= ($where ? " AND " : "WHERE ") . "(position_title LIKE ? OR department LIKE ?)";
}

// Get jobs
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM jobs $where ORDER BY created_at DESC");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $jobs = $conn->query("SELECT * FROM jobs $where ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Job Postings - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Manage Job Postings</h1>
            <a href="create.php" class="btn btn-primary">+ Create New Job Posting</a>
        </div>
        
        <?php if (isset($_GET['archived'])): ?>
            <div class="alert alert-success">Job posting archived successfully.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['restored'])): ?>
            <div class="alert alert-success">Job posting restored successfully.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['closed'])): ?>
            <div class="alert alert-success">Job posting closed successfully.</div>
        <?php endif; ?>
        
        <!-- Filters and Search -->
        <div class="filters-bar">
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=open" class="filter-tab <?php echo $filter == 'open' ? 'active' : ''; ?>">Open</a>
                <a href="?filter=closed" class="filter-tab <?php echo $filter == 'closed' ? 'active' : ''; ?>">Closed</a>
                <a href="?filter=archived" class="filter-tab <?php echo $filter == 'archived' ? 'active' : ''; ?>">Archived</a>
            </div>
            
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                <?php if ($filter != 'all'): ?>
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
        
        <!-- Jobs Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Position Title</th>
                        <th>Department</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jobs)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No job postings found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($job['position_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($job['department']); ?></td>
                                <td><?php echo formatDate($job['deadline']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($job['status']); ?>">
                                        <?php echo $job['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($job['created_at']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <a href="edit.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <?php if ($job['status'] != 'Archived'): ?>
                                            <a href="?action=archive&id=<?php echo $job['id']; ?>&filter=<?php echo $filter; ?>" 
                                               class="btn btn-sm btn-warning" 
                                               onclick="return confirm('Archive this job posting?')">Archive</a>
                                        <?php else: ?>
                                            <a href="?action=restore&id=<?php echo $job['id']; ?>&filter=<?php echo $filter; ?>" 
                                               class="btn btn-sm btn-success">Restore</a>
                                        <?php endif; ?>
                                        <?php if ($job['status'] == 'Open'): ?>
                                            <a href="?action=close&id=<?php echo $job['id']; ?>&filter=<?php echo $filter; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Close this job posting?')">Close</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

