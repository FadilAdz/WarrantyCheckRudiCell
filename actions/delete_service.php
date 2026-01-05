<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/garansi_aktif.php');
    exit();
}

// CSRF check
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    $msg = 'Token CSRF tidak valid.';
    if (!empty($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }

    $_SESSION['error'] = $msg;
    header('Location: ../pages/garansi_aktif.php');
    exit();
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    $msg = 'ID tidak valid.';
    if (!empty($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }

    $_SESSION['error'] = $msg;
    header('Location: ../pages/garansi_aktif.php');
    exit();
}

try {
    // Ambil record terlebih dahulu
    $stmtSel = $pdo->prepare('SELECT * FROM service_records WHERE id = ? LIMIT 1');
    $stmtSel->execute([$id]);
    $row = $stmtSel->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Masukkan ke trash (arsip)
        $insertSql = 'INSERT INTO trash (kode_garansi, nomor_hp_encrypted, jenis_hp, keluhan, tanggal_service, tanggal_expired, data_transaksi_encrypted) VALUES (?, ?, ?, ?, ?, ?, ?)';
        $ins = $pdo->prepare($insertSql);
        $ins->execute([
            $row['kode_garansi'],
            $row['nomor_hp_encrypted'],
            $row['jenis_hp'],
            $row['keluhan'],
            $row['tanggal_service'],
            $row['tanggal_expired'],
            $row['data_transaksi_encrypted']
        ]);

        // Hapus dari service_records
        $del = $pdo->prepare('DELETE FROM service_records WHERE id = ? LIMIT 1');
        $del->execute([$id]);

        if ($del->rowCount() > 0) {
            $msg = 'Garansi berhasil dipindahkan ke arsip (trash).';
            logUserActivity('DELETE_SERVICE', "User {$_SESSION['username']} memindahkan service id=$id ke trash");
            if (!empty($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $msg, 'id' => $id]);
                exit();
            }
            $_SESSION['success'] = $msg;
        } else {
            $msg = 'Gagal memindahkan data ke arsip.';
            if (!empty($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit();
            }
            $_SESSION['error'] = $msg;
        }
    } else {
        $msg = 'Data tidak ditemukan atau sudah dihapus.';
        if (!empty($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit();
        }
        $_SESSION['error'] = $msg;
    }
} catch (PDOException $e) {
    error_log('Delete service error: ' . $e->getMessage());
    $msg = 'Terjadi kesalahan saat menghapus data.';
    if (!empty($_POST['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
}

header('Location: ../pages/garansi_aktif.php');
exit();
