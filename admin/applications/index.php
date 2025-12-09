<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$conn = getDBConnection();

// Handle status change and archive actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Preserve current filter and page
    $redirect_params = [];
    if (isset($_GET['filter']) && $_GET['filter'] != 'all') {
        $redirect_params['filter'] = $_GET['filter'];
    }
    if (isset($_GET['page']) && $_GET['page'] > 1) {
        $redirect_params['page'] = $_GET['page'];
    }
    if (isset($_GET['job_id']) && !empty($_GET['job_id'])) {
        $redirect_params['job_id'] = $_GET['job_id'];
    }
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $redirect_params['search'] = $_GET['search'];
    }
    
    if ($action == 'archive') {
        $stmt = $conn->prepare("UPDATE applications SET status = 'Archived' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $redirect_params['archived'] = '1';
        header('Location: index.php?' . http_build_query($redirect_params));
        exit();
    } elseif ($action == 'restore') {
        $stmt = $conn->prepare("UPDATE applications SET status = 'Unread' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $redirect_params['restored'] = '1';
        header('Location: index.php?' . http_build_query($redirect_params));
        exit();
    } elseif ($action == 'mark_viewed') {
        $stmt = $conn->prepare("UPDATE applications SET status = 'Viewed' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $redirect_params['viewed'] = '1';
        header('Location: index.php?' . http_build_query($redirect_params));
        exit();
    }
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where_conditions = [];
$params = [];
$types = "";

if ($filter == 'unread') {
    $where_conditions[] = "a.status = 'Unread'";
} elseif ($filter == 'viewed') {
    $where_conditions[] = "a.status = 'Viewed'";
} elseif ($filter == 'archived') {
    $where_conditions[] = "a.status = 'Archived'";
}

// Job filter
$job_id = $_GET['job_id'] ?? '';
if (!empty($job_id)) {
    $where_conditions[] = "a.job_id = ?";
    $params[] = intval($job_id);
    $types .= "i";
}

// Search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $search_term = "%$search%";
    $where_conditions[] = "(a.full_name LIKE ? OR a.email LIKE ? OR j.position_title LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$where = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM applications a JOIN jobs j ON a.job_id = j.id $where";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_count = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_count = $conn->query($count_query)->fetch_assoc()['total'];
}
$total_pages = ceil($total_count / $per_page);

// Get applications with pagination
$query_params = $params;
$query_types = $types;
$query_params[] = $per_page;
$query_params[] = $offset;
$query_types .= "ii";

$stmt = $conn->prepare("SELECT a.*, j.position_title, j.department FROM applications a JOIN jobs j ON a.job_id = j.id $where ORDER BY a.submitted_at DESC LIMIT ? OFFSET ?");
if (!empty($query_params)) {
    $stmt->bind_param($query_types, ...$query_params);
} else {
    // This shouldn't happen, but handle it just in case
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all jobs for filter
$jobs = $conn->query("SELECT id, position_title FROM jobs ORDER BY position_title")->fetch_all(MYSQLI_ASSOC);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Applications</h1>
        </div>
        
        <?php if (isset($_GET['archived'])): ?>
            <div class="alert alert-success">Application archived successfully.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['restored'])): ?>
            <div class="alert alert-success">Application restored successfully.</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['viewed'])): ?>
            <div class="alert alert-success">Application marked as viewed.</div>
        <?php endif; ?>
        
        <!-- Filters and Search -->
        <div class="filters-bar">
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=unread" class="filter-tab <?php echo $filter == 'unread' ? 'active' : ''; ?>">Unread</a>
                <a href="?filter=viewed" class="filter-tab <?php echo $filter == 'viewed' ? 'active' : ''; ?>">Viewed</a>
                <a href="?filter=archived" class="filter-tab <?php echo $filter == 'archived' ? 'active' : ''; ?>">Archived</a>
            </div>
            
            <form method="GET" class="search-form">
                <select name="job_id">
                    <option value="">All Positions</option>
                    <?php foreach ($jobs as $job): ?>
                        <option value="<?php echo $job['id']; ?>" <?php echo $job_id == $job['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($job['position_title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" placeholder="Search applications..." value="<?php echo htmlspecialchars($search); ?>">
                <?php if ($filter != 'all'): ?>
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="index.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
        
        <!-- Applications Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position Applied</th>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No applications found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr class="application-row <?php echo strtolower($app['status']); ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($app['full_name']); ?></strong>
                                    <?php if ($app['status'] == 'Unread'): ?>
                                        <span class="unread-indicator">●</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($app['position_title']); ?></td>
                                <td><?php echo htmlspecialchars($app['department']); ?></td>
                                <td><?php echo htmlspecialchars($app['email']); ?></td>
                                <td><?php echo formatDate($app['submitted_at']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                        <?php echo $app['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <?php if ($app['status'] != 'Archived'): ?>
                                            <?php
                                            $archive_params = ['action' => 'archive', 'id' => $app['id']];
                                            if ($filter != 'all') $archive_params['filter'] = $filter;
                                            if ($page > 1) $archive_params['page'] = $page;
                                            if (!empty($job_id)) $archive_params['job_id'] = $job_id;
                                            if (!empty($search)) $archive_params['search'] = $search;
                                            ?>
                                            <a href="?<?php echo http_build_query($archive_params); ?>" 
                                               class="btn btn-sm btn-warning" 
                                               onclick="return confirm('Archive this application?')">Archive</a>
                                        <?php else: ?>
                                            <?php
                                            $restore_params = ['action' => 'restore', 'id' => $app['id']];
                                            if ($filter != 'all') $restore_params['filter'] = $filter;
                                            if ($page > 1) $restore_params['page'] = $page;
                                            if (!empty($job_id)) $restore_params['job_id'] = $job_id;
                                            if (!empty($search)) $restore_params['search'] = $search;
                                            ?>
                                            <a href="?<?php echo http_build_query($restore_params); ?>" 
                                               class="btn btn-sm btn-success">Restore</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                $query_string = http_build_query(array_filter([
                    'filter' => $filter != 'all' ? $filter : null,
                    'job_id' => !empty($job_id) ? $job_id : null,
                    'search' => !empty($search) ? $search : null
                ]));
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary">← Previous</a>
                <?php endif; ?>
                
                <span class="pagination-info">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                    (<?php echo $total_count; ?> total applications)
                </span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

