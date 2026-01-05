<?php
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query('SELECT id, kode_garansi FROM service_records ORDER BY id ASC LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo $row['id'] . '|' . $row['kode_garansi'] . "\n";
    } else {
        echo "NO_SERVICE\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
