<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/encryption.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    header('Location: ../pages/garansi_aktif.php');
    exit;
}

$conn = getConnection();

$sql = "SELECT sr.*, u.nama_lengkap as created_by_name
        FROM service_records sr
        LEFT JOIN users u ON sr.created_by = u.id
        WHERE sr.id = ?";
$data = fetchData($conn, $sql, [$id], 'i');

if (empty($data)) {
    header('Location: ../pages/garansi_aktif.php');
    exit;
}

$record = $data[0];

// Dekripsi data sensitif
$record['nomor_hp'] = '';
$transaksi = [];
try {
    if (!empty($record['nomor_hp_encrypted']) && function_exists('decryptData')) {
        $record['nomor_hp'] = decryptData($record['nomor_hp_encrypted']);
    }
    if (!empty($record['data_transaksi_encrypted']) && function_exists('decryptData')) {
        $dec = decryptData($record['data_transaksi_encrypted']);
        $transaksi = json_decode($dec, true) ?: [];
    }
} catch (Throwable $ex) {
    // fallback: biarkan nilai default kosong
}

$conn->close();

// Hitung status garansi
$isActive = strtotime($record['tanggal_expired']) >= strtotime(date('Y-m-d'));

// Configure Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Courier');
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);

// Generate HTML content for PDF
$biaya = intval($record['biaya_int'] ?? $transaksi['biaya_service'] ?? 0);
$biaya_formatted = $biaya > 0 ? 'Rp ' . number_format($biaya, 0, ',', '.') : 'Rp 0';
$biaya_service = $biaya > 0 ? 'Rp ' . number_format($biaya, 0, ',', '.') : 'Belum ditentukan';

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Struk Service - Rudi Cell</title>
    <style>
        body {
            font-family: "Courier New", monospace;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            max-width: 400px;
            margin: 0 auto;
            color: #000;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            font-size: 10px;
        }
        .receipt-body {
            margin: 20px 0;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 2px 0;
        }
        .receipt-row.border-bottom {
            border-bottom: 1px dashed #ccc;
        }
        .receipt-label {
            font-weight: bold;
        }
        .receipt-value {
            text-align: right;
        }
        .total {
            font-weight: bold;
            font-size: 14px;
            margin: 15px 0;
            padding: 10px 0;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .warranty-info {
            background: #f9f9f9;
            padding: 10px;
            margin: 15px 0;
            border-left: 4px solid ' . ($isActive ? '#4CAF50' : '#f44336') . ';
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1> RUDI CELL</h1>
        <p>Jasa Service Handphone & Gadget</p>
        <p>STRUK PEMBAYARAN SERVICE</p>
    </div>

    <div class="receipt-body">
        <div class="receipt-row">
            <span class="receipt-label">No. Transaksi:</span>
            <span class="receipt-value">#' . htmlspecialchars($record['id']) . '</span>
        </div>

        <div class="receipt-row">
            <span class="receipt-label">Kode Garansi:</span>
            <span class="receipt-value">' . htmlspecialchars($record['kode_garansi']) . '</span>
        </div>

        <div class="receipt-row border-bottom">
            <span class="receipt-label">Tanggal:</span>
            <span class="receipt-value">' . date('d/m/Y H:i', strtotime($record['created_at'])) . '</span>
        </div>

        <div class="receipt-row">
            <span class="receipt-label">Customer:</span>
            <span class="receipt-value">' . htmlspecialchars($record['nomor_hp']) . '</span>
        </div>

        <div class="receipt-row">
            <span class="receipt-label">Jenis HP:</span>
            <span class="receipt-value">' . htmlspecialchars($record['jenis_hp']) . '</span>
        </div>

        <div class="receipt-row border-bottom">
            <span class="receipt-label">Keluhan:</span>
            <span class="receipt-value" style="max-width: 200px; word-wrap: break-word; text-align: left;">
                ' . htmlspecialchars(substr($record['keluhan'], 0, 50)) . (strlen($record['keluhan']) > 50 ? '...' : '') . '
            </span>
        </div>

        <div class="receipt-row">
            <span class="receipt-label">Biaya Service:</span>
            <span class="receipt-value">' . $biaya_service . '</span>
        </div>

        <div class="receipt-row">
            <span class="receipt-label">Metode Bayar:</span>
            <span class="receipt-value">' . htmlspecialchars($transaksi['metode_pembayaran'] ?? 'Cash') . '</span>
        </div>

        <div class="total">
            <div class="receipt-row">
                <span class="receipt-label">TOTAL:</span>
                <span class="receipt-value">' . $biaya_formatted . '</span>
            </div>
        </div>
    </div>

    <div class="warranty-info">
        <strong>INFORMASI GARANSI:</strong><br>
        Masa Garansi: ' . $record['masa_garansi_hari'] . ' hari<br>
        Berlaku hingga: ' . date('d F Y', strtotime($record['tanggal_expired'])) . '<br>
        Status: <span style="color: ' . ($isActive ? 'green' : 'red') . '; font-weight: bold;">
            ' . ($isActive ? 'AKTIF' : 'EXPIRED') . '
        </span><br>
        <small>Kode garansi wajib dibawa saat klaim garansi</small>
    </div>

    <div class="footer">
        <p><strong>TERIMA KASIH ATAS KUNJUNGAN ANDA</strong></p>
        <p>RUDI CELL - Solusi Terpercaya untuk Service HP Anda</p>
        <p>Dicetak oleh: ' . htmlspecialchars($_SESSION['nama_lengkap']) . ' | ' . date('d/m/Y H:i:s') . '</p>
    </div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="struk-service-' . $record['kode_garansi'] . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $dompdf->output();
