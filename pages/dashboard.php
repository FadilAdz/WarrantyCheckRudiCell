<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Jalankan auto-move expired garansi ke trash setiap kali dashboard diakses
require_once '../actions/move_to_trash.php';

$conn = getConnection();

// Statistik
$totalServiceBulanIni = 0;
$totalGaransiAktif = 0;
$totalGaransiExpired = 0;

// Total service bulan ini
$bulanIni = date('Y-m');
$sql = "SELECT COUNT(*) as total FROM service_records WHERE DATE_FORMAT(tanggal_service, '%Y-%m') = ?";
$result = fetchData($conn, $sql, [$bulanIni], 's');
$totalServiceBulanIni = $result[0]['total'] ?? 0;

// Total garansi aktif
$sql = "SELECT COUNT(*) as total FROM service_records WHERE tanggal_expired >= CURDATE()";
$result = fetchData($conn, $sql);
$totalGaransiAktif = $result[0]['total'] ?? 0;

// Total garansi expired (di trash)
$sql = "SELECT COUNT(*) as total FROM trash";
$result = fetchData($conn, $sql);
$totalGaransiExpired = $result[0]['total'] ?? 0;

// Jenis HP paling sering diservice (Top 5)
$sql = "SELECT jenis_hp, COUNT(*) as jumlah 
        FROM (
            SELECT jenis_hp FROM service_records
            UNION ALL
            SELECT jenis_hp FROM trash
        ) as all_hp
        GROUP BY jenis_hp
        ORDER BY jumlah DESC
        LIMIT 5";
$topHP = fetchData($conn, $sql);

// Service terbaru (5 terakhir) - sertakan `id` untuk menghindari undefined index
$sql = "SELECT id, kode_garansi, jenis_hp, tanggal_service, tanggal_expired 
        FROM service_records 
        ORDER BY created_at DESC 
        LIMIT 5";
$recentServices = fetchData($conn, $sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Rudi Cell</title>
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
                    <div>
                        <h1 class="h2 mb-0"><i class="bi bi-speedometer2 text-primary"></i> Dashboard Rudi Cell</h1>
                        <p class="text-muted mb-0">Pantau bisnis bengkel HP Anda dengan mudah</p>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">Hari ini</div>
                        <div class="fw-bold fs-5"><?php echo date('d F Y'); ?></div>
                    </div>
                </div>
                
                <!-- Welcome Message -->
                <div class="alert alert-info" role="alert">
                    <h5 class="alert-heading"><i class="bi bi-info-circle"></i> Selamat Datang di Sistem Manajemen Garansi</h5>
                    <p class="mb-0">Pantau status garansi pelanggan dengan mudah dan aman. Data sensitif terlindungi dengan enkripsi AES-256.</p>
                </div>

                <?php if (!empty($_SESSION['move_trash_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['move_trash_msg']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['move_trash_msg']); ?>
                <?php endif; ?>
                
                <!-- Statistik Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Service Bulan Ini</h6>
                                        <h2 class="mb-0"><?= $totalServiceBulanIni ?></h2>
                                        <small>Total unit servis</small>
                                    </div>
                                    <i class="bi bi-calendar-check" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Garansi Aktif</h6>
                                        <h2 class="mb-0"><?= $totalGaransiAktif ?></h2>
                                        <small>Pelanggan aktif</small>
                                    </div>
                                    <i class="bi bi-shield-check" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Garansi Expired</h6>
                                        <h2 class="mb-0"><?= $totalGaransiExpired ?></h2>
                                        <small>Diarsipkan</small>
                                    </div>
                                    <i class="bi bi-archive" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Keamanan Data</h6>
                                        <h2 class="mb-0">AES-256</h2>
                                        <small>Enkripsi aktif</small>
                                    </div>
                                    <i class="bi bi-lock" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Service Terbaru & Jenis HP Populer -->
                <div class="row">
                    <!-- Service Terbaru -->
                    <div class="col-md-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Service Terbaru</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentServices)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-3">Belum ada data service</p>
                                        <a href="input_service.php" class="btn btn-primary">Input Service Pertama</a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Kode Garansi</th>
                                                    <th>Jenis HP</th>
                                                    <th>Tanggal Service</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentServices as $service): ?>
                                                    <?php
                                                    $today = new DateTime();
                                                    $expired_date = new DateTime($service['tanggal_expired']);
                                                    $isActive = $expired_date >= $today;
                                                    ?>
                                                    <tr>
                                                        <td><small class="font-monospace"><?php echo htmlspecialchars($service['kode_garansi']); ?></small></td>
                                                        <td><?php echo htmlspecialchars($service['jenis_hp']); ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($service['tanggal_service'])); ?></td>
                                                        <td>
                                                            <?php if ($isActive): ?>
                                                                <span class="badge bg-success">Aktif</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Expired</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="detail_service.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-eye"></i> Detail
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Panel Kanan -->
                    <div class="col-md-4">
                        <!-- Jenis HP Populer -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="bi bi-bar-chart"></i> HP Paling Banyak Diservice</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($topHP)): ?>
                                    <p class="text-muted small text-center">Belum ada data</p>
                                <?php else: ?>
                                    <?php foreach (array_slice($topHP, 0, 5) as $index => $hp): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small"><?php echo htmlspecialchars($hp['jenis_hp']); ?></span>
                                            <span class="badge bg-primary"><?php echo $hp['jumlah']; ?>x</span>
                                        </div>
                                        <?php if ($index < 4): ?><hr class="my-2"><?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tips untuk UMKM -->
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Tips UMKM</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info small mb-0">
                                    <strong>💡 Tip:</strong> Selalu catat nomor HP pelanggan dengan lengkap untuk menghindari penipuan garansi.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Keamanan Data Info -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Keamanan Data</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Data yang Diamankan (Enkripsi AES-256):</h6>
                                        <ul>
                                            <li><i class="bi bi-check-circle text-success"></i> Nomor HP Pelanggan</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Catatan Transaksi</li>
                                            <li><i class="bi bi-check-circle text-success"></i> Password Admin</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Data yang Ditampilkan (Tidak Dienkripsi):</h6>
                                        <ul>
                                            <li><i class="bi bi-eye"></i> Kode Garansi (untuk tracking)</li>
                                            <li><i class="bi bi-eye"></i> Jenis HP</li>
                                            <li><i class="bi bi-eye"></i> Tanggal Service & Expired</li>
                                            <li><i class="bi bi-eye"></i> Keluhan/Kerusakan</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="alert alert-success mt-3">
                                    <i class="bi bi-check-circle"></i> <strong>Sistem ini mencegah pelanggan berbohong tentang status garansi!</strong>
                                    <br>Data garansi tersimpan dengan aman dan akurat, sehingga Anda dapat memverifikasi klaim pelanggan kapan saja.
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
