<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
$error = '';

$conn = getDBConnection();
$job = $conn->query("SELECT * FROM jobs WHERE id = $id")->fetch_assoc();

if (!$job) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $position_title = sanitizeInput($_POST['position_title'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    $job_description = $_POST['job_description'] ?? '';
    $salary_grade = sanitizeInput($_POST['salary_grade'] ?? '');
    $qualifications = $_POST['qualifications'] ?? '';
    $required_documents = $_POST['required_documents'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    $status = sanitizeInput($_POST['status'] ?? 'Open');
    
    if (empty($position_title) || empty($department) || empty($job_description) || 
        empty($qualifications) || empty($required_documents) || empty($deadline)) {
        $error = 'Please fill in all required fields';
    } else {
        $stmt = $conn->prepare("UPDATE jobs SET position_title = ?, department = ?, job_description = ?, salary_grade = ?, qualifications = ?, required_documents = ?, deadline = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $position_title, $department, $job_description, $salary_grade, $qualifications, $required_documents, $deadline, $status, $id);
        
        if ($stmt->execute()) {
            header('Location: index.php?updated=1');
            exit();
        } else {
            $error = 'Error updating job posting: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job Posting - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Edit Job Posting</h1>
            <a href="index.php" class="btn btn-secondary">← Back to Job Postings</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="job-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="position_title">Position Title *</label>
                    <input type="text" id="position_title" name="position_title" required value="<?php echo htmlspecialchars($job['position_title']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="department">Department *</label>
                    <input type="text" id="department" name="department" required value="<?php echo htmlspecialchars($job['department']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="salary_grade">Salary Grade</label>
                    <input type="text" id="salary_grade" name="salary_grade" value="<?php echo htmlspecialchars($job['salary_grade']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="deadline">Deadline / Closing Date *</label>
                    <input type="date" id="deadline" name="deadline" required value="<?php echo $job['deadline']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="job_description">Job Description *</label>
                <textarea id="job_description" name="job_description" rows="6" required><?php echo htmlspecialchars($job['job_description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="qualifications">Qualifications *</label>
                <textarea id="qualifications" name="qualifications" rows="6" required><?php echo htmlspecialchars($job['qualifications']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="required_documents">Required Documents *</label>
                <textarea id="required_documents" name="required_documents" rows="4" required><?php echo htmlspecialchars($job['required_documents']); ?></textarea>
                <small>List all required documents (e.g., Personal Data Sheet, Transcript of Records, etc.)</small>
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="Open" <?php echo $job['status'] == 'Open' ? 'selected' : ''; ?>>Open</option>
                    <option value="Closed" <?php echo $job['status'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                    <option value="Archived" <?php echo $job['status'] == 'Archived' ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Job Posting</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

