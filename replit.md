# University Student Portal

## Overview

This is a University Student Portal built with PHP 8+, PDO, and MySQL. The application provides a secure authentication system with user registration, login functionality, and a dashboard for students. The system emphasizes security best practices including password hashing, prepared statements, input validation, and CAPTCHA protection.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
- **Template Structure**: Uses modular PHP includes (header.php, footer.php) for consistent layout
- **Styling**: Custom CSS with modern gradient designs and responsive card-based layouts
- **UI Components**: Authentication forms with glassmorphism design, dashboard statistics cards
- **Client-side Validation**: Regex-based input validation for email and password requirements

### Backend Architecture
- **Language**: PHP 8+ with object-oriented programming patterns
- **Database Layer**: PDO (PHP Data Objects) for all database interactions with prepared statements
- **Security Model**: 
  - Password hashing using `password_hash()` and verification with `password_verify()`
  - Session-based authentication with optional persistent login via cookies
  - Input sanitization and validation using regular expressions
  - CAPTCHA protection on registration and login forms
- **File Organization**:
  - `db.php` - Centralized database connection configuration
  - Separate files for authentication logic (register.php, login.php, logout.php)
  - Protected dashboard area requiring authentication

### Authentication & Authorization
- **Registration System**: Validates unique usernames/emails, enforces password complexity rules
- **Login System**: Email-based authentication with "Remember Me" functionality
- **Session Management**: Server-side sessions with automatic cleanup on logout
- **Access Control**: Page protection requiring valid authentication state
- **Email Validation**: Restricted to specific domains (Gmail, Hotmail)
- **Password Policy**: Enforces inclusion of letters, numbers, and symbols

### Data Storage
- **Primary Database**: MySQL with PDO connection layer
- **Security**: All queries use prepared statements to prevent SQL injection
- **User Data**: Stores full name, email, hashed passwords
- **Session Storage**: PHP sessions with optional cookie persistence

## External Dependencies

- **CAPTCHA Service**: Google reCAPTCHA integration or PHP image captcha library
- **Database**: MySQL database server
- **Web Server**: Apache/Nginx with PHP 8+ support
- **Email Validation**: Gmail and Hotmail domain restrictions
- **Security Libraries**: Built-in PHP password hashing functions