<?php
require_once __DIR__ . '/../config/database.php';

$conn = getConnection();
$id = $argv[1] ?? '';
if (empty($id)) {
    echo "Usage: php inspect_service.php <id>\n";
    exit(1);
}

$stmt = $conn->prepare('SELECT id, kode_garansi, data_transaksi_encrypted, CHAR_LENGTH(data_transaksi_encrypted) as enc_len, IFNULL(biaya_int,0) as biaya_int FROM service_records WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if (!$res) {
    echo "No record with id=$id\n";
    exit(0);
}

echo "id={$res['id']} kode={$res['kode_garansi']} enc_len={$res['enc_len']}\n";
echo "raw:[{$res['data_transaksi_encrypted']}]\n";
// Try to base64 decode and show first bytes
$decoded = base64_decode($res['data_transaksi_encrypted']);
if ($decoded === false) {
    echo "base64_decode: FAILED\n";
} else {
    echo "base64_decode length=" . strlen($decoded) . "\n";
    echo "decoded snippet: " . substr($decoded,0,200) . "\n";
}

// Attempt to decrypt using our helper
require_once __DIR__ . '/../config/encryption.php';
if (function_exists('decryptData')) {
    $decrypted = decryptData($res['data_transaksi_encrypted']);
    echo "decryptData result: [" . substr($decrypted,0,200) . "]\n";
} else {
    echo "decryptData not available\n";
}

$conn->close();
