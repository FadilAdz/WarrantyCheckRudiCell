<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';

$kode = $argv[1] ?? '';
if (empty($kode)) {
    echo "Usage: php test_fetch_garansi.php <kode>\n";
    exit(1);
}

$conn = getConnection();
$sql = "SELECT * FROM service_records WHERE LOWER(TRIM(kode_garansi)) = LOWER(TRIM(?)) LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $kode);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if (!$res) { echo "Not found\n"; exit(0); }

$log = [];
$biaya = isset($res['biaya_int']) ? intval($res['biaya_int']) : 0;
$log[] = "biaya_int={$biaya}";
if ($biaya <= 0 && !empty($res['data_transaksi_encrypted']) && function_exists('decryptData')) {
    $dec = decryptData($res['data_transaksi_encrypted']);
    $log[] = "decrypted_snip=" . substr($dec,0,200);
    $json = json_decode($dec, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        $biaya = intval($json['biaya_service'] ?? ($json['biaya'] ?? ($json['amount'] ?? 0)));
        $log[] = "json_biaya={$biaya}";
    } else {
        if (preg_match('/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $dec, $m)) {
            $num = preg_replace('/[^\d]/', '', $m[1]);
            $biaya = intval($num);
            $log[] = "fallback_biaya={$biaya}";
        }
    }
}

echo implode("\n", $log) . "\n";
