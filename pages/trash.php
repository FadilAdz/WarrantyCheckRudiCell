<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

$conn = getConnection();

// Get all trash data
$sql = "SELECT * FROM trash ORDER BY moved_to_trash_at DESC";
$trashData = fetchData($conn, $sql);

$conn->close();

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trash - Rudi Cell</title>
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
                    <h1 class="h2"><i class="bi bi-trash"></i> Trash (Garansi Expired)</h1>
                    <div>
                        <span class="badge bg-danger fs-6"><?= count($trashData) ?> Data</span>
                    </div>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">Daftar Garansi yang Sudah Expired</h5>
                            </div>
                            <div class="col-auto">
                                <?php if (!empty($trashData)): ?>
                                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                                        <i class="bi bi-trash3"></i> Hapus Semua Trash
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($trashData)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">Trash kosong</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode Garansi</th>
                                            <th>Jenis HP</th>
                                            <th>Tanggal Service</th>
                                            <th>Expired Sejak</th>
                                            <th>Pindah ke Trash</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trashData as $index => $data): ?>
                                            <?php
                                            $today = new DateTime();
                                            $expired = new DateTime($data['tanggal_expired']);
                                            $lamExpired = $today->diff($expired)->days;
                                            ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><small><?= htmlspecialchars($data['kode_garansi']) ?></small></td>
                                                <td><?= htmlspecialchars($data['jenis_hp']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($data['tanggal_service'])) ?></td>
                                                <td>
                                                    <span class="text-danger">
                                                        <?= date('d/m/Y', strtotime($data['tanggal_expired'])) ?>
                                                    </span>
                                                    <br>
                                                    <small class="text-muted">(<?= $lamExpired ?> hari lalu)</small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($data['moved_to_trash_at'])) ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3" role="alert">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Info:</strong> Data di trash adalah garansi yang sudah expired dan dipindahkan otomatis oleh sistem.
                    Data ini tidak bisa di-restore dan hanya bisa dihapus secara permanen.
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal Konfirmasi Hapus Semua -->
    <div class="modal fade" id="deleteAllModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Apakah Anda yakin ingin menghapus SEMUA data di trash?</strong></p>
                    <p class="text-danger">
                        <i class="bi bi-exclamation-circle"></i> 
                        Aksi ini tidak dapat dibatalkan! Semua data (<?= count($trashData) ?> record) akan dihapus permanen.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form action="../actions/delete_trash.php" method="POST" style="display: inline;">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash3"></i> Ya, Hapus Semua
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
