<?php
/**
 * Script untuk memperbaiki database - Menambahkan kolom biaya_int
 * Jalankan: php fix_database.php
 */

require_once 'config/database.php';

echo "=== Memeriksa dan Memperbaiki Database ===\n\n";

try {
    $conn = getConnection();
    
    // Cek apakah kolom biaya_int sudah ada
    $check = $conn->query("SHOW COLUMNS FROM service_records LIKE 'biaya_int'");
    
    if ($check && $check->num_rows > 0) {
        echo "✓ Kolom 'biaya_int' sudah ada di tabel service_records\n";
    } else {
        echo "✗ Kolom 'biaya_int' belum ada. Menambahkan kolom...\n";
        
        $alter = $conn->query("ALTER TABLE service_records ADD COLUMN biaya_int INT NOT NULL DEFAULT 0 AFTER data_transaksi_encrypted");
        
        if ($alter) {
            echo "✓ Kolom 'biaya_int' berhasil ditambahkan!\n";
        } else {
            echo "✗ Error: " . $conn->error . "\n";
            exit(1);
        }
    }
    
    // Tampilkan struktur kolom untuk verifikasi
    echo "\n=== Struktur Tabel service_records ===\n";
    $result = $conn->query("SHOW COLUMNS FROM service_records");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    }
    
    $conn->close();
    echo "\n✓ Database sudah diperbaiki! Silakan coba input service baru lagi.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

