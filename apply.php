<?php
require_once 'config/config.php';
require_once 'config/database.php';

$job_id = intval($_GET['job_id'] ?? 0);

$conn = getDBConnection();

// Auto-close expired jobs
$conn->query("UPDATE jobs SET status = 'Closed' WHERE status = 'Open' AND deadline < CURDATE()");

$job = $conn->query("SELECT * FROM jobs WHERE id = $job_id")->fetch_assoc();

if (!$job || $job['status'] != 'Open') {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone_number = sanitizeInput($_POST['phone_number'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    // Validate required fields
    if (empty($full_name) || empty($email) || empty($phone_number) || empty($address)) {
        $error = 'Please fill in all required fields';
    } else {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            // Check file uploads
            $required_docs = REQUIRED_DOCUMENTS;
            $uploaded_files = [];
            $upload_errors = [];
            
            foreach ($required_docs as $doc_type) {
                $field_name = getDocumentFieldName($doc_type);
                
                // Check if file was uploaded
                if (!isset($_FILES[$field_name])) {
                    $upload_errors[] = "$doc_type is required";
                    continue;
                }
                
                if ($_FILES[$field_name]['error'] != UPLOAD_ERR_OK) {
                    if ($_FILES[$field_name]['error'] == UPLOAD_ERR_NO_FILE) {
                        $upload_errors[] = "$doc_type is required";
                    } else {
                        $upload_errors[] = "$doc_type upload error (code: " . $_FILES[$field_name]['error'] . ")";
                    }
                    continue;
                }
                
                $file = $_FILES[$field_name];
                
                // Validate file type
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($file_ext != 'pdf') {
                    $upload_errors[] = "$doc_type must be a PDF file";
                    continue;
                }
                
                // Validate file size
                if ($file['size'] > MAX_FILE_SIZE) {
                    $upload_errors[] = "$doc_type exceeds maximum file size (15MB)";
                    continue;
                }
                
                $uploaded_files[$doc_type] = $file;
            }
            
            if (!empty($upload_errors)) {
                $error = implode('<br>', $upload_errors);
            } else {
                // Insert application
                $stmt = $conn->prepare("INSERT INTO applications (job_id, full_name, email, phone_number, address) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $job_id, $full_name, $email, $phone_number, $address);
                
                if ($stmt->execute()) {
                    $application_id = $stmt->insert_id;
                    $stmt->close();
                    
                    // Upload files
                    $all_uploaded = true;
                    foreach ($uploaded_files as $doc_type => $file) {
                        $file_name = $application_id . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                        $file_path = UPLOAD_DIR . $file_name;
                        
                        if (move_uploaded_file($file['tmp_name'], $file_path)) {
                            $doc_stmt = $conn->prepare("INSERT INTO application_documents (application_id, document_type, file_name, file_path, file_size) VALUES (?, ?, ?, ?, ?)");
                            $original_name = $file['name'];
                            $doc_stmt->bind_param("isssi", $application_id, $doc_type, $original_name, $file_path, $file['size']);
                            $doc_stmt->execute();
                            $doc_stmt->close();
                        } else {
                            $all_uploaded = false;
                            break;
                        }
                    }
                    
                    if ($all_uploaded) {
                        $success = 'Your application has been submitted successfully! We will review your application and contact you soon.';
                        // Clear form data
                        $_POST = [];
                    } else {
                        $error = 'Error uploading files. Please try again.';
                        // Delete application if file upload failed
                        $conn->query("DELETE FROM applications WHERE id = $application_id");
                    }
                } else {
                    $error = 'Error submitting application. Please try again.';
                }
            }
        }
    }
}

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo htmlspecialchars($job['position_title']); ?> - NCIP</title>
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
            <a href="job-details.php?id=<?php echo $job_id; ?>">← Back to Job Details</a>
        </div>
        
        <div class="application-form-container">
            <h1>Application Form</h1>
            <p class="form-subtitle">Position: <strong><?php echo htmlspecialchars($job['position_title']); ?></strong></p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-primary">View Other Job Vacancies</a>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data" class="application-form">
                    <div class="form-section">
                        <h2>Personal Information</h2>
                        
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone_number">Phone Number *</label>
                                <input type="tel" id="phone_number" name="phone_number" required 
                                       value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address *</label>
                            <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2>Required Documents</h2>
                        <p class="form-note">Please upload all required documents in PDF format (Maximum 15MB per file)</p>
                        
                        <?php foreach (REQUIRED_DOCUMENTS as $doc_type): ?>
                            <?php 
                            $field_name = getDocumentFieldName($doc_type);
                            ?>
                            <div class="form-group">
                                <label for="<?php echo $field_name; ?>"><?php echo $doc_type; ?> *</label>
                                <input type="file" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" 
                                       accept=".pdf" required>
                                <small>PDF only, max 15MB</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">Submit Application</button>
                        <a href="job-details.php?id=<?php echo $job_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
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

