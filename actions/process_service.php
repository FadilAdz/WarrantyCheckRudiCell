<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/encryption.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../pages/input_service.php');
    exit;
}

// Ambil data dari form
$nomor_hp = $_POST['nomor_hp'] ?? '';
$jenis_hp = $_POST['jenis_hp'] ?? '';
$keluhan = $_POST['keluhan'] ?? '';
$tanggal_service = $_POST['tanggal_service'] ?? '';
$masa_garansi_hari = $_POST['masa_garansi_hari'] ?? 30;
// Sanitize biaya_service - strip non-digits and cast to int
$rawBiaya = $_POST['biaya_service'] ?? '';
$biaya_service = intval(preg_replace('/\D/', '', (string)$rawBiaya));
$catatan_transaksi = $_POST['catatan_transaksi'] ?? '';

// Validasi input
if (empty($nomor_hp) || empty($jenis_hp) || empty($keluhan) || empty($tanggal_service)) {
    header('Location: ../pages/input_service.php?error=' . urlencode('Semua field wajib diisi!'));
    exit;
}

// Validasi format nomor HP
if (!isValidPhoneNumber($nomor_hp)) {
    header('Location: ../pages/input_service.php?error=' . urlencode('Format nomor HP tidak valid! Gunakan format 08xx atau 628xx'));
    exit;
}

// Normalisasi nomor HP ke format internasional (62...) sebelum disimpan
$nomor_hp = formatPhone($nomor_hp);

try {
    $conn = getConnection();
    
    // Generate kode garansi unik
    $kode_garansi = generateKodeGaransi();
    
    // Enkripsi data sensitif
    $nomor_hp_encrypted = encryptData($nomor_hp);
    
    // Buat data transaksi dalam format JSON, lalu enkripsi
    $data_transaksi = json_encode([
        'biaya_service' => $biaya_service,
        'catatan' => $catatan_transaksi,
        'metode_pembayaran' => 'Cash', // Bisa ditambahkan field di form
        'created_by' => $_SESSION['nama_lengkap']
    ]);
    $data_transaksi_encrypted = encryptData($data_transaksi);
    
    // Hitung tanggal expired menggunakan DateTime untuk akurasi
    $tanggal_service_obj = new DateTime($tanggal_service);
    $tanggal_service_obj->add(new DateInterval('P' . $masa_garansi_hari . 'D'));
    $tanggal_expired = $tanggal_service_obj->format('Y-m-d');
    
    // Debug: Log tanggal yang dihitung
    error_log("Tanggal service: $tanggal_service, Masa garansi: $masa_garansi_hari hari, Tanggal expired: $tanggal_expired");
    
    // Insert ke database (include denormalized biaya_int)
    $sql = "INSERT INTO service_records 
            (kode_garansi, nomor_hp_encrypted, jenis_hp, keluhan, 
             tanggal_service, masa_garansi_hari, tanggal_expired, data_transaksi_encrypted, biaya_int, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = executeQuery(
        $conn, 
        $sql, 
        [
            $kode_garansi,
            $nomor_hp_encrypted,
            $jenis_hp,
            $keluhan,
            $tanggal_service,
            $masa_garansi_hari,
            $tanggal_expired,
            $data_transaksi_encrypted,
            $biaya_service,
            $_SESSION['user_id']
        ],
        'sssssissii'
    );
    
    if ($stmt) {
        $service_id = $conn->insert_id;
        $conn->close();
        // Redirect ke halaman cetak struk PDF
        header('Location: print_receipt.php?id=' . $service_id);
        exit;
    } else {
        throw new Exception('Gagal menyimpan data ke database');
    }
    
} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: ../pages/input_service.php?error=' . urlencode('Terjadi kesalahan: ' . $e->getMessage()));
    exit;
}
?>
