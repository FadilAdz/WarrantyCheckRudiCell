# Rudi Cell Warranty Management System

Sistem manajemen garansi untuk UMKM bengkel handphone yang fokus pada keamanan data dan pencegahan penipuan garansi.

## 🎯 Tujuan Aplikasi

Aplikasi ini dibuat khusus untuk UMKM bengkel handphone dengan fokus:
- **Manfaat UMKM**: Mudah digunakan, hemat biaya, aman
- **Konsep Kriptografi**: Enkripsi AES-256 untuk data sensitif
- **Pencegahan Penipuan**: Sistem yang akurat mencegah pelanggan berbohong tentang status garansi

## 🔐 Keamanan Data

### Data yang Dienkripsi (AES-256):
- ✅ Nomor HP Pelanggan
- ✅ Biaya Service
- ✅ Catatan Transaksi Internal

### Data yang Ditampilkan (Tidak Dienkripsi):
- 👁️ Kode Garansi (untuk tracking)
- 👁️ Jenis HP
- 👁️ Tanggal Service & Expired
- 👁️ Keluhan/Kerusakan

## 🚀 Fitur Utama

- **Input Service Baru**: Pencatatan service dengan enkripsi otomatis
- **Monitoring Garansi Aktif**: Pantau status garansi pelanggan
- **Cek Status Garansi**: Verifikasi garansi berdasarkan kode
- **Auto-Archive Expired**: Garansi expired otomatis dipindahkan ke trash
- **Dashboard Informatif**: Statistik dan quick actions

## 🛠️ Teknologi

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Security**: AES-256-CBC Encryption

## 📦 Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/your-repo/rudi-cell.git
   cd rudi-cell
   ```

2. **Setup Database**
   - Import `database_schema.sql` ke MySQL
   - Konfigurasi database di `config/database.php`

3. **Konfigurasi Enkripsi**
   - Edit `ENCRYPTION_KEY` di `config/encryption.php`
   - Gunakan key yang unik dan aman

4. **Login Default**
   - Username: `admin`
   - Password: `admin123`

## 🎨 Antarmuka

- **Dashboard**: Overview lengkap dengan statistik
- **Input Service**: Form input dengan validasi
- **Garansi Aktif**: List garansi yang masih berlaku
- **Trash**: Arsip garansi expired

## 🔒 Konsep Kriptografi

Sistem menggunakan **AES-256-CBC** untuk mengenkripsi data sensitif:
- **Key Management**: Key statis (bisa diubah ke key rotation)
- **IV Generation**: Random IV per enkripsi
- **Storage**: Data + IV di-encode base64

## 📈 Manfaat untuk UMKM

1. **Efisiensi**: Digitalisasi pencatatan garansi
2. **Keamanan**: Data pelanggan terlindungi
3. **Akurasi**: Sistem akurat mencegah dispute
4. **Skalabilitas**: Mudah dikembangkan
5. **Biaya Rendah**: Open source, mudah dihosting

## 🤝 Kontribusi

Selamat datang kontribusi untuk pengembangan sistem ini!

## 📄 Lisensi

MIT License - bebas digunakan untuk UMKM Indonesia.

---

**Dibuat untuk UMKM Indonesia** 🇮🇩
**Fokus: Keamanan Data & Pencegahan Penipuan Garansi**</content>
<parameter name="filePath">c:\xampp\htdocs\rudiCell\README.md