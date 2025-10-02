<?php
require_once 'config.php';
require_once 'db.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link href="style.css" rel="stylesheet">
</head>
<body>

<?php if (isLoggedIn()): ?>
    <div class="app-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-logo">
                    <i class="fas fa-dumbbell"></i>
                    <div class="logo-text">
                        <span class="logo-title">FitZone</span>
                        <span class="logo-subtitle">Fitness Center</span>
                    </div>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main Menu</div>
                    
                    <div class="nav-item">
                        <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                    
                    <div class="nav-item">
                        <a href="courses.php" class="nav-link <?= $current_page == 'courses.php' ? 'active' : '' ?>">
                            <i class="fas fa-dumbbell"></i>
                            <span>Classes</span>
                        </a>
                    </div>
                    
                    <div class="nav-item">
                        <a href="materials.php" class="nav-link <?= $current_page == 'materials.php' ? 'active' : '' ?>">
                            <i class="fas fa-folder-open"></i>
                            <span>Resources</span>
                        </a>
                    </div>
                    
                    <div class="nav-item">
                        <a href="grades.php" class="nav-link <?= $current_page == 'grades.php' ? 'active' : '' ?>">
                            <i class="fas fa-chart-line"></i>
                            <span>Progress</span>
                        </a>
                    </div>
                    
                    <div class="nav-item">
                        <a href="news.php" class="nav-link <?= $current_page == 'news.php' ? 'active' : '' ?>">
                            <i class="fas fa-bullhorn"></i>
                            <span>Announcements</span>
                        </a>
                    </div>
                    
                    <div class="nav-item">
                        <a href="contact.php" class="nav-link <?= $current_page == 'contact.php' ? 'active' : '' ?>">
                            <i class="fas fa-envelope"></i>
                            <span>Contact</span>
                        </a>
                    </div>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Admin</div>
                    
                    <div class="nav-item">
                        <a href="admin_students.php" class="nav-link <?= $current_page == 'admin_students.php' ? 'active' : '' ?>">
                            <i class="fas fa-users"></i>
                            <span>Manage Members</span>
                        </a>
                    </div>
                    
                    <div class="nav-item">
                        <a href="admin_courses.php" class="nav-link <?= $current_page == 'admin_courses.php' ? 'active' : '' ?>">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Manage Classes</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    
                    <div class="nav-item">
                        <a href="profile.php" class="nav-link <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                            <i class="fas fa-user-edit"></i>
                            <span>Profile</span>
                        </a>
                    </div>
                    
                    <div class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= sanitizeInput($_SESSION['name'] ?? 'User') ?></div>
                        <div class="user-role"><?= isAdmin() ? 'Admin' : 'Member' ?></div>
                    </div>
                </div>
            </div>
        </aside>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <div class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?= isset($page_title) ? $page_title : 'Dashboard' ?></h1>
                </div>
                <div class="topbar-right">
                    <button class="theme-toggle" onclick="toggleTheme()" id="theme-toggle" title="Toggle theme">
                        <i class="fas fa-sun" id="theme-icon"></i>
                        <span id="theme-text">Light</span>
                    </button>
                </div>
            </div>
            
            <div class="content-container page-transition">
<?php else: ?>
    <div class="page-transition">
<?php endif; ?>
