<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/encryption.php';

$conn = getConnection();

// Ambil semua data garansi aktif
$sql = "SELECT * FROM service_records ORDER BY tanggal_service DESC";
$data = fetchData($conn, $sql);

$conn->close();

// Set header untuk download Excel
$filename = "Laporan_Garansi_Aktif_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output HTML table yang akan diinterpretasikan sebagai Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Garansi Aktif</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .header {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>RUDI CELL - LAPORAN GARANSI AKTIF</h2>
        <p>Tanggal Export: <?= date('d F Y H:i:s') ?></p>
        <p>Total Data: <?= count($data) ?> garansi aktif</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Garansi</th>
                <th>Nama Customer</th>
                <th>Nomor HP</th>
                <th>Jenis HP</th>
                <th>Keluhan</th>
                <th>Tanggal Service</th>
                <th>Masa Garansi (Hari)</th>
                <th>Tanggal Expired</th>
                <th>Sisa Hari</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $index => $row): ?>
                <?php
                // Dekripsi nomor HP
                $nomorHP = decryptData($row['nomor_hp_encrypted']);
                
                // Hitung sisa hari
                $today = new DateTime();
                $expired = new DateTime($row['tanggal_expired']);
                $sisaHari = $today->diff($expired)->days;
                
                $isActive = strtotime($row['tanggal_expired']) >= strtotime(date('Y-m-d'));
                ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($row['kode_garansi']) ?></td>
                    <td><?= htmlspecialchars($row['nama_customer']) ?></td>
                    <td><?= htmlspecialchars($nomorHP) ?></td>
                    <td><?= htmlspecialchars($row['jenis_hp']) ?></td>
                    <td><?= htmlspecialchars($row['keluhan']) ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal_service'])) ?></td>
                    <td><?= $row['masa_garansi_hari'] ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal_expired'])) ?></td>
                    <td><?= $sisaHari ?></td>
                    <td><?= $isActive ? 'AKTIF' : 'EXPIRED' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 30px;">
        <p><strong>Catatan:</strong></p>
        <ul>
            <li>Data ini diekspor menggunakan sistem enkripsi AES-256 untuk melindungi informasi sensitif</li>
            <li>Nomor HP customer telah didekripsi untuk keperluan laporan</li>
            <li>Harap simpan file ini dengan aman dan jangan disebarkan ke pihak yang tidak berwenang</li>
        </ul>
    </div>
</body>
</html>
