<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';

$conn = getConnection();
$log = __DIR__ . '/../logs/migrate_biaya_int.log';
file_put_contents($log, "[".date('Y-m-d H:i:s')."] Migration started\n", FILE_APPEND);

// 1) Add column if not exists
$check = $conn->query("SHOW COLUMNS FROM service_records LIKE 'biaya_int'");
if ($check && $check->num_rows === 0) {
    $alter = $conn->query("ALTER TABLE service_records ADD COLUMN biaya_int INT NOT NULL DEFAULT 0");
    if ($alter) file_put_contents($log, "[".date('Y-m-d H:i:s')."] Column biaya_int added\n", FILE_APPEND);
    else file_put_contents($log, "[".date('Y-m-d H:i:s')."] Failed to add biaya_int: " . $conn->error . "\n", FILE_APPEND);
} else {
    file_put_contents($log, "[".date('Y-m-d H:i:s')."] Column biaya_int already exists\n", FILE_APPEND);
}

// 2) Populate biaya_int from decrypted payload when possible
$stmt = $conn->prepare('SELECT id, data_transaksi_encrypted FROM service_records');
$stmt->execute();
$res = $stmt->get_result();
$updated = 0;
while ($row = $res->fetch_assoc()) {
    $id = intval($row['id']);
    $biaya = 0;
    if (!empty($row['data_transaksi_encrypted']) && function_exists('decryptData')) {
        try {
            $dec = decryptData($row['data_transaksi_encrypted']);
            $json = json_decode($dec, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json) && isset($json['biaya_service'])) {
                $biaya = intval(preg_replace('/\D/', '', (string)$json['biaya_service']));
            } else {
                // try fallback numeric
                if (preg_match('/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $dec, $m)) {
                    $num = preg_replace('/[^\d]/', '', $m[1]);
                    $biaya = intval($num);
                }
            }
        } catch (Throwable $ex) {
            // ignore
        }
    }

    if ($biaya > 0) {
        $u = $conn->prepare('UPDATE service_records SET biaya_int = ? WHERE id = ?');
        $u->bind_param('ii', $biaya, $id);
        if ($u->execute()) {
            $updated++;
            file_put_contents($log, "[".date('Y-m-d H:i:s')."] Set biaya_int=$biaya for id=$id\n", FILE_APPEND);
        }
    }
}

file_put_contents($log, "[".date('Y-m-d H:i:s')."] Migration finished. Updated=$updated rows.\n", FILE_APPEND);
echo "Migration finished. Updated=$updated rows.\n";
$conn->close();
