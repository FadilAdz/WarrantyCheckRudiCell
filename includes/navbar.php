<?php
require_once '../config/database.php';

$conn = getConnection();
$sql = "SELECT COUNT(*) as total FROM trash";
$result = fetchData($conn, $sql);
$trashCount = $result[0]['total'] ?? 0;
$conn->close();
?>
<style>
/* Custom Navbar Styles - Simplified */
.navbar-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.navbar-brand-custom {
    font-weight: 700;
    font-size: 1.4rem;
    color: white !important;
}

.navbar-brand-custom:hover {
    color: rgba(255, 255, 255, 0.9) !important;
}

.nav-link-custom {
    color: white !important;
    font-weight: 500;
    padding: 0.5rem 1rem;
    margin: 0 0.25rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.nav-link-custom:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white !important;
    text-decoration: none;
}

.nav-link-custom.active {
    background: rgba(255, 255, 255, 0.3);
    color: white !important;
}

.user-info {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff4757;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 0.65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

@media (max-width: 991px) {
    .navbar-nav {
        margin-top: 1rem;
    }

    .nav-link-custom {
        margin: 0.25rem 0;
    }
}
</style>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid">
        <!-- Brand Logo -->
        <a class="navbar-brand navbar-brand-custom text-white d-flex align-items-center" href="dashboard.php">
            <div class="bg-white bg-opacity-20 rounded-circle p-2 me-2">
                <i class="bi bi-phone-fill text-white fs-4"></i>
            </div>
            <div>
                <div class="fw-bold">Rudi Cell</div>
                <small class="text-white-50">Warranty System</small>
            </div>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Menu - Hidden on small screens when sidebar is present -->
        <div class="collapse navbar-collapse d-none d-lg-block" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom text-white" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom text-white" href="input_service.php">
                        <i class="bi bi-plus-circle me-1"></i>Input Service
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom text-white" href="garansi_aktif.php">
                        <i class="bi bi-shield-check me-1"></i>Garansi Aktif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom text-white" href="cek_garansi.php">
                        <i class="bi bi-search me-1"></i>Cek Garansi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom text-white position-relative" href="trash.php">
                        <i class="bi bi-archive me-1"></i>Arsip
                        <?php if ($trashCount > 0): ?>
                        <!-- Notification badge for expired warranties -->
                        <span class="notification-badge"><?php echo $trashCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <!-- User Info & Logout -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-info text-white" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-2"></i>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Administrator'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
// Initialize navbar effects
document.addEventListener('DOMContentLoaded', function() {
    // Add active class to current page
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link-custom');

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
        }
    });

    // Smooth navbar hover effects
    const navbar = document.querySelector('.navbar-custom');

    navbar.addEventListener('mouseenter', function() {
        this.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.2)';
    });

    navbar.addEventListener('mouseleave', function() {
        this.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
    });
});
</script>
