<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireLogin();

$error = '';
$success = '';

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
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO jobs (position_title, department, job_description, salary_grade, qualifications, required_documents, deadline, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $position_title, $department, $job_description, $salary_grade, $qualifications, $required_documents, $deadline, $status);
        
        if ($stmt->execute()) {
            $success = 'Job posting created successfully!';
            header('Location: index.php?created=1');
            exit();
        } else {
            $error = 'Error creating job posting: ' . $conn->error;
        }
        
        $stmt->close();
        closeDBConnection($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Job Posting - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Create New Job Posting</h1>
            <a href="index.php" class="btn btn-secondary">← Back to Job Postings</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="job-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="position_title">Position Title *</label>
                    <input type="text" id="position_title" name="position_title" required value="<?php echo htmlspecialchars($_POST['position_title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="department">Department *</label>
                    <input type="text" id="department" name="department" required value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="salary_grade">Salary Grade</label>
                    <input type="text" id="salary_grade" name="salary_grade" value="<?php echo htmlspecialchars($_POST['salary_grade'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="deadline">Deadline / Closing Date *</label>
                    <input type="date" id="deadline" name="deadline" required value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="job_description">Job Description *</label>
                <textarea id="job_description" name="job_description" rows="6" required><?php echo htmlspecialchars($_POST['job_description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="qualifications">Qualifications *</label>
                <textarea id="qualifications" name="qualifications" rows="6" required><?php echo htmlspecialchars($_POST['qualifications'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="required_documents">Required Documents *</label>
                <textarea id="required_documents" name="required_documents" rows="4" required><?php echo htmlspecialchars($_POST['required_documents'] ?? ''); ?></textarea>
                <small>List all required documents (e.g., Personal Data Sheet, Transcript of Records, etc.)</small>
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="Open" <?php echo (($_POST['status'] ?? 'Open') == 'Open') ? 'selected' : ''; ?>>Open</option>
                    <option value="Closed" <?php echo (($_POST['status'] ?? '') == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Job Posting</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

