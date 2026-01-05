<?php
/**
 * Authentication Middleware
 * File ini digunakan untuk memastikan user sudah login sebelum mengakses halaman
 */

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check apakah user sudah login
 */
function checkAuthentication() {
    // Cek apakah session user_id ada
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // User belum login, redirect ke halaman login
        $_SESSION['error_message'] = 'Anda harus login terlebih dahulu!';
        header('Location: ../auth/login.php');
        exit();
    }
    
    // Cek session timeout (optional - 2 jam)
    $timeout_duration = 7200; // 2 jam dalam detik
    
    if (isset($_SESSION['login_time'])) {
        $elapsed_time = time() - $_SESSION['login_time'];
        
        if ($elapsed_time > $timeout_duration) {
            // Session expired
            session_unset();
            session_destroy();
            
            session_start();
            $_SESSION['warning_message'] = 'Sesi Anda telah berakhir. Silakan login kembali.';
            header('Location: ../auth/login.php');
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Get current logged in user data
 * @return array User data
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'login_time' => $_SESSION['login_time'] ?? null
    ];
}

/**
 * Check if user has specific permission (untuk pengembangan future)
 * @param string $permission Permission name
 * @return bool
 */
function hasPermission($permission) {
    // Untuk saat ini, semua user yang login punya akses penuh
    // Bisa dikembangkan untuk role-based access control (RBAC)
    return isset($_SESSION['user_id']);
}

/**
 * Require specific permission
 * @param string $permission Permission name
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        $_SESSION['error_message'] = 'Anda tidak memiliki akses untuk fitur ini!';
        header('Location: ../pages/dashboard.php');
        exit();
    }
}

/**
 * Prevent access for logged in users (untuk halaman login/register)
 */
function preventAuthAccess() {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        header('Location: ../pages/dashboard.php');
        exit();
    }
}

/**
 * Log user activity
 * @param string $action Action performed
 * @param string $details Additional details
 */
function logUserActivity($action, $details = '') {
    $log_file = '../logs/user_activity.log';
    
    // Buat folder logs jika belum ada
    if (!file_exists('../logs')) {
        mkdir('../logs', 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'guest';
    $username = $_SESSION['username'] ?? 'guest';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $log_entry = sprintf(
        "[%s] User ID: %s | Username: %s | IP: %s | Action: %s | Details: %s\n",
        $timestamp,
        $user_id,
        $username,
        $ip_address,
        $action,
        $details
    );
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Regenerate session ID untuk mencegah session fixation attack
 */
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Destroy session dan logout
 */
function destroySession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Log logout activity
        if (isset($_SESSION['username'])) {
            logUserActivity('LOGOUT', "User {$_SESSION['username']} logout");
        }
        
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
}

/**
 * Check CSRF token (untuk form submission yang aman)
 * @param string $token Token from form
 * @return bool
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get user's session duration in human readable format
 * @return string
 */
function getSessionDuration() {
    if (!isset($_SESSION['login_time'])) {
        return 'Unknown';
    }
    
    $duration = time() - $_SESSION['login_time'];
    
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    $seconds = $duration % 60;
    
    if ($hours > 0) {
        return sprintf('%d jam %d menit', $hours, $minutes);
    } elseif ($minutes > 0) {
        return sprintf('%d menit %d detik', $minutes, $seconds);
    } else {
        return sprintf('%d detik', $seconds);
    }
}

/**
 * Check if session is about to expire (within 10 minutes)
 * @return bool
 */
function isSessionExpiringSoon() {
    if (!isset($_SESSION['login_time'])) {
        return false;
    }
    
    $timeout_duration = 7200; // 2 jam
    $warning_threshold = 600; // 10 menit
    
    $elapsed_time = time() - $_SESSION['login_time'];
    $remaining_time = $timeout_duration - $elapsed_time;
    
    return $remaining_time > 0 && $remaining_time <= $warning_threshold;
}

/**
 * Get remaining session time in minutes
 * @return int
 */
function getRemainingSessionTime() {
    if (!isset($_SESSION['login_time'])) {
        return 0;
    }
    
    $timeout_duration = 7200; // 2 jam
    $elapsed_time = time() - $_SESSION['login_time'];
    $remaining_time = $timeout_duration - $elapsed_time;
    
    return max(0, floor($remaining_time / 60));
}

// Auto-check authentication jika file ini di-include
checkAuthentication();
?>