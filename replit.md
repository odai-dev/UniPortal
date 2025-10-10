# جامعة الرشيد الذكية - Ar-Rasheed Smart University Student Portal

## Overview
This is a comprehensive Student Portal for Ar-Rasheed Smart University (جامعة الرشيد الذكية) in Yemen, built with PHP 8.4, PDO, and PostgreSQL. The portal serves Yemeni students and faculty with bilingual Arabic/English support. It offers secure user authentication, role-based access for students and administrators, and features for course management, grade tracking, and communication. A unique, hidden tech store page is also included. The system prioritizes security through robust measures like password hashing, prepared statements, input validation, and CAPTCHA protection.

## User Preferences
Preferred communication style: Simple, everyday language.

## System Architecture
The portal is built on a PHP 8.4 backend utilizing PDO for PostgreSQL database interactions. It employs a responsive frontend design using Bootstrap 5, custom CSS, jQuery, and Font Awesome.

**UI/UX Decisions:**
- **Theming**: Features a theme toggle (light/dark mode) and a distinct purple gradient theme for the hidden store.
- **Responsiveness**: Designed with Bootstrap 5 for mobile-friendly interfaces.
- **Interactive Elements**: Uses DataTables for interactive, sortable, and searchable tables.

**Technical Implementations:**
- **Authentication**: Secure registration and login with strong password policies (letters, numbers, symbols required), email validation (Gmail/Hotmail only), and "Remember Me" functionality using secure tokens.
- **Authorization**: Role-based access control with distinct 'student' and 'admin' roles, managed by `requireAdmin()` function for admin-only pages.
- **Security**:
    - **Password Hashing**: Employs `password_hash()` with bcrypt.
    - **CAPTCHA**: Custom image-based puzzle CAPTCHA (3x3 grid with color/emoji matching) for login and registration, with session-based verification and IP-based rate limiting.
    - **CSRF Protection**: Token-based validation for all POST requests, with token expiration and regeneration.
    - **SQL Injection Prevention**: Achieved through PDO prepared statements for all database queries and `htmlspecialchars()` for input sanitization.
    - **Session Management**: Automatic session timeout (1 hour), session ID regeneration every 5 minutes, and secure cookies (HTTPOnly, Secure flags).

**Feature Specifications:**
- **Core Features**: User registration & login, comprehensive security, student and admin roles, and personalized dashboards with statistics and announcements.
- **Student Features**: Course browsing and enrollment, grade viewing, profile management, announcements, course materials access, and a contact form.
- **Admin Features**: Full student management (add, edit, delete, password reset), course management (add, edit, delete, grade assignment), course materials upload/management, and system overview.
- **Course Materials**: Secure file sharing system where admins can upload course materials (PDF, Word, PowerPoint, Excel, Text, ZIP, RAR up to 10MB) and students can view/download materials only for enrolled courses. Features CSRF protection, MIME validation, and blocked direct file access.
- **Hidden Store**: A standalone tech store (`/store.php`) with a working shopping cart and 6 products, accessible only via direct URL.

**System Design Choices:**
- **Database**: PostgreSQL 17 is the chosen database, managed via PDO for robust data interaction.
- **Timezone**: Configured to Asia/Aden (Yemen timezone).
- **Localization**: Bilingual support (Arabic/English) throughout the interface, with Yemeni context for Ar-Rasheed Smart University.
- **Sample Data**: Features authentic Yemeni instructor names (Dr. Ahmed Al-Haddad, Prof. Khadija Al-Mahbashi, etc.), Yemeni student names, and courses relevant to Ar-Rasheed Smart University including "History of Yemen".
- **API/Helper Files**: Dedicated files for CAPTCHA generation (`custom_captcha.php`), verification (`captcha_verify.php`), database connection (`db.php`), application configuration (`config.php`), and file management (`upload_material.php`, `download_material.php`, `delete_material.php`).
- **Security Router**: `router.php` blocks direct HTTP access to uploaded files, ensuring materials are only accessible through authenticated download endpoints.

## External Dependencies
- **PostgreSQL 17**: Database system (Neon-backed).
- **Bootstrap 5.3.0**: Frontend CSS framework for responsive design.
- **Font Awesome 6.4.0**: Icon library.
- **jQuery 3.7.0**: JavaScript library for DOM manipulation and client-side scripting.
- **DataTables**: jQuery plugin for enhancing HTML tables with advanced features like sorting, searching, and pagination.