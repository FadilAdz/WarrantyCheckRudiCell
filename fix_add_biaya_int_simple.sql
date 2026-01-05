-- Fix: Tambahkan kolom biaya_int ke tabel service_records
-- Script sederhana untuk memperbaiki database yang sudah ada

USE rudi_cell_warranty;

-- Tambahkan kolom biaya_int jika belum ada
ALTER TABLE service_records ADD COLUMN biaya_int INT NOT NULL DEFAULT 0 AFTER data_transaksi_encrypted;

