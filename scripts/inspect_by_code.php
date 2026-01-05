<?php
require_once __DIR__ . '/../config/database.php';
$kode = $argv[1] ?? '';
if (empty($kode)) { echo "Usage: php inspect_by_code.php <kode>\n"; exit(1); }
$conn = getConnection();
$stmt = $conn->prepare('SELECT id,kode_garansi,nomor_hp_encrypted,jenis_hp,keluhan,tanggal_service,tanggal_expired,data_transaksi_encrypted,biaya_int FROM service_records WHERE kode_garansi=? LIMIT 1');
$stmt->bind_param('s', $kode);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if (!$res) { echo "Not found\n"; exit; }
var_export($res);
echo "\n";
if (!empty($res['data_transaksi_encrypted'])) {
    require_once __DIR__ . '/../config/encryption.php';
    $dec = decryptData($res['data_transaksi_encrypted']);
    echo "decrypted trans: " . substr($dec,0,400) . "\n";
}
if (!empty($res['nomor_hp_encrypted'])) {
    require_once __DIR__ . '/../config/encryption.php';
    echo "decrypted phone: " . decryptData($res['nomor_hp_encrypted']) . "\n";
}
?>