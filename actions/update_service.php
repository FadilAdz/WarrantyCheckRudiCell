<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit();
}

if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
    exit();
}

$id = intval($_POST['id'] ?? 0);
$jenis_hp = trim($_POST['jenis_hp'] ?? '');
$nama_customer = trim($_POST['nama_customer'] ?? '');
$keluhan = trim($_POST['keluhan'] ?? '');
$tanggal_service = trim($_POST['tanggal_service'] ?? '');
$masa_garansi = intval($_POST['masa_garansi'] ?? 0);

if ($id <= 0 || empty($jenis_hp) || empty($tanggal_service) || $masa_garansi <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap atau tidak valid.']);
    exit();
}

$dt = DateTime::createFromFormat('Y-m-d', $tanggal_service);
if (!$dt) {
    echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid. Gunakan YYYY-MM-DD.']);
    exit();
}

$dt->modify('+' . $masa_garansi . ' days');
$tanggal_expired = $dt->format('Y-m-d');

try {
    $stmt = $pdo->prepare('UPDATE service_records SET jenis_hp = ?, keluhan = ?, tanggal_service = ?, masa_garansi_hari = ?, tanggal_expired = ?, nama_customer = ? WHERE id = ?');
    $stmt->execute([$jenis_hp, $keluhan, $tanggal_service, $masa_garansi, $tanggal_expired, $nama_customer, $id]);

    // Hitung sisa hari dan badge class
    $today = new DateTime();
    $expired = new DateTime($tanggal_expired);
    $sisa = $today->diff($expired)->days;
    if ($expired < $today) {
        $sisa = 0;
    }
    if ($sisa <= 3) {
        $badgeClass = 'bg-danger';
    } elseif ($sisa <= 7) {
        $badgeClass = 'bg-warning';
    } else {
        $badgeClass = 'bg-success';
    }

    logUserActivity('EDIT_SERVICE', "User {$_SESSION['username']} mengedit service id=$id via AJAX");

    echo json_encode([
        'success' => true,
        'message' => 'Data berhasil diperbarui. ',
        'data' => [
            'id' => $id,
            'jenis_hp' => htmlspecialchars($jenis_hp),
            'nama_customer' => htmlspecialchars($nama_customer),
            'tanggal_service' => date('d/m/Y', strtotime($tanggal_service)),
            'tanggal_expired' => date('d/m/Y', strtotime($tanggal_expired)),
            'sisa' => $sisa,
            'badgeClass' => $badgeClass
        ]
    ]);
    exit();
} catch (PDOException $e) {
    error_log('Update service error (AJAX): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan.']);
    exit();
}
