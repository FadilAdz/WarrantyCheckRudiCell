<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/encryption.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$biaya = trim($_POST['biaya'] ?? '');

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

// Normalize biaya to integer (strip non-digits)
$numeric = intval(preg_replace('/\D/', '', $biaya));

try {
    $conn = getConnection();

    $sql = "SELECT data_transaksi_encrypted FROM service_records WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $dataArr = [];
    if ($row && !empty($row['data_transaksi_encrypted']) && function_exists('decryptData')) {
        $decrypted = decryptData($row['data_transaksi_encrypted']);
        $json = json_decode($decrypted, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $dataArr = $json;
        }
    }

    // Update biaya
    $dataArr['biaya_service'] = $numeric;
    // Keep existing fields or set defaults
    if (empty($dataArr['metode_pembayaran'])) $dataArr['metode_pembayaran'] = 'Cash';
    if (empty($dataArr['created_by'])) $dataArr['created_by'] = $_SESSION['nama_lengkap'] ?? ($_SESSION['username'] ?? 'admin');

    $newJson = json_encode($dataArr);
    $newEncrypted = encryptData($newJson);

    $updateSql = "UPDATE service_records SET data_transaksi_encrypted = ?, biaya_int = ? WHERE id = ?";
    $uStmt = executeQuery($conn, $updateSql, [$newEncrypted, $numeric, $id], 'sii');

    if ($uStmt) {
        // Log
        $logFile = __DIR__ . '/../logs/update_biaya.log';
        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] User {$_SESSION['username']} set biaya for id=$id to $numeric\n", FILE_APPEND);

        // Return formatted value
        require_once '../config/encryption.php';
        $formatted = formatRupiah($numeric);

        echo json_encode(['success' => true, 'message' => 'Biaya berhasil diperbarui', 'cost' => $formatted]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database']);
        exit;
    }

} catch (Exception $e) {
    error_log('update_biaya error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
    exit;
}
