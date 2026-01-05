-- Buat Database
CREATE DATABASE rudi_cell_warranty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rudi_cell_warranty;

-- Tabel Admin/User
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (password: admin123)
INSERT INTO users (username, password, nama_lengkap) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

-- Tabel Service Records (Garansi Aktif)
CREATE TABLE service_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_garansi VARCHAR(50) NOT NULL UNIQUE,
    nomor_hp_encrypted TEXT NOT NULL,
    jenis_hp VARCHAR(100) NOT NULL,
    keluhan TEXT NOT NULL,
    tanggal_service DATE NOT NULL,
    masa_garansi_hari INT NOT NULL DEFAULT 30,
    tanggal_expired DATE NOT NULL,
    data_transaksi_encrypted TEXT,
    biaya_int INT NOT NULL DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Tabel Trash (Garansi Expired)
CREATE TABLE trash (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_garansi VARCHAR(50) NOT NULL,
    nomor_hp_encrypted TEXT NOT NULL,
    jenis_hp VARCHAR(100) NOT NULL,
    keluhan TEXT NOT NULL,
    tanggal_service DATE NOT NULL,
    tanggal_expired DATE NOT NULL,
    data_transaksi_encrypted TEXT,
    moved_to_trash_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index untuk performa
CREATE INDEX idx_tanggal_expired ON service_records(tanggal_expired);
CREATE INDEX idx_jenis_hp ON service_records(jenis_hp);
CREATE INDEX idx_kode_garansi ON service_records(kode_garansi);
