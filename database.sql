-- NCIP Job Application System Database Schema

CREATE DATABASE IF NOT EXISTS ncip_job_system;
USE ncip_job_system;

-- Admin Users Table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (password: password - CHANGE THIS IN PRODUCTION!)
-- The hash below is for the password "password"
INSERT INTO admins (username, password, full_name, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'NCIP Admin', 'admin@ncip.gov.ph');

-- Job Postings Table
CREATE TABLE IF NOT EXISTS jobs (
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
);

-- Applications Table
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    status ENUM('Unread', 'Viewed', 'Archived') DEFAULT 'Unread',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Application Documents Table
CREATE TABLE IF NOT EXISTS application_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX idx_job_status ON jobs(status);
CREATE INDEX idx_job_deadline ON jobs(deadline);
CREATE INDEX idx_application_status ON applications(status);
CREATE INDEX idx_application_job ON applications(job_id);

