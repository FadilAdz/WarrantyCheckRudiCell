<?php
/**
 * Script untuk auto-move garansi expired ke trash
 * 
 * Cara menjalankan:
 * 1. Manual: Akses langsung via browser atau jalankan via PHP CLI
 * 2. Otomatis: Setup Cron Job (Linux) atau Task Scheduler (Windows)
 * 
 * Contoh Cron Job (jalankan setiap hari jam 00:00):
 * 0 0 * * * php /path/to/actions/move_to_trash.php
 */

require_once '../config/database.php';

try {
    $conn = getConnection();
    
    // Log file untuk tracking
    $logFile = __DIR__ . '/../logs/move_to_trash.log';
    
    // Cari semua garansi yang sudah expired tapi masih di service_records
    $sql = "SELECT * FROM service_records WHERE tanggal_expired < CURDATE()";
    $expiredRecords = fetchData($conn, $sql);
    
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Found " . count($expiredRecords) . " expired records to move\n", FILE_APPEND);
    
    $movedCount = 0;
    $errorCount = 0;
    
    foreach ($expiredRecords as $record) {
        try {
            // Cek apakah data sudah ada di trash (untuk menghindari duplicate)
            $checkSql = "SELECT id FROM trash WHERE kode_garansi = ? LIMIT 1";
            $checkResult = fetchData($conn, $checkSql, [$record['kode_garansi']], 's');
            
            if (!empty($checkResult)) {
                // Data sudah ada di trash, langsung hapus dari service_records saja
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Record {$record['kode_garansi']} already in trash, deleting from service_records only\n", FILE_APPEND);
                $sqlDelete = "DELETE FROM service_records WHERE id = ?";
                $deleteStmt = executeQuery($conn, $sqlDelete, [$record['id']], 'i');
                if ($deleteStmt) {
                    $movedCount++;
                }
                continue;
            }
            
            // Insert ke trash
            $sqlInsert = "INSERT INTO trash 
                          (kode_garansi, nomor_hp_encrypted, jenis_hp, keluhan, 
                           tanggal_service, tanggal_expired, data_transaksi_encrypted) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = executeQuery(
                $conn,
                $sqlInsert,
                [
                    $record['kode_garansi'],
                    $record['nomor_hp_encrypted'],
                    $record['jenis_hp'],
                    $record['keluhan'],
                    $record['tanggal_service'],
                    $record['tanggal_expired'],
                    $record['data_transaksi_encrypted']
                ],
                'sssssss'
            );
            
            if ($stmt) {
                // Delete dari service_records
                $sqlDelete = "DELETE FROM service_records WHERE id = ?";
                $deleteStmt = executeQuery($conn, $sqlDelete, [$record['id']], 'i');
                
                if ($deleteStmt) {
                    $movedCount++;
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Successfully moved {$record['kode_garansi']} to trash\n", FILE_APPEND);
                } else {
                    $errorCount++;
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Failed to delete {$record['kode_garansi']} from service_records after insert to trash\n", FILE_APPEND);
                }
            } else {
                $errorCount++;
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: Failed to insert {$record['kode_garansi']} to trash\n", FILE_APPEND);
            }
        } catch (Exception $ex) {
            $errorCount++;
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR processing {$record['kode_garansi']}: " . $ex->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Completed: $movedCount moved, $errorCount errors\n", FILE_APPEND);
    
    $conn->close();
    
    // Output untuk log (untuk CLI)
    if (php_sapi_name() === 'cli') {
        echo date('Y-m-d H:i:s') . " - Berhasil memindahkan $movedCount garansi expired ke trash\n";
        return;
    }

    // Jika diakses langsung melalui browser (file ini dieksekusi sendiri), redirect ke halaman trash
    $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($scriptName === 'move_to_trash.php') {
        header('Location: ../pages/trash.php?success=' . urlencode("$movedCount garansi expired dipindahkan ke trash"));
        exit;
    }

    // Jika file ini di-include oleh halaman lain (mis. dashboard), simpan pesan ke session sebagai flash message
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!empty($movedCount)) {
        $_SESSION['move_trash_msg'] = "$movedCount garansi expired dipindahkan ke trash";
    }
    // Jika tidak ada yang dipindahkan, bisa juga menyimpan informasi 0, tapi di sini kita simpan hanya jika >0
    
} catch (Exception $e) {
    error_log("Error move to trash: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    
    if (php_sapi_name() !== 'cli') {
        header('Location: ../pages/trash.php?error=' . urlencode('Terjadi kesalahan'));
        exit;
    }
}
?>
