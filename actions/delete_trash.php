<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../pages/trash.php');
    exit;
}

try {
    $conn = getConnection();
    
    // Hitung jumlah data sebelum dihapus
    $sql = "SELECT COUNT(*) as total FROM trash";
    $result = fetchData($conn, $sql);
    $totalDeleted = $result[0]['total'] ?? 0;
    
    // Delete all trash
    $sql = "DELETE FROM trash";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $conn->close();
    
    header('Location: ../pages/trash.php?success=' . urlencode("Berhasil menghapus $totalDeleted data dari trash"));
    exit;
    
} catch (Exception $e) {
    error_log("Error delete trash: " . $e->getMessage());
    header('Location: ../pages/trash.php?error=' . urlencode('Gagal menghapus data trash'));
    exit;
}
?>
