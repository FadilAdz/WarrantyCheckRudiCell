<?php
/**
 * Encryption Configuration & Functions
 * AES-256-CBC Encryption for Rudi Cell Warranty System
 */

// IMPORTANT: Ganti key ini dengan key unik kamu sendiri!
// Cara generate key baru: echo base64_encode(openssl_random_pseudo_bytes(32));
define('ENCRYPTION_KEY', 'YourSecretEncryptionKey123456789!@#RudiCell2025Secure'); // Ganti ini!

/**
 * Encrypt data menggunakan AES-256-CBC
 * @param string $data - Data yang akan dienkripsi
 * @return string - Data terenkripsi (base64 encoded)
 */
function encryptData($data) {
    if (empty($data)) {
        return '';
    }
    
    $cipher = "aes-256-cbc";
    
    // Generate Initialization Vector (IV)
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    
    // Encrypt data
    $encrypted = openssl_encrypt(
        $data, 
        $cipher, 
        hash('sha256', ENCRYPTION_KEY, true), 
        0, 
        $iv
    );
    
    // Gabungkan encrypted data dengan IV, pisahkan dengan ::
    $result = base64_encode($encrypted . '::' . base64_encode($iv));
    
    return $result;
}

/**
 * Decrypt data yang sudah dienkripsi
 * @param string $data - Data terenkripsi (base64 encoded)
 * @return string - Data asli (plaintext)
 */
function decryptData($data) {
    if (empty($data)) {
        return '';
    }
    
    try {
        $cipher = "aes-256-cbc";
        
        // Decode base64
        $decoded = base64_decode($data);
        
        // Pisahkan encrypted data dan IV
        $parts = explode('::', $decoded, 2);
        
        if (count($parts) !== 2) {
            return '[Error: Invalid encrypted data format]';
        }
        
        $encrypted_data = $parts[0];
        $iv = base64_decode($parts[1]);
        
        // Decrypt data
        $decrypted = openssl_decrypt(
            $encrypted_data, 
            $cipher, 
            hash('sha256', ENCRYPTION_KEY, true), 
            0, 
            $iv
        );
        
        return $decrypted !== false ? $decrypted : '[Error: Decryption failed]';
        
    } catch (Exception $e) {
        return '[Error: ' . $e->getMessage() . ']';
    }
}

/**
 * Generate kode garansi unik
 * Format: RC-YYYYMMDD-XXXXX
 * RC = Rudi Cell, XXXXX = Random 5 digit
 * @return string - Kode garansi unik
 */
function generateKodeGaransi() {
    $prefix = 'RC';
    $date = date('Ymd'); // Format: 20241216
    $random = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT); // 5 digit random
    
    return $prefix . '-' . $date . '-' . $random;
}

/**
 * Validasi format kode garansi
 * @param string $kode - Kode garansi yang akan divalidasi
 * @return bool - True jika format valid
 */
function validateKodeGaransi($kode) {
    // Format: RC-YYYYMMDD-XXXXX
    $pattern = '/^RC-\d{8}-\d{5}$/';
    return preg_match($pattern, $kode) === 1;
}

/**
 * Hash password untuk user authentication
 * @param string $password - Password plaintext
 * @return string - Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verifikasi password
 * @param string $password - Password plaintext
 * @param string $hash - Hashed password dari database
 * @return bool - True jika password cocok
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sanitize input untuk mencegah XSS
 * @param string $data - Data input
 * @return string - Data yang sudah di-sanitize
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Format nomor HP Indonesia
 * @param string $phone - Nomor HP
 * @return string - Nomor HP terformat
 */
function formatPhone($phone) {
    // Hapus semua karakter non-digit
    $phone = preg_replace('/\D/', '', $phone);
    
    // Jika diawali 0, ganti dengan 62
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    // Jika tidak diawali 62, tambahkan 62
    if (substr($phone, 0, 2) !== '62') {
        $phone = '62' . $phone;
    }
    
    return $phone;
}

/**
 * Validasi nomor HP (08xx atau 628xx)
 * @param string $phone - Nomor HP yang akan divalidasi
 * @return bool - True jika format valid
 */
function isValidPhoneNumber($phone) {
    // Hanya ambil digit
    $digits = preg_replace('/\D/', '', $phone);

    // Terima format 08xxxxxxxx atau 628xxxxxxxx dengan panjang wajar
    // 08 + 6-12 digit (total 8-14), 628 + 6-12 digit (total 9-15)
    return preg_match('/^(08\d{6,12}|628\d{6,12})$/', $digits) === 1;
}

/**
 * Hitung sisa hari garansi
 * @param string $tanggal_expired - Tanggal expired (YYYY-MM-DD)
 * @return int - Sisa hari garansi (negatif jika sudah expired)
 */
function hitungSisaHari($tanggal_expired) {
    $today = new DateTime();
    $expired = new DateTime($tanggal_expired);
    $interval = $today->diff($expired);
    
    // Jika sudah expired, return negatif
    if ($today > $expired) {
        return -$interval->days;
    }
    
    return $interval->days;
}

/**
 * Generate tanggal expired berdasarkan tanggal service dan masa garansi
 * @param string $tanggal_service - Tanggal service (YYYY-MM-DD)
 * @param int $masa_garansi - Masa garansi dalam hari
 * @return string - Tanggal expired (YYYY-MM-DD)
 */
function generateTanggalExpired($tanggal_service, $masa_garansi) {
    $date = new DateTime($tanggal_service);
    $date->add(new DateInterval('P' . $masa_garansi . 'D'));
    return $date->format('Y-m-d');
}

/**
 * Check apakah user sudah login
 * @return bool - True jika sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirect ke halaman login jika belum login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

/**
 * Format rupiah
 * @param int $amount - Nominal
 * @return string - Format rupiah
 */
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 * @param string $date - Tanggal (YYYY-MM-DD)
 * @return string - Tanggal format Indonesia
 */
function formatTanggalIndonesia($date) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $tanggal = explode('-', $date);
    return $tanggal[2] . ' ' . $bulan[(int)$tanggal[1]] . ' ' . $tanggal[0];
}

/**
 * Log activity (optional - untuk tracking)
 * @param string $action - Aksi yang dilakukan
 * @param string $description - Deskripsi detail
 */
function logActivity($action, $description = '') {
    // Implementasi logging bisa ditambahkan di sini
    // Misalnya simpan ke file log atau database
    $log_file = '../logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
    $log_entry = "[$timestamp] User: $user_id | Action: $action | $description\n";
    
    // Buat folder logs jika belum ada
    if (!file_exists('../logs')) {
        mkdir('../logs', 0755, true);
    }
    
    // Tulis ke file log
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
?>