<?php
// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config files
require_once '../config/database.php';
require_once '../config/encryption.php';

// Check authentication
requireLogin();

// Get current page untuk active menu
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Rudi Cell Warranty System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    
    <style>
        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            .sidebar {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            body {
                background: white !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top no-print">
        <div class="container-fluid">
            <button class="navbar-toggler me-3" type="button" onclick="toggleSidebar()">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-phone-fill"></i>
                Rudi Cell Warranty
            </a>
            
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle"></i>
                    <?php echo isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'User'; ?>
                </span>
                
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm" onclick="return confirm('Yakin ingin logout?')">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar no-print">
        <div class="sidebar-header">
            <h5><i class="bi bi-phone-fill"></i> Menu</h5>
        </div>
        
        <ul class="sidebar-menu">
            <li class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'input_service' ? 'active' : ''; ?>">
                <a href="input_service.php">
                    <i class="bi bi-plus-circle"></i>
                    <span>Input Service Baru</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'garansi_aktif' ? 'active' : ''; ?>">
                <a href="garansi_aktif.php">
                    <i class="bi bi-check-circle"></i>
                    <span>Garansi Aktif</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'cek_garansi' ? 'active' : ''; ?>">
                <a href="cek_garansi.php">
                    <i class="bi bi-search"></i>
                    <span>Cek Garansi</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'trash' ? 'active' : ''; ?>">
                <a href="trash.php">
                    <i class="bi bi-trash"></i>
                    <span>Trash (Expired)</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page == 'analisis' ? 'active' : ''; ?>">
                <a href="analisis.php">
                    <i class="bi bi-bar-chart"></i>
                    <span>Analisis Kerusakan</span>
                </a>
            </li>
            
            <li class="menu-divider"></li>
            
            <li>
                <a href="../auth/logout.php" onclick="return confirm('Yakin ingin logout?')">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <small class="text-muted">
                <i class="bi bi-shield-lock"></i>
                AES-256 Encrypted
            </small>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <?php
            // Display flash messages
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                echo '<i class="bi bi-check-circle-fill me-2"></i>';
                echo $_SESSION['success_message'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
                unset($_SESSION['success_message']);
            }
            
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
                echo $_SESSION['error_message'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
                unset($_SESSION['error_message']);
            }
            
            if (isset($_SESSION['warning_message'])) {
                echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
                echo '<i class="bi bi-exclamation-circle-fill me-2"></i>';
                echo $_SESSION['warning_message'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
                unset($_SESSION['warning_message']);
            }
            
            if (isset($_SESSION['info_message'])) {
                echo '<div class="alert alert-info alert-dismissible fade show" role="alert">';
                echo '<i class="bi bi-info-circle-fill me-2"></i>';
                echo $_SESSION['info_message'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
                unset($_SESSION['info_message']);
            }
            ?>
            
            <!-- Page content will be inserted here -->