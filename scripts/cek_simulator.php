<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';

$kode = $argv[1] ?? '';
if (!$kode) { echo "Usage: php cek_simulator.php <kode>\n"; exit(1); }

$conn = getConnection();
$sql = "SELECT * FROM service_records WHERE LOWER(TRIM(kode_garansi)) = LOWER(TRIM(?)) LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $kode);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if (!$res) { echo "Not found in service_records\n"; exit; }

$result = $res;
// status
$today = new DateTime();
$expiredDate = isset($result['tanggal_expired']) ? new DateTime($result['tanggal_expired']) : null;
$status = ($expiredDate && $expiredDate >= $today) ? 'aktif' : 'expired';

// Decrypt phone
$result['nomor_hp'] = '';
if (!empty($result['nomor_hp_encrypted']) && function_exists('decryptData')) {
    try { $result['nomor_hp'] = decryptData($result['nomor_hp_encrypted']); } catch (Throwable $e) { $result['nomor_hp'] = ''; }
}

// biaya via biaya_int
$result['biaya_service'] = isset($result['biaya_int']) ? intval($result['biaya_int']) : 0;

// output summary
echo "status=$status\n";
echo "kode=" . ($result['kode_garansi'] ?? '') . "\n";
echo "nomor_hp=" . ($result['nomor_hp'] ?? '') . "\n";
echo "jenis_hp=" . ($result['jenis_hp'] ?? '') . "\n";
echo "keluhan=" . ($result['keluhan'] ?? '') . "\n";
echo "tanggal_service=" . ($result['tanggal_service'] ?? '') . "\n";
echo "tanggal_expired=" . ($result['tanggal_expired'] ?? '') . "\n";
echo "biaya_service=" . ($result['biaya_service'] ?? '') . "\n";

?>