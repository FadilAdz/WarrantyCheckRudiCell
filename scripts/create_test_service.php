<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';

$conn = getConnection();
$kode = generateKodeGaransi();
$nomor = encryptData('628123456789');
$jenis = 'TestPhone';
$keluhan = 'Test Keluhan';
$tgl = date('Y-m-d');
$masa = 30;
$tglExp = date('Y-m-d', strtotime('+30 days'));
$biaya = 125000;
$payload = json_encode(['biaya_service' => $biaya, 'catatan' => 'test', 'metode_pembayaran' => 'Cash', 'created_by' => 'tester']);
$enc = encryptData($payload);

$sql = "INSERT INTO service_records (kode_garansi, nomor_hp_encrypted, jenis_hp, keluhan, tanggal_service, masa_garansi_hari, tanggal_expired, data_transaksi_encrypted, biaya_int, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = executeQuery($conn, $sql, [$kode, $nomor, $jenis, $keluhan, $tgl, $masa, $tglExp, $enc, $biaya, 1], 'sssssissii');
if ($stmt) {
    echo "Inserted $kode with biaya=$biaya\n";
} else {
    echo "Insert failed\n";
}
