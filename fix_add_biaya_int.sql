-- Fix: Tambahkan kolom biaya_int ke tabel service_records
-- Script ini untuk memperbaiki database yang sudah ada

USE rudi_cell_warranty;

-- Cek apakah kolom sudah ada, jika belum tambahkan
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'rudi_cell_warranty' 
    AND TABLE_NAME = 'service_records' 
    AND COLUMN_NAME = 'biaya_int'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE service_records ADD COLUMN biaya_int INT NOT NULL DEFAULT 0 AFTER data_transaksi_encrypted',
    'SELECT "Kolom biaya_int sudah ada, tidak perlu ditambahkan" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atau jika menggunakan cara sederhana (uncomment baris di bawah jika cara di atas tidak berfungsi):
-- ALTER TABLE service_records ADD COLUMN biaya_int INT NOT NULL DEFAULT 0;

SELECT 'Kolom biaya_int berhasil ditambahkan ke tabel service_records!' AS status;

