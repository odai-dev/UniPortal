# FitZone Fitness Center Portal

## Overview
This project is a comprehensive Fitness Center Member Portal, built with PHP 8.4, PDO, and PostgreSQL. It offers secure user authentication, role-based access for members and trainers/staff, and features for class management, progress tracking, and communication. A unique, hidden supplements & gear store is also included. The system prioritizes security through robust measures like password hashing, prepared statements, input validation, and CAPTCHA protection. The project aims to provide a secure and efficient platform for fitness center operations.

## Project Transformation
This project was successfully transformed from a University Student Portal (UNiportal) to a Fitness Center Portal while maintaining all technical requirements and security features. The transformation included:
- **Database Schema**: Renamed tables (students→members, courses→classes, enrollments→memberships, grades→progress)
- **Terminology**: Complete rebrand from academic to fitness theme throughout all pages
- **Visual Design**: Updated color scheme from academic blues/purples to energetic fitness oranges/greens
- **Content**: Transformed all sample data, products, and announcements to fitness-themed content

## User Preferences
Preferred communication style: Simple, everyday language.

## System Architecture
The portal is built on a PHP 8.4 backend utilizing PDO for PostgreSQL database interactions. It employs a responsive frontend design using Bootstrap 5, custom CSS, jQuery, and Font Awesome.

**UI/UX Decisions:**
- **Layout Architecture**: Modern left sidebar navigation (replacing traditional top navbar) with fixed positioning, creating a premium fitness app experience similar to Peloton or ClassPass
- **Sidebar Navigation**: Fixed left panel (260px wide) with logo header, icon-based menu items, active state indicators, and user profile footer section
- **Content Area**: Dynamic main content area with sticky top bar for page titles and quick actions, providing maximum screen real estate
- **Mobile Experience**: Collapsible sidebar with overlay and hamburger menu toggle for responsive mobile navigation
- **Theming**: Features a theme toggle (light/dark mode) with energetic fitness colors (orange/green gradient) and glassmorphism effects
- **Design System**: Comprehensive CSS custom properties for colors, typography, spacing, border radius, shadows, and gradients
- **Card Components**: Modern card-based layouts with hover effects, shadow transitions, and gradient headers throughout the application
- **Responsiveness**: Fully responsive design with mobile-first approach, touch-friendly interactions, and adaptive breakpoints
- **Interactive Elements**: Uses DataTables for interactive, sortable, and searchable tables with custom styling
- **Animations**: Smooth page transitions, staggered card animations, hover lift effects, and floating background elements

**Technical Implementations:**
- **Authentication**: Secure registration and login with strong password policies (letters, numbers, symbols required), email validation (Gmail/Hotmail only), and "Remember Me" functionality using secure tokens.
- **Authorization**: Role-based access control with distinct 'member' and 'admin' roles, managed by `requireAdmin()` function for admin-only pages.
- **Security**:
    - **Password Hashing**: Employs `password_hash()` with bcrypt.
    - **CAPTCHA**: Custom image-based puzzle CAPTCHA (3x3 grid with color/emoji matching) for login and registration, with session-based verification and IP-based rate limiting.
    - **CSRF Protection**: Token-based validation for all POST requests, with token expiration and regeneration.
    - **SQL Injection Prevention**: Achieved through PDO prepared statements for all database queries and `htmlspecialchars()` for input sanitization.
    - **Session Management**: Automatic session timeout (1 hour), session ID regeneration every 5 minutes, and secure cookies (HTTPOnly, Secure flags).

**Feature Specifications:**
- **Core Features**: User registration & login, comprehensive security, member and admin roles, and personalized dashboards with statistics and announcements.
- **Member Features**: Class browsing and registration, progress tracking, profile management, gym announcements, workout resources access, and a contact form for trainer consultation.
- **Admin/Trainer Features**: Full member management (add, edit, delete, password reset), class management (add, edit, delete, performance score assignment), workout resources upload/management, and system overview.
- **Workout Resources**: Secure file sharing system where admins can upload workout plans/nutrition guides (PDF, Word, PowerPoint, Excel, Text, ZIP, RAR up to 10MB) and members can view/download resources only for registered classes. Features CSRF protection, MIME validation, and blocked direct file access.
- **Hidden Store**: A standalone fitness supplements & gear shop (`/store.php`) with a working shopping cart and 6 products (protein powder, resistance bands, yoga mats, etc.), accessible only via direct URL.

**System Design Choices:**
- **Database**: PostgreSQL 17 is the chosen database, managed via PDO for robust data interaction.
    - Tables: users, classes, memberships, progress, announcements, messages, remember_tokens, course_materials
- **API/Helper Files**: Dedicated files for CAPTCHA generation (`custom_captcha.php`), verification (`captcha_verify.php`), database connection (`db.php`), application configuration (`config.php`), and file management (`upload_material.php`, `download_material.php`, `delete_material.php`).
- **Security Router**: `router.php` blocks direct HTTP access to uploaded files, ensuring materials are only accessible through authenticated download endpoints.

## External Dependencies
- **PostgreSQL 17**: Database system (Neon-backed).
- **Bootstrap 5.3.0**: Frontend CSS framework for responsive design.
- **Font Awesome 6.4.0**: Icon library.
- **jQuery 3.7.0**: JavaScript library for DOM manipulation and client-side scripting.
- **DataTables**: jQuery plugin for enhancing HTML tables with advanced features like sorting, searching, and pagination.

## Recent Changes

**October 2, 2025 (PM)**: Complete UI/UX Transformation - Modern Sidebar Layout
- **Major Architectural Change**: Converted entire interface from top horizontal navbar to modern left sidebar navigation
- **Complete CSS Redesign**: Rebuilt style.css (1300+ lines) with comprehensive design system including:
  - CSS custom properties for entire color palette, typography, spacing, and effects
  - Sidebar layout system with fixed positioning and mobile responsive behavior
  - Modern component library (stats cards, class cards, forms, buttons, tables, badges, alerts)
  - Glassmorphism effects on authentication pages
  - Dark mode support throughout the application
  - Smooth animations and transitions (fadeInUp, stagger effects, hover lifts)
- **Header/Footer Restructure**: Updated header.php to render sidebar instead of navbar, added mobile menu toggle, and user profile footer
- **Authentication Pages**: Enhanced login/register pages with full-screen gradient backgrounds and centered glass-effect cards
- **Mobile Navigation**: Implemented collapsible sidebar with overlay and hamburger menu for mobile devices
- **Responsive Design**: Added comprehensive breakpoints and mobile-first responsive layouts
- **All Pages Updated**: Dashboard, classes, profile, grades, admin pages all now use the new sidebar layout and modern design system

**October 2, 2025 (AM)**: Complete project transformation from University Student Portal to Fitness Center Portal
- Updated all database schema (tables and columns)
- Rebranded all PHP pages from academic to fitness terminology
- Changed color scheme to energetic fitness theme (orange/green)
- Transformed hidden store from tech products to fitness supplements & gear
- Maintained all security features and validations (email, password, CAPTCHA, CSRF)
