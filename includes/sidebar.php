<?php
// Pastikan session dan variabel current page tersedia
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
}
?>

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
