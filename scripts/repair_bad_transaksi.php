<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';

$conn = getConnection();
$logFile = __DIR__ . '/../logs/repair_transaksi.log';

$sql = "SELECT id, kode_garansi, data_transaksi_encrypted FROM service_records ORDER BY id ASC";
$res = $conn->query($sql);
$updated = 0;
$rows = [];
while ($row = $res->fetch_assoc()) {
    $len = strlen($row['data_transaksi_encrypted'] ?? '');
    if ($len <= 2 || $row['data_transaksi_encrypted'] === '0' || $row['data_transaksi_encrypted'] === '' || is_null($row['data_transaksi_encrypted'])) {
        $rows[] = $row;
    }
}

if (empty($rows)) {
    echo "No suspicious records found.\n";
    exit(0);
}

foreach ($rows as $r) {
    $id = intval($r['id']);
    $kode = $r['kode_garansi'];
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Repairing id=$id kode=$kode | old_enc_len=" . strlen($r['data_transaksi_encrypted'] ?? '') . "\n", FILE_APPEND);

    // Build default payload
    $payload = [
        'biaya_service' => 0,
        'catatan' => '',
        'metode_pembayaran' => 'Cash',
        'created_by' => 'system_repair'
    ];
    $json = json_encode($payload);
    $enc = encryptData($json);

    // Update DB
    $stmt = $conn->prepare('UPDATE service_records SET data_transaksi_encrypted = ? WHERE id = ?');
    $stmt->bind_param('si', $enc, $id);
    $ok = $stmt->execute();
    if ($ok) {
        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Repaired id=$id kode=$kode | new_enc_len=" . strlen($enc) . "\n", FILE_APPEND);
        $updated++;
    } else {
        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Failed to repair id=$id kode=$kode\n", FILE_APPEND);
    }
}

$conn->close();

echo "Repair complete. Updated: $updated records.\n";
