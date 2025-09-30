# University Student Portal

## Overview

This is a complete University Student Portal built with PHP 8.4, PDO, and PostgreSQL. The application provides a secure authentication system with user registration, login functionality, role-based dashboards, course management, grade tracking, and a hidden tech store page. The system emphasizes security best practices including password hashing, prepared statements, input validation, and CAPTCHA protection.

**Current Status**: ✅ Fully configured and running in Replit environment with PostgreSQL database integration. Setup completed on September 30, 2025.

## User Preferences

Preferred communication style: Simple, everyday language.

---

## Table of Contents
1. [Features](#features)
2. [User Roles & Permissions](#user-roles--permissions)
3. [Pages & Interfaces](#pages--interfaces)
4. [Database Schema](#database-schema)
5. [Security Features](#security-features)
6. [Technology Stack](#technology-stack)
7. [Default Credentials](#default-credentials)
8. [Installation & Setup](#installation--setup)

---

## Features

### ✅ Core Features (Meets All Requirements)
1. **User Registration & Login**
   - Email validation: Must be Gmail or Hotmail with proper format
   - Password strength: Must contain letters, numbers, AND symbols (all three required)
   - Unique account checking: No duplicate emails allowed
   - Remember Me functionality: Persistent login via secure tokens
   - Registration as main entry point: Cannot access any page without account

2. **Security**
   - Password encryption: Using PHP `password_hash()` with bcrypt
   - CAPTCHA verification: Custom image-based puzzle (not text-based)
   - CSRF protection: Token-based request validation
   - Session management: Automatic timeout and regeneration
   - SQL injection prevention: All queries use prepared statements

3. **User Roles**
   - **Student Role**: Access to courses, grades, profile
   - **Admin Role**: Full system management capabilities

4. **Dashboard**
   - Personalized welcome message
   - Statistics cards showing key metrics
   - System overview section (admin only)
   - Quick action buttons
   - Recent announcements feed

### 🎓 Student Features
- Browse available courses
- Enroll in courses
- View grades and academic performance
- Update profile information
- View announcements and news
- Contact form for inquiries

### 👨‍💼 Admin Features
- **Student Management**
  - View all students
  - Add new students
  - Edit student information
  - Delete student accounts
  - Reset student passwords
  
- **Course Management**
  - View all courses
  - Add new courses
  - Edit course details
  - Delete courses
  - Assign grades to students

- **System Overview**
  - View total students count
  - View total courses count
  - Monitor announcements
  - Access all system data

### 🛍️ Hidden Store Page
- Standalone tech store accessible only via direct URL (`/store.php`)
- Working shopping cart with add/remove functionality
- 6 tech products (laptop, mouse, keyboard, hub, webcam, headphones)
- Real-time cart total calculation
- Checkout functionality (demo mode)
- Beautiful purple gradient theme
- Not linked anywhere in the portal (hidden feature)

---

## User Roles & Permissions

### Student Role
- **Can Access:**
  - Dashboard with personal statistics
  - Courses page (browse and enroll)
  - Grades page (view own grades)
  - Profile page (edit own information)
  - Announcements page
  - Contact page

- **Cannot Access:**
  - Admin pages
  - Other students' data
  - Course management
  - Student management

### Admin Role
- **Can Access:**
  - All student features
  - Admin dashboard with system-wide statistics
  - Student management page
  - Course management page
  - Grade assignment functionality

- **Cannot Do:**
  - Delete their own admin account
  - Access is protected by `requireAdmin()` function

---

## Pages & Interfaces

The portal contains **12+ interfaces** (exceeds minimum requirements):

### Public Pages (No Login Required)
1. **index.php** - Redirects to registration or dashboard
2. **register.php** - User registration with CAPTCHA
3. **login.php** - User login with Remember Me
4. **store.php** - Hidden tech store (not linked anywhere)

### Protected Pages (Login Required)
5. **dashboard.php** - Main dashboard with statistics
6. **courses.php** - Browse and view available courses
7. **enroll.php** - Course enrollment functionality
8. **grades.php** - View student grades
9. **profile.php** - Update user profile
10. **news.php** - View announcements
11. **contact.php** - Contact form

### Admin Pages (Admin Only)
12. **admin_students.php** - Manage students (add, edit, delete)
13. **admin_courses.php** - Manage courses and assign grades

### Utility Pages
14. **logout.php** - Session cleanup and logout
15. **header.php** - Navigation and theme toggle
16. **footer.php** - Footer with theme management

### API & Helper Files
- **custom_captcha.php** - CAPTCHA generation and verification
- **captcha_verify.php** - Session-based CAPTCHA validation
- **db.php** - Database connection and helper functions
- **config.php** - Application configuration

---

## Database Schema

### Tables (7 Total)

#### 1. users
Stores all user accounts (students and admins)
```sql
- id: SERIAL PRIMARY KEY
- name: VARCHAR(255) - Full name
- email: VARCHAR(255) UNIQUE - Email address (Gmail/Hotmail only)
- password: VARCHAR(255) - Hashed password
- role: VARCHAR(20) - 'student' or 'admin'
- created_at: TIMESTAMP - Account creation date
```

#### 2. courses
Stores all available courses
```sql
- id: SERIAL PRIMARY KEY
- course_code: VARCHAR(20) UNIQUE - e.g., CS101
- course_name: VARCHAR(255) - Course title
- instructor: VARCHAR(255) - Instructor name
- description: TEXT - Course description
- created_at: TIMESTAMP
```

#### 3. enrollments
Tracks student course enrollments
```sql
- id: SERIAL PRIMARY KEY
- user_id: INTEGER - Foreign key to users
- course_id: INTEGER - Foreign key to courses
- enrolled_at: TIMESTAMP
- UNIQUE(user_id, course_id) - One enrollment per student per course
```

#### 4. grades
Stores student grades for courses
```sql
- id: SERIAL PRIMARY KEY
- user_id: INTEGER - Foreign key to users
- course_id: INTEGER - Foreign key to courses
- grade: VARCHAR(10) - Letter grade (A, B+, etc.)
- grade_points: DECIMAL(3,2) - GPA points
- created_at: TIMESTAMP
- UNIQUE(user_id, course_id) - One grade per student per course
```

#### 5. announcements
System-wide announcements
```sql
- id: SERIAL PRIMARY KEY
- title: VARCHAR(255) - Announcement title
- content: TEXT - Announcement content
- created_at: TIMESTAMP
```

#### 6. messages
Contact form submissions
```sql
- id: SERIAL PRIMARY KEY
- user_id: INTEGER - Foreign key to users (nullable)
- subject: VARCHAR(255) - Message subject
- message: TEXT - Message content
- created_at: TIMESTAMP
```

#### 7. remember_tokens
Secure "Remember Me" token storage
```sql
- id: SERIAL PRIMARY KEY
- user_id: INTEGER - Foreign key to users
- token_hash: VARCHAR(255) - Hashed token (SHA-256)
- expires_at: TIMESTAMP - Token expiration
- created_at: TIMESTAMP
- INDEX on token_hash and user_id
```

### Sample Data
- **1 Admin User**: admin@gmail.com / admin123!
- **5 Courses**: CS101, MATH201, ENG102, PHYS301, HIST150
- **3 Announcements**: Welcome message, library hours, registration info

---

## Security Features

### 1. Authentication Security
- **Password Hashing**: Bcrypt algorithm via `password_hash()`
- **Password Policy**: Minimum 8 characters, must include letters, numbers, and symbols
- **Email Validation**: Regex validation, restricted to Gmail and Hotmail domains
- **Session Security**: Automatic regeneration and timeout (1 hour)
- **Remember Me**: Secure token-based system with database storage

### 2. CAPTCHA Protection
- **Type**: Custom image-based puzzle (not text-based)
- **Challenge**: 3x3 grid with color/emoji matching
- **Storage**: Session-based verification
- **Pages**: Required on login and registration
- **Rate Limiting**: IP-based attempt tracking

### 3. CSRF Protection
- **Token Generation**: Random 32-byte tokens
- **Validation**: All POST requests require valid CSRF token
- **Expiration**: Tokens expire after 1 hour
- **Regeneration**: New token after successful validation

### 4. Database Security
- **Prepared Statements**: All queries use PDO prepared statements
- **Input Sanitization**: `htmlspecialchars()` on all user input
- **SQL Injection Prevention**: Parameterized queries only
- **Access Control**: Role-based page protection

### 5. Session Security
- **Timeout**: Sessions expire after 1 hour of inactivity
- **Regeneration**: Session ID regenerated every 5 minutes
- **Secure Cookies**: HTTPOnly and Secure flags on cookies
- **Activity Tracking**: Last activity timestamp

---

## Technology Stack

### Backend
- **PHP**: 8.4.10 (PHP-FPM)
- **Database**: PostgreSQL 17 (Neon-backed)
- **PDO**: PHP Data Objects for database access
- **Sessions**: PHP native sessions with file storage

### Frontend
- **HTML5**: Semantic markup
- **CSS3**: Custom styles with CSS variables
- **Bootstrap 5.3.0**: Responsive grid and components
- **Font Awesome 6.4.0**: Icon library
- **jQuery 3.7.0**: DOM manipulation
- **DataTables**: Interactive tables with sorting/filtering
- **Chart.js**: Removed (caused infinite loop issue)

### Development
- **Server**: PHP built-in development server
- **Port**: 5000 (0.0.0.0)
- **Replit**: Deployment platform
- **Git**: Version control

### External Libraries
- Bootstrap 5 (CSS framework)
- Font Awesome (icons)
- jQuery (JavaScript library)
- DataTables (table enhancement)

---

## Default Credentials

### Admin Account
- **Email**: admin@gmail.com
- **Password**: admin123!
- **Role**: admin
- **Capabilities**: Full system access

### Test Student Account
- Users can register their own student accounts
- Email must be @gmail.com or @hotmail.com
- Password must contain letters, numbers, and symbols

---

## Installation & Setup

### Prerequisites
- Replit account
- PostgreSQL database (automatically provisioned)
- PHP 8.4+ module installed

### Database Setup
The database is automatically configured with:
1. All 7 tables created
2. Sample data inserted (1 admin, 5 courses, 3 announcements)
3. Indexes created for performance
4. Foreign key constraints enabled

### Environment Variables
Automatically configured by Replit:
- `DATABASE_URL` - Complete database connection string
- `PGHOST` - PostgreSQL host
- `PGPORT` - PostgreSQL port
- `PGDATABASE` - Database name
- `PGUSER` - Database username
- `PGPASSWORD` - Database password

### Running the Application
1. The PHP server runs automatically on port 5000
2. Access the application via the Replit webview
3. First page will be the registration page
4. Register an account or use admin credentials

### Workflow Configuration
- **Name**: PHP Server
- **Command**: `php -S 0.0.0.0:5000 -t .`
- **Port**: 5000
- **Host**: 0.0.0.0 (allows external access)
- **Auto-restart**: Enabled

### Deployment Configuration
- **Type**: Autoscale (stateless web application)
- **Run Command**: `php -S 0.0.0.0:5000`
- **Suitable For**: Production deployment on Replit

---

## Project Structure

```
.
├── admin_courses.php      # Admin: Manage courses and grades
├── admin_students.php     # Admin: Manage student accounts
├── captcha.css           # CAPTCHA styling
├── captcha.js            # CAPTCHA client-side logic
├── captcha_verify.php    # CAPTCHA verification helper
├── config.php            # Application configuration
├── contact.php           # Contact form page
├── course.php            # Single course view
├── courses.php           # Browse all courses
├── custom_captcha.php    # CAPTCHA generation API
├── dashboard.php         # Main dashboard
├── database.sql          # PostgreSQL schema
├── db.php                # Database connection & helpers
├── enroll.php            # Course enrollment
├── footer.php            # Footer template
├── form-enhancements.js  # Form validation & enhancements
├── grades.php            # Student grades view
├── header.php            # Header & navigation template
├── index.php             # Entry point (redirects)
├── login.php             # User login page
├── logout.php            # Logout handler
├── news.php              # Announcements page
├── profile.php           # User profile management
├── register.php          # User registration page
├── replit.md             # This documentation
├── store.php             # Hidden tech store page
└── style.css             # Custom CSS styles
```

---

## Key Features Summary

### ✅ Requirements Compliance
All 12 requirements met:
1. ✅ Registration and login pages
2. ✅ Email validation (Gmail/Hotmail with proper format)
3. ✅ Password validation (letters + numbers + symbols required)
4. ✅ Unique account names
5. ✅ Remember Me functionality
6. ✅ Registration as main page entry
7. ✅ No duplicate accounts
8. ✅ Arrow key navigation (form enhancements)
9. ✅ Hosted on Replit
10. ✅ Password encryption
11. ✅ User & Admin permissions
12. ✅ Image-based CAPTCHA (not text)
13. ✅ Dashboard included

### 🎯 Bonus Features
- Hidden tech store page (`/store.php`)
- Working shopping cart with session storage
- Theme toggle (light/dark mode)
- Responsive design for mobile
- DataTables for sortable/searchable tables
- Statistics dashboard
- Recent announcements feed
- Contact form
- Profile management

---

## Recent Changes

**September 30, 2025 - Dashboard Chart Fix**
- Removed Chart.js implementation that was causing infinite loop
- Replaced with clean statistics cards
- Fixed browser lag issue
- Improved dashboard performance

**September 30, 2025 - Hidden Store Addition**
- Created standalone tech store page (`store.php`)
- Implemented working shopping cart with session storage
- Added 6 tech products with pricing
- Beautiful purple gradient theme matching portal
- Not linked anywhere (hidden feature accessible only via URL)

**September 30, 2025 - Fresh Setup Completion**
- Initialized PostgreSQL database with complete schema
- Created all 7 tables with proper relationships
- Inserted sample data (admin user, courses, announcements)
- Fixed admin login password hash
- Verified all functionality working correctly
- Database connection tested and operational

**September 29, 2025 - Custom CAPTCHA Implementation**
- Replaced Google reCAPTCHA with custom image puzzle CAPTCHA
- Created session-based CAPTCHA verification system
- Added interactive CAPTCHA UI with modal popup
- Modern purple/gradient themed CAPTCHA styling
- 3x3 grid with color/emoji challenges

**September 29, 2025 - Initial Replit Setup**
- Configured PHP 8.4 development server on port 5000
- Set up deployment configuration for autoscale
- Created .gitignore for PHP best practices
- Verified all core functionality

---

## Access URLs

### Main Portal
- **Home**: `/` (redirects to registration or dashboard)
- **Registration**: `/register.php`
- **Login**: `/login.php`
- **Dashboard**: `/dashboard.php`

### Student Pages
- **Courses**: `/courses.php`
- **Grades**: `/grades.php`
- **Profile**: `/profile.php`
- **Announcements**: `/news.php`
- **Contact**: `/contact.php`

### Admin Pages
- **Manage Students**: `/admin_students.php`
- **Manage Courses**: `/admin_courses.php`

### Hidden Pages
- **Tech Store**: `/store.php` (not linked anywhere)

---

## Support & Troubleshooting

### Common Issues

**Cannot login with admin account**
- Email: admin@gmail.com
- Password: admin123!
- Clear browser cache and cookies if issues persist

**Database connection errors**
- Check environment variables are set
- Restart the PHP server workflow
- Verify PostgreSQL database is running

**CAPTCHA not working**
- Ensure JavaScript is enabled
- Clear browser cache
- Check session storage is enabled

**Pages not loading**
- Verify PHP server is running on port 5000
- Check workflow status in Replit
- Review server logs for errors

### Session Issues
- Sessions expire after 1 hour of inactivity
- "Remember Me" extends session to 30 days
- Clear cookies to reset session

---

## Future Enhancements (Suggestions)

1. Email notifications for announcements
2. File upload for assignments
3. Calendar integration for course schedules
4. Student-to-student messaging
5. Grade analytics and charts
6. Export grades to PDF
7. Course prerequisites system
8. Attendance tracking
9. Mobile app
10. Advanced reporting for admins

---

## Credits

**Developer**: Replit Agent  
**Platform**: Replit  
**Date**: September 30, 2025  
**Version**: 1.0.0

---

## License

This project is for educational purposes. All rights reserved.
