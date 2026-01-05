<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/encryption.php';

// Jalankan auto-move expired garansi ke trash sebelum proses cek garansi
require_once '../actions/move_to_trash.php';

$csrf = generateCSRFToken();
$kode_garansi = $_GET['kode'] ?? '';
$result = null;
$error = '';

// Ensure display variables are always defined to avoid undefined variable warnings
$display_kode = $display_nomor_hp = $display_jenis = $display_keluhan = '';
$display_tanggal_service = $display_tanggal_expired = $display_biaya = '';


if (!empty($kode_garansi)) {
    $conn = getConnection();

    // Logging incoming search
    $logFileGlobal = __DIR__ . '/../logs/cek_garansi.log';
    // Ensure we have a $logFile alias used by internal logging calls
    $logFile = $logFileGlobal;
    file_put_contents($logFileGlobal, "[".date('Y-m-d H:i:s')."] Search request kode=" . trim($kode_garansi) . " | validate=" . (validateKodeGaransi($kode_garansi) ? 'ok' : 'invalid') . "\n", FILE_APPEND);

    // Cek di garansi aktif (case-insensitive, trimmed)
    $sql = "SELECT * FROM service_records WHERE LOWER(TRIM(kode_garansi)) = LOWER(TRIM(?))";
    $data = fetchData($conn, $sql, [$kode_garansi], 's');
    file_put_contents($logFileGlobal, "[".date('Y-m-d H:i:s')."] Query returned rows=" . count($data) . " for service_records\n", FILE_APPEND);

    if (!empty($data)) {
        $result = $data[0];
        // Tentukan status berdasarkan tanggal_expired (compare date-only)
        $today = new DateTime();
        $today->setTime(0,0,0);
        
        // Ambil tanggal_expired dan trim whitespace
        $expiredRaw = isset($result['tanggal_expired']) ? trim($result['tanggal_expired']) : null;
        
        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Checking status for id={$result['id']}: expired_raw=" . ($expiredRaw ?? 'NULL') . ", today=" . $today->format('Y-m-d') . "\n", FILE_APPEND);

        // Validasi dan hitung status - Initialize status first
        $result['status'] = 'unknown';
        
        if (empty($expiredRaw) || $expiredRaw === '' || $expiredRaw === '0000-00-00' || $expiredRaw === '1970-01-01') {
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Empty or invalid expired date for id={$result['id']}: raw={$expiredRaw}\n", FILE_APPEND);
            $result['status'] = 'unknown';
        } else {
            try {
                // Parse tanggal dengan format Y-m-d
                $expiredDate = DateTime::createFromFormat('Y-m-d', $expiredRaw);
                
                // Jika createFromFormat gagal, coba dengan format default
                if (!$expiredDate) {
                    $expiredDate = new DateTime($expiredRaw);
                }
                
                $expiredDate->setTime(0,0,0);
                
                // Bandingkan tanggal: aktif jika tanggal_expired >= today
                $isActive = $expiredDate >= $today;
                $result['status'] = $isActive ? 'aktif' : 'expired';
                
                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Status computed for id={$result['id']}: {$result['status']} (expired={$expiredDate->format('Y-m-d')}, today={$today->format('Y-m-d')}, is_active=" . ($isActive ? 'true' : 'false') . ")\n", FILE_APPEND);
                // Ensure status is explicitly set as string
                $result['status'] = (string)$result['status'];
            } catch (Exception $ex) {
                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] tanggal_expired parse error id={$result['id']}: {$expiredRaw} - " . $ex->getMessage() . "\n", FILE_APPEND);
                
                // Fallback: coba dengan strtotime jika DateTime gagal
                $timestamp = strtotime($expiredRaw);
                if ($timestamp !== false) {
                    $todayTimestamp = strtotime($today->format('Y-m-d'));
                    $isActive = $timestamp >= $todayTimestamp;
                    $result['status'] = $isActive ? 'aktif' : 'expired';
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Fallback strtotime success for id={$result['id']}: status={$result['status']}\n", FILE_APPEND);
                } else {
                    $result['status'] = 'unknown';
                }
            } catch (Throwable $ex) {
                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] tanggal_expired throwable error id={$result['id']}: {$expiredRaw} - " . $ex->getMessage() . "\n", FILE_APPEND);
                $result['status'] = 'unknown';
            }
        }
        
        // Log and safely decrypt data sensitif
        $logFile = __DIR__ . '/../logs/cek_garansi.log';
        $encPhoneLen = isset($result['nomor_hp_encrypted']) ? strlen($result['nomor_hp_encrypted']) : 0;
        $encTransLen = isset($result['data_transaksi_encrypted']) ? strlen($result['data_transaksi_encrypted']) : 0;
        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Found record id={$result['id']} kode={$result['kode_garansi']} | enc_len phone={$encPhoneLen} trans={$encTransLen} | status=" . ($result['status'] ?? 'NOT SET') . "\n", FILE_APPEND);

        // Nomor HP
        $result['nomor_hp'] = '';
        if (!empty($result['nomor_hp_encrypted']) && function_exists('decryptData')) {
            try {
                $result['nomor_hp'] = decryptData($result['nomor_hp_encrypted']);
            } catch (Throwable $ex) {
                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] decryptData error phone id={$result['id']}: " . $ex->getMessage() . "\n", FILE_APPEND);
                $result['nomor_hp'] = '';
            }
        }

        // Data transaksi
        // Prefer biaya_int when available (denormalized), fall back to decrypting payload
        $result['biaya_service'] = isset($result['biaya_int']) ? intval($result['biaya_int']) : 0;
        $result['catatan'] = '';
        $decryptedTrans = '';

        if ($result['biaya_service'] <= 0 && !empty($result['data_transaksi_encrypted']) && function_exists('decryptData')) {
            try {
                $decryptedTrans = decryptData($result['data_transaksi_encrypted']);
                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Decrypted transaksi id={$result['id']} (truncated): " . substr($decryptedTrans,0,1000) . "\n", FILE_APPEND);
                $json = json_decode($decryptedTrans, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $result['biaya_service'] = intval($json['biaya_service'] ?? ($json['biaya'] ?? ($json['amount'] ?? 0)));
                    $result['catatan'] = $json['catatan'] ?? '';
                    // Log keys for visibility
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Decoded keys for id={$result['id']}: " . json_encode(array_keys($json)) . " | biaya=" . json_encode($result['biaya_service']) . "\n", FILE_APPEND);
                } else {
                    // fallback: try extract numeric
                    if (preg_match('/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $decryptedTrans, $m)) {
                        $num = preg_replace('/[^\d]/', '', $m[1]);
                        $result['biaya_service'] = intval($num);
                        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Fallback extracted biaya for id={$result['id']}: " . $result['biaya_service'] . "\n", FILE_APPEND);
                    } else {
                        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] JSON decode failed and no numeric fallback for id={$result['id']}\n", FILE_APPEND);
                    }
                }
            } catch (Throwable $ex) {
                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] decryptData error transaksi id={$result['id']}: " . $ex->getMessage() . "\n", FILE_APPEND);
            }
        } else {
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Using biaya_int for id={$result['id']}: " . $result['biaya_service'] . "\n", FILE_APPEND);
        }
        
    } else {
        // Cek di trash
        $sql = "SELECT * FROM trash WHERE LOWER(TRIM(kode_garansi)) = LOWER(TRIM(?))";
        $data = fetchData($conn, $sql, [$kode_garansi], 's');
        file_put_contents($logFileGlobal, "[".date('Y-m-d H:i:s')."] Query returned rows=" . count($data) . " for trash\n", FILE_APPEND);
        
        if (!empty($data)) {
            $result = $data[0];
            $result['status'] = 'expired';

            // Log and safely decrypt data sensitif
            $logFile = __DIR__ . '/../logs/cek_garansi.log';
            $encPhoneLen = isset($result['nomor_hp_encrypted']) ? strlen($result['nomor_hp_encrypted']) : 0;
            $encTransLen = isset($result['data_transaksi_encrypted']) ? strlen($result['data_transaksi_encrypted']) : 0;
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Found (trash) id={$result['id']} kode={$result['kode_garansi']} | enc_len phone={$encPhoneLen} trans={$encTransLen}\n", FILE_APPEND);

            // Nomor HP
            $result['nomor_hp'] = '';
            if (!empty($result['nomor_hp_encrypted']) && function_exists('decryptData')) {
                try {
                    $result['nomor_hp'] = decryptData($result['nomor_hp_encrypted']);
                } catch (Throwable $ex) {
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] decryptData error phone(trash) id={$result['id']}: " . $ex->getMessage() . "\n", FILE_APPEND);
                    $result['nomor_hp'] = '';
                }
            }

            // Data transaksi
            $result['biaya_service'] = 0;
            $result['catatan'] = '';
            $decryptedTrans = '';
            if (!empty($result['data_transaksi_encrypted']) && function_exists('decryptData')) {
                try {
                    $decryptedTrans = decryptData($result['data_transaksi_encrypted']);
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Decrypted transaksi (trash) id={$result['id']} (truncated): " . substr($decryptedTrans,0,1000) . "\n", FILE_APPEND);
                    $json = json_decode($decryptedTrans, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        $result['biaya_service'] = $json['biaya_service'] ?? ($json['biaya'] ?? ($json['amount'] ?? 0));
                        $result['catatan'] = $json['catatan'] ?? '';
                        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Decoded keys (trash) for id={$result['id']}: " . json_encode(array_keys($json)) . " | biaya=" . json_encode($result['biaya_service']) . "\n", FILE_APPEND);
                    } else {
                        if (preg_match('/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $decryptedTrans, $m)) {
                            $num = preg_replace('/[^\d]/', '', $m[1]);
                            $result['biaya_service'] = intval($num);
                            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Fallback extracted biaya (trash) for id={$result['id']}: " . $result['biaya_service'] . "\n", FILE_APPEND);
                        } else {
                            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] JSON decode failed and no numeric fallback (trash) for id={$result['id']}\n", FILE_APPEND);
                        }
                    }
                } catch (Throwable $ex) {
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] decryptData error transaksi(trash) id={$result['id']}: " . $ex->getMessage() . "\n", FILE_APPEND);
                }
            } else {
                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] No transaksi_encrypted present (trash) for id={$result['id']}\n", FILE_APPEND);
            }
        } else {
            $error = 'Kode garansi tidak ditemukan!';
        }
    }
    
    // Prepare display-safe variables to avoid template inconsistencies
    // Simpan tanggal_expired asli sebelum diproses untuk digunakan di bagian display
    $original_tanggal_expired = null;
    if (!empty($result) && isset($result['tanggal_expired'])) {
        $original_tanggal_expired = trim((string)$result['tanggal_expired']);
    }
    
    if (!empty($result)) {
        // Debug: Log status sebelum prepare display
        $logFile = __DIR__ . '/../logs/cek_garansi.log';
        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Before display prep: status=" . ($result['status'] ?? 'NOT SET') . " (type: " . gettype($result['status'] ?? null) . ")\n", FILE_APPEND);
        
        $display_kode = htmlspecialchars($result['kode_garansi'] ?? '');
        $display_nomor_hp = htmlspecialchars($result['nomor_hp'] ?? '');
        $display_jenis = htmlspecialchars($result['jenis_hp'] ?? '');
        $display_keluhan = nl2br(htmlspecialchars($result['keluhan'] ?? ''));
        $display_tanggal_service = isset($result['tanggal_service']) ? date('d F Y', strtotime($result['tanggal_service'])) : '';
        $display_tanggal_expired = isset($result['tanggal_expired']) ? date('d F Y', strtotime($result['tanggal_expired'])) : '';
        $display_biaya = (isset($result['biaya_service']) && intval($result['biaya_service']) > 0) ? 'Rp ' . number_format(intval($result['biaya_service']), 0, ',', '.') : '';
    } else {
        $display_kode = $display_nomor_hp = $display_jenis = $display_keluhan = $display_tanggal_service = $display_tanggal_expired = $display_biaya = '';
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Garansi - Rudi Cell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Main Content - Full Width -->
            <main class="col-12 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-search"></i> Cek Status Garansi</h1>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mx-auto">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-shield-check"></i> Input Kode Garansi</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Kode Garansi</label>
                                        <input type="text" class="form-control form-control-lg" name="kode" 
                                               value="<?= htmlspecialchars($kode_garansi) ?>" 
                                               placeholder="RC-YYYYMMDD-XXXXX" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search"></i> Cek Garansi
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="row mt-4">
                        <div class="col-md-6 mx-auto">
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($kode_garansi) && $result): ?>
                    <?php
                    // SELALU hitung ulang status dari tanggal_expired untuk memastikan akurat
                    // Jangan bergantung pada status yang sudah dihitung sebelumnya karena mungkin hilang atau tidak konsisten
                    $logFile = __DIR__ . '/../logs/cek_garansi.log';
                    
                    // Gunakan original_tanggal_expired jika tersedia, jika tidak gunakan dari $result
                    $expiredRawDisplay = '';
                    if (!empty($original_tanggal_expired)) {
                        $expiredRawDisplay = $original_tanggal_expired;
                    } elseif (isset($result['tanggal_expired'])) {
                        $expiredRawDisplay = trim((string)$result['tanggal_expired']);
                    }
                    
                    // Debug: Log semua keys yang tersedia di $result
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Display section: result keys=" . implode(', ', array_keys($result ?? [])) . "\n", FILE_APPEND);
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Display section: original_tanggal_expired=" . var_export($original_tanggal_expired, true) . "\n", FILE_APPEND);
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Display section: tanggal_expired from result=" . var_export($result['tanggal_expired'] ?? 'NOT SET', true) . "\n", FILE_APPEND);
                    
                    $todayDisplay = new DateTime();
                    $todayDisplay->setTime(0, 0, 0);
                    
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Display section: expired_raw=" . ($expiredRawDisplay ?: 'EMPTY') . ", today=" . $todayDisplay->format('Y-m-d') . "\n", FILE_APPEND);
                    
                    // Initialize status
                    $status = 'unknown';
                    
                    // Validasi dan hitung status - gunakan logika yang sama dengan bagian atas
                    if (!empty($expiredRawDisplay) && 
                        $expiredRawDisplay !== '0000-00-00' && 
                        $expiredRawDisplay !== '1970-01-01') {
                        
                        try {
                            // Coba parse dengan format Y-m-d dulu
                            $expiredDateDisplay = DateTime::createFromFormat('Y-m-d', $expiredRawDisplay);
                            
                            // Jika createFromFormat gagal, coba dengan format default
                            if (!$expiredDateDisplay) {
                                $expiredDateDisplay = new DateTime($expiredRawDisplay);
                            }
                            
                            $expiredDateDisplay->setTime(0, 0, 0);
                            
                            // Bandingkan: aktif jika expired >= today
                            if ($expiredDateDisplay >= $todayDisplay) {
                                $status = 'aktif';
                            } else {
                                $status = 'expired';
                            }
                            
                            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Display section: status calculated = {$status} (expired={$expiredDateDisplay->format('Y-m-d')}, today={$todayDisplay->format('Y-m-d')})\n", FILE_APPEND);
                        } catch (Throwable $ex) {
                            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Display section: DateTime error - " . $ex->getMessage() . "\n", FILE_APPEND);
                            
                            // Fallback: coba dengan strtotime jika DateTime gagal
                            $timestamp = strtotime($expiredRawDisplay);
                            if ($timestamp !== false) {
                                $todayTimestamp = strtotime($todayDisplay->format('Y-m-d'));
                                $isActive = $timestamp >= $todayTimestamp;
                                $status = $isActive ? 'aktif' : 'expired';
                                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Display section: Fallback strtotime success, status = {$status}\n", FILE_APPEND);
                            } else {
                                $status = 'unknown';
                                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Display section: Fallback strtotime failed, status = unknown\n", FILE_APPEND);
                            }
                        }
                    } else {
                        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Display section: expiredRawDisplay is empty or invalid: " . var_export($expiredRawDisplay, true) . "\n", FILE_APPEND);
                    }
                    
                    // Set result status untuk konsistensi
                    $result['status'] = $status;
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Display section: Final status = {$status}\n", FILE_APPEND);
                    
                    // Determine display classes based on status
                    if ($status === 'aktif') {
                        $headerClass = 'success';  // Hijau
                        $borderClass = 'border-success';
                        $icon = 'check-circle-fill';
                        $statusLabel = 'AKTIF';
                        $statusBadgeClass = 'bg-success';
                    } elseif ($status === 'expired') {
                        $headerClass = 'danger';   // Merah
                        $borderClass = 'border-danger';
                        $icon = 'x-circle-fill';
                        $statusLabel = 'EXPIRED';
                        $statusBadgeClass = 'bg-danger';
                    } else {
                        $headerClass = 'warning';  // Kuning untuk unknown
                        $borderClass = 'border-warning';
                        $icon = 'question-circle-fill';
                        $statusLabel = 'TIDAK DIKETAHUI';
                        $statusBadgeClass = 'bg-warning';
                    }
                    
                    // Hitung sisa hari untuk tampilan
                    $sisaHari = 0;
                    if ($status === 'aktif' && !empty($result['tanggal_expired'])) {
                        try {
                            $todayCalc = new DateTime();
                            $todayCalc->setTime(0,0,0);
                            $expiredCalc = new DateTime($result['tanggal_expired']);
                            $expiredCalc->setTime(0,0,0);
                            $diff = $todayCalc->diff($expiredCalc);
                            $sisaHari = max(0, $diff->days);
                        } catch (Exception $e) {
                            $sisaHari = 0;
                        }
                    }
                    ?>
                    <div class="row mt-4">
                        <div class="col-md-8 mx-auto">
                            <div class="card <?= $borderClass ?> shadow-sm">
                                <div class="card-header bg-<?= $headerClass ?> text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="bi bi-<?= $icon ?>"></i>
                                            Status Garansi: <strong><?= $statusLabel ?></strong>
                                        </h5>
                                        <?php if ($status === 'aktif' && $sisaHari > 0): ?>
                                            <span class="badge bg-light text-dark fs-6">
                                                <i class="bi bi-calendar-check"></i> Sisa <?= $sisaHari ?> hari
                                            </span>
                                        <?php elseif ($status === 'expired'): ?>
                                            <span class="badge bg-light text-dark fs-6">
                                                <i class="bi bi-calendar-x"></i> Sudah Expired
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Kode Garansi:</strong><br>
                                            <span class="badge bg-dark"><?= $display_kode ?></span>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <?php if ($status === 'aktif'): ?>
                                                <span class="badge <?= $statusBadgeClass ?> fs-6 px-3 py-2">
                                                    <i class="bi bi-shield-check"></i> Garansi Aktif
                                                </span>
                                            <?php elseif ($status === 'expired'): ?>
                                                <span class="badge <?= $statusBadgeClass ?> fs-6 px-3 py-2">
                                                    <i class="bi bi-shield-x"></i> Garansi Expired
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                                    <i class="bi bi-question-circle"></i> Status Tidak Diketahui
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6><i class="bi bi-person"></i> Data Customer</h6>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%"><strong>Nomor HP</strong></td>
                                            <td><?= $display_nomor_hp ?></td>
                                        </tr>
                                    </table>
                                    
                                    <hr>
                                    
                                    <h6><i class="bi bi-phone"></i> Detail Service</h6>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%"><strong>Jenis HP</strong></td>
                                            <td><?= $display_jenis ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Keluhan</strong></td>
                                            <td><?= $display_keluhan ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Service</strong></td>
                                            <td><?= $display_tanggal_service ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Expired</strong></td>
                                            <td><?= $display_tanggal_expired ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Biaya Service</strong></td>
                                            <td id="biayaCell">
                                            <?php if (!empty($display_biaya)): ?>
                                                <?= $display_biaya ?>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak tersedia</span>
                                                <?php if (isset($_SESSION['user_id'])): ?>
                                                    <button class="btn btn-sm btn-outline-primary ms-2" id="editBiayaBtn">Tambahkan Biaya</button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <?php if (!empty($result['catatan'])): ?>
                                        <hr>
                                        <h6><i class="bi bi-journal-text"></i> Catatan</h6>
                                        <p><?= nl2br(htmlspecialchars($result['catatan'])) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($status === 'expired'): ?>
                                        <div class="alert alert-danger border-danger mt-3" role="alert">
                                            <i class="bi bi-exclamation-triangle-fill"></i> 
                                            <strong>Perhatian:</strong> Garansi service ini sudah tidak berlaku. 
                                            Silakan hubungi toko untuk service ulang.
                                        </div>
                                    <?php elseif ($status === 'aktif'): ?>
                                        <div class="alert alert-success border-success mt-3" role="alert">
                                            <i class="bi bi-check-circle-fill"></i> 
                                            <strong>Garansi Aktif:</strong> Garansi service ini masih berlaku. 
                                            Sisa masa garansi: <strong><?= $sisaHari ?> hari</strong>.
                                        </div>
                                    <?php endif; ?>

                                    <!-- Modal: Edit Biaya -->
                                    <div class="modal fade" id="editBiayaModal" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Tambahkan / Perbarui Biaya</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Biaya (Rp)</label>
                                                        <input type="number" id="biayaInput" class="form-control" min="0" placeholder="200000">
                                                        <div id="biayaError" class="text-danger mt-2" style="display:none;"></div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="button" class="btn btn-primary" id="saveBiayaBtn">Simpan</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            const editBtn = document.getElementById('editBiayaBtn');
            if (editBtn) {
                editBtn.addEventListener('click', () => {
                    const modal = new bootstrap.Modal(document.getElementById('editBiayaModal'));
                    modal.show();
                });
            }

            const saveBtn = document.getElementById('saveBiayaBtn');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => {
                    const input = document.getElementById('biayaInput');
                    const err = document.getElementById('biayaError');
                    err.style.display = 'none';
                    let val = input.value.trim();
                    if (!val || !/^[0-9]+$/.test(val)) {
                        err.textContent = 'Masukkan angka (mis. 200000)';
                        err.style.display = 'block';
                        return;
                    }

                    // Kirim ke endpoint
                    fetch('../actions/update_biaya.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        credentials: 'same-origin',
                        body: new URLSearchParams({
                            csrf_token: '<?= htmlspecialchars($csrf) ?>',
                            id: '<?= intval($result['id'] ?? 0) ?>',
                            biaya: val
                        })
                    })
                    .then(r => r.json())
                    .then(j => {
                        if (j.success) {
                            // update UI
                            const cell = document.getElementById('biayaCell');
                            cell.innerHTML = j.cost;
                            bootstrap.Modal.getInstance(document.getElementById('editBiayaModal')).hide();
                        } else {
                            err.textContent = j.message || 'Terjadi kesalahan';
                            err.style.display = 'block';
                        }
                    })
                    .catch(e => {
                        err.textContent = 'Terjadi kesalahan jaringan';
                        err.style.display = 'block';
                    });
                });
            }
        })();
    </script>
</body>
</html>
