<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

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

// Service terbaru (5 terakhir)
$sql = "SELECT id, kode_garansi, nama_customer, jenis_hp, tanggal_service, tanggal_expired 
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
                    <h1 class="h2"><i class="bi bi-speedometer2"></i> Dashboard</h1>
                    <div class="text-muted">
                        <i class="bi bi-calendar"></i> <?= date('d F Y') ?>
                    </div>
                </div>
                
                <!-- Statistik Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Service Bulan Ini</h6>
                                        <h2 class="mb-0"><?= $totalServiceBulanIni ?></h2>
                                    </div>
                                    <i class="bi bi-calendar-check" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Garansi Aktif</h6>
                                        <h2 class="mb-0"><?= $totalGaransiAktif ?></h2>
                                    </div>
                                    <i class="bi bi-shield-check" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Garansi Expired</h6>
                                        <h2 class="mb-0"><?= $totalGaransiExpired ?></h2>
                                    </div>
                                    <i class="bi bi-shield-x" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Jenis HP Terbanyak -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Jenis HP Paling Sering Diservice</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($topHP)): ?>
                                    <p class="text-muted text-center py-4">Belum ada data</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Jenis HP</th>
                                                    <th>Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($topHP as $index => $hp): ?>
                                                    <tr>
                                                        <td><?= $index + 1 ?></td>
                                                        <td><strong><?= htmlspecialchars($hp['jenis_hp']) ?></strong></td>
                                                        <td>
                                                            <span class="badge bg-primary"><?= $hp['jumlah'] ?>x</span>
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
                    
                    <!-- Service Terbaru -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Service Terbaru</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentServices)): ?>
                                    <p class="text-muted text-center py-4">Belum ada data</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Kode</th>
                                                    <th>Customer</th>
                                                    <th>Jenis HP</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentServices as $service): ?>
                                                    <?php
                                                    $isActive = strtotime($service['tanggal_expired']) >= strtotime(date('Y-m-d'));
                                                    ?>
                                                    <tr>
                                                        <td><small><?= htmlspecialchars($service['kode_garansi']) ?></small></td>
                                                        <td><?= htmlspecialchars($service['nama_customer']) ?></td>
                                                        <td><?= htmlspecialchars($service['jenis_hp']) ?></td>
                                                        <td>
                                                            <?php if ($isActive): ?>
                                                                <span class="badge bg-success">Aktif</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Expired</span>
                                                            <?php endif; ?>
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
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
