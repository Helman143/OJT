# NCIP Job Application System

A comprehensive web-based job hiring portal for the National Commission on Indigenous Peoples (NCIP).

## Features

### Admin Features
- **Secure Login System** - Only admins can log in
- **Job Posting Management**
  - Create, edit, and archive job postings
  - Set deadlines for automatic closing
  - Search and filter job postings
- **Application Management**
  - Gmail-style inbox for viewing applications
  - Pagination support (20 applications per page)
  - Mark applications as Unread/Viewed/Archived
  - Download individual documents or all as ZIP
  - Preview PDF documents
  - Archive and restore applications
  - Search and filter by position or applicant name

### Public Features
- **Job Listings** - View all open job vacancies (no login required)
- **Job Details** - View complete job information
- **Application Form** - Submit applications with PDF document uploads
- **Search & Filter** - Search jobs by keyword or filter by department

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP (recommended for local development)

## Installation

1. **Clone or extract the project** to your web server directory:
   ```
   C:\xampp\htdocs\ojt\
   ```

2. **Create the database**:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the `database.sql` file to create the database and tables
   - Or run the SQL commands manually

3. **Configure database connection** (if needed):
   - Edit `config/database.php` if your MySQL credentials differ from defaults
   - Default: host=localhost, user=root, password=(empty), database=ncip_job_system

4. **Set up file uploads directory**:
   - The system will automatically create the `uploads/` directory
   - Ensure the directory has write permissions

5. **Access the system**:
   - Public job listings: `http://localhost/ojt/`
   - Admin login: `http://localhost/ojt/admin/login.php`
   - Default admin credentials:
     - Username: `admin`
     - Password: `password` (CHANGE THIS IMMEDIATELY in production!)

## Default Admin Account

**IMPORTANT**: Change the default admin password immediately after installation!

Default credentials:
- Username: `admin`
- Password: `password`

To change the password, you can:
1. Log in to phpMyAdmin
2. Go to the `admins` table
3. Update the password hash (use PHP's `password_hash()` function)

Or create a new admin account via SQL:
```sql
INSERT INTO admins (username, password, full_name, email) 
VALUES ('newadmin', '$2y$10$...', 'Admin Name', 'admin@ncip.gov.ph');
```

## File Structure

```
ojt/
├── admin/
│   ├── applications/
│   │   ├── index.php          # Application list (Gmail-style inbox)
│   │   ├── view.php           # View application details
│   │   ├── download.php       # Download individual document
│   │   └── preview.php        # Preview PDF document
│   ├── jobs/
│   │   ├── index.php          # Job postings list
│   │   ├── create.php         # Create new job posting
│   │   ├── edit.php           # Edit job posting
│   │   └── view.php           # View job posting details
│   ├── includes/
│   │   ├── header.php         # Admin header
│   │   └── footer.php         # Admin footer
│   ├── dashboard.php          # Admin dashboard
│   ├── login.php              # Admin login page
│   └── logout.php             # Logout handler
├── assets/
│   └── css/
│       └── style.css          # Main stylesheet
├── config/
│   ├── config.php             # Application configuration
│   └── database.php           # Database connection
├── uploads/                   # Uploaded documents (auto-created)
├── index.php                  # Public job listings
├── job-details.php            # Public job details page
├── apply.php                  # Application form
├── database.sql               # Database schema
└── README.md                  # This file
```

## Required Documents

The system requires applicants to upload the following documents (PDF only):
1. Personal Data Sheet
2. Transcript of Records
3. Certificate of Eligibility
4. Work Experience Sheet
5. Application Letter
6. CS Form No. 9 Annex P

## Auto-Closing Feature

Job postings automatically close when the deadline is reached:
- Expired jobs are marked as "Closed"
- They disappear from public view
- They remain visible in the admin panel for reference

## Archive System

Both job postings and applications can be archived:
- Archived items are removed from active lists
- They can be restored anytime
- Useful for organizing completed hiring processes

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- File upload validation (PDF only, size limits)
- Session-based authentication
- Input sanitization

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Edge (latest)
- Safari (latest)

## Development Notes

- The system uses vanilla PHP (no frameworks)
- Responsive design works on mobile and desktop
- File uploads are stored in the `uploads/` directory
- Maximum file size: 15MB per document

## Troubleshooting

**Cannot upload files:**
- Check that the `uploads/` directory exists and has write permissions
- Verify PHP `upload_max_filesize` and `post_max_size` settings

**Database connection error:**
- Verify MySQL is running
- Check credentials in `config/database.php`
- Ensure database `ncip_job_system` exists

**Login not working:**
- Clear browser cookies/session
- Verify admin account exists in database
- Check password hash format

## License

This system is developed for NCIP (National Commission on Indigenous Peoples).

## Support

For issues or questions, contact the development team.

