<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/encryption.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Service Baru - Rudi Cell</title>
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
                    <h1 class="h2"><i class="bi bi-plus-circle"></i> Input Service Baru</h1>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-clipboard-plus"></i> Form Input Service</h5>
                            </div>
                            <div class="card-body">
                                <form action="../actions/process_service.php" method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nomor HP (akan dienkripsi) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="nomor_hp" placeholder="08xxxxxxxxxx" required>
                                            <small class="text-muted">Data ini akan dienkripsi dengan AES-256</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Jenis HP <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="jenis_hp" placeholder="Contoh: iPhone 12 Pro" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tanggal Service <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="tanggal_service" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Keluhan / Kerusakan HP <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="keluhan" rows="3" placeholder="Deskripsikan kerusakan HP..." required></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Masa Garansi (hari) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="masa_garansi_hari" value="30" min="1" required>
                                            <small class="text-muted">Default: 30 hari</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Biaya Service (Rp)</label>
                                            <input type="number" class="form-control" name="biaya_service" placeholder="0" min="0">
                                            <small class="text-muted">Data ini akan dienkripsi dengan AES-256</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Catatan Tambahan</label>
                                        <textarea class="form-control" name="catatan_transaksi" rows="2" placeholder="Catatan internal (opsional)"></textarea>
                                        <small class="text-muted">Data ini akan dienkripsi dengan AES-256</small>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="d-flex justify-content-between">
                                        <button type="reset" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Simpan Data Service
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informasi</h6>
                            </div>
                            <div class="card-body">
                                <h6>Data yang Dienkripsi:</h6>
                                <ul class="small">
                                    <li><strong>Nomor HP</strong> - Melindungi privasi pelanggan</li>
                                    <li><strong>Biaya Service</strong> - Melindungi informasi finansial</li>
                                    <li><strong>Catatan Transaksi</strong> - Melindungi informasi internal</li>
                                </ul>
                                
                                <hr>
                                
                                <h6>Data yang Tidak Dienkripsi:</h6>
                                <ul class="small">
                                    <li>Jenis HP</li>
                                    <li>Tanggal Service</li>
                                    <li>Keluhan/Kerusakan HP</li>
                                    <li>Kode Garansi (untuk tracking)</li>
                                </ul>
                                
                                <div class="alert alert-info mt-3 small" role="alert">
                                    <i class="bi bi-shield-lock"></i> Sistem menggunakan enkripsi <strong>AES-256-CBC</strong> untuk melindungi data sensitif.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
