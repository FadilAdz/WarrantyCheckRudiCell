<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/encryption.php';

$conn = getConnection();

// Ambil statistik
$bulanIni = date('Y-m');
$sql = "SELECT COUNT(*) as total FROM service_records WHERE DATE_FORMAT(tanggal_service, '%Y-%m') = ?";
$result = fetchData($conn, $sql, [$bulanIni], 's');
$totalServiceBulanIni = $result[0]['total'] ?? 0;

$sql = "SELECT COUNT(*) as total FROM service_records WHERE tanggal_expired >= CURDATE()";
$result = fetchData($conn, $sql);
$totalGaransiAktif = $result[0]['total'] ?? 0;

$sql = "SELECT COUNT(*) as total FROM trash";
$result = fetchData($conn, $sql);
$totalGaransiExpired = $result[0]['total'] ?? 0;

// Ambil data garansi aktif (10 terakhir untuk summary)
$sql = "SELECT * FROM service_records ORDER BY tanggal_service DESC LIMIT 10";
$data = fetchData($conn, $sql);

$conn->close();

// Set header untuk PDF (menggunakan HTML to PDF browser)
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan - Rudi Cell</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .info-box {
            background: #f4f4f4;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .stat-card {
            background: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
            width: 30%;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 28px;
        }
        .stat-card p {
            margin: 5px 0 0 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .btn-print {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 20px 0;
        }
        .btn-print:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
    
    <div class="header">
        <h1>📱 RUDI CELL</h1>
        <h2>Laporan Bulanan Warranty Management System</h2>
        <p>Periode: <?= date('F Y') ?></p>
        <p>Tanggal Export: <?= date('d F Y H:i:s') ?></p>
    </div>
    
    <div class="info-box">
        <strong>ℹ️ Informasi Sistem:</strong><br>
        Laporan ini dihasilkan dari sistem manajemen garansi dengan enkripsi AES-256 untuk melindungi data sensitif pelanggan.
    </div>
    
    <h3>📊 Statistik Bulan Ini</h3>
    <div class="stats">
        <div class="stat-card">
            <h3><?= $totalServiceBulanIni ?></h3>
            <p>Service Bulan Ini</p>
        </div>
        <div class="stat-card" style="background: #2196F3;">
            <h3><?= $totalGaransiAktif ?></h3>
            <p>Garansi Aktif</p>
        </div>
        <div class="stat-card" style="background: #f44336;">
            <h3><?= $totalGaransiExpired ?></h3>
            <p>Garansi Expired</p>
        </div>
    </div>
    
    <h3>📋 Daftar 10 Service Terbaru</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Garansi</th>
                <th>Customer</th>
                <th>Jenis HP</th>
                <th>Tanggal Service</th>
                <th>Expired</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Belum ada data</td>
                </tr>
            <?php else: ?>
                <?php foreach ($data as $index => $row): ?>
                    <?php
                    $isActive = strtotime($row['tanggal_expired']) >= strtotime(date('Y-m-d'));
                    ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><small><?= htmlspecialchars($row['kode_garansi']) ?></small></td>
                        <td><?= htmlspecialchars($row['nama_customer']) ?></td>
                        <td><?= htmlspecialchars($row['jenis_hp']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_service'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_expired'])) ?></td>
                        <td style="color: <?= $isActive ? 'green' : 'red' ?>; font-weight: bold;">
                            <?= $isActive ? '✓ AKTIF' : '✗ EXPIRED' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="info-box">
        <strong>🔐 Keamanan Data:</strong><br>
        • Nomor HP pelanggan dienkripsi dengan AES-256-CBC<br>
        • Data transaksi dilindungi enkripsi end-to-end<br>
        • Sistem otomatis memindahkan garansi expired ke trash<br>
        • Akses sistem hanya untuk staff yang terautentikasi
    </div>
    
    <div class="footer">
        <p><strong>RUDI CELL - Warranty Management System</strong></p>
        <p>Document ini bersifat rahasia dan hanya untuk keperluan internal</p>
        <p>Generated by: <?= htmlspecialchars($_SESSION['nama_lengkap']) ?> | <?= date('d/m/Y H:i:s') ?></p>
    </div>
    
    <script>
        // Auto print dialog jika diperlukan (opsional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
