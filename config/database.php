<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ncip_job_system');

// Create database connection
function getDBConnection() {
    // First, connect without database to create it if needed
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->close();
    
    // Now connect to the database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create tables if they don't exist
    initializeDatabase($conn);
    
    return $conn;
}

// Initialize database tables
function initializeDatabase($conn) {
    // Admin Users Table
    $conn->query("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Check if admin exists, if not insert default admin
    $result = $conn->query("SELECT COUNT(*) as count FROM admins");
    if ($result && $result->fetch_assoc()['count'] == 0) {
        // Using the same hash from database.sql for consistency
        // Password: password
        $default_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $stmt = $conn->prepare("INSERT INTO admins (username, password, full_name, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $password, $full_name, $email);
        $username = 'admin';
        $password = $default_password;
        $full_name = 'NCIP Admin';
        $email = 'admin@ncip.gov.ph';
        $stmt->execute();
        $stmt->close();
    }
    
    // Job Postings Table
    $conn->query("CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        position_title VARCHAR(200) NOT NULL,
        department VARCHAR(100) NOT NULL,
        job_description TEXT NOT NULL,
        salary_grade VARCHAR(50),
        qualifications TEXT NOT NULL,
        required_documents TEXT NOT NULL,
        deadline DATE NOT NULL,
        status ENUM('Open', 'Closed', 'Archived') DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Applications Table
    $conn->query("CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        full_name VARCHAR(200) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        status ENUM('Unread', 'Viewed', 'Archived') DEFAULT 'Unread',
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
    )");
    
    // Application Documents Table
    $conn->query("CREATE TABLE IF NOT EXISTS application_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        document_type VARCHAR(100) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    )");
    
    // Create indexes only if they don't exist
    $indexes = [
        ['table' => 'jobs', 'name' => 'idx_job_status', 'columns' => 'status'],
        ['table' => 'jobs', 'name' => 'idx_job_deadline', 'columns' => 'deadline'],
        ['table' => 'applications', 'name' => 'idx_application_status', 'columns' => 'status'],
        ['table' => 'applications', 'name' => 'idx_application_job', 'columns' => 'job_id']
    ];
    
    foreach ($indexes as $index) {
        try {
            // Check if index exists
            $check = $conn->query("SHOW INDEX FROM {$index['table']} WHERE Key_name = '{$index['name']}'");
            if (!$check || $check->num_rows == 0) {
                $conn->query("CREATE INDEX {$index['name']} ON {$index['table']}({$index['columns']})");
            }
        } catch (mysqli_sql_exception $e) {
            // Index might already exist or table might not be ready yet, ignore
            // This prevents duplicate key errors
        }
    }
}

// Close database connection
function closeDBConnection($conn) {
    $conn->close();
}
?>

