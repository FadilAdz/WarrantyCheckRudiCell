<?php
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();
$sql = "SELECT id, kode_garansi, CHAR_LENGTH(IFNULL(data_transaksi_encrypted,'')) as enc_len, data_transaksi_encrypted FROM service_records ORDER BY id ASC";
$res = $conn->query($sql);
$bad = [];
while ($row = $res->fetch_assoc()) {
    $len = intval($row['enc_len']);
    if ($len <= 2 || $row['data_transaksi_encrypted'] === '0' || $row['data_transaksi_encrypted'] === null || $row['data_transaksi_encrypted'] === '') {
        $bad[] = $row;
    }
}
if (empty($bad)) {
    echo "No suspicious records found.\n";
} else {
    echo "Found " . count($bad) . " suspicious records:\n";
    foreach ($bad as $b) {
        echo "id={$b['id']} kode={$b['kode_garansi']} enc_len={$b['enc_len']}\n";
    }
}
$conn->close();
