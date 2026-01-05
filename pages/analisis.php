<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

$conn = getConnection();

// Analisis kerusakan dari semua data (aktif + trash)
$sql = "SELECT keluhan, COUNT(*) as jumlah,
        GROUP_CONCAT(DISTINCT jenis_hp SEPARATOR ', ') as jenis_hp_list
        FROM (
            SELECT keluhan, jenis_hp FROM service_records
            UNION ALL
            SELECT keluhan, jenis_hp FROM trash
        ) as all_services
        GROUP BY keluhan
        ORDER BY jumlah DESC
        LIMIT 20";
$dataAnalisis = fetchData($conn, $sql);

// Statistik tambahan
$sqlTotal = "SELECT 
             (SELECT COUNT(*) FROM service_records) + (SELECT COUNT(*) FROM trash) as total_service,
             COUNT(DISTINCT keluhan) as unique_keluhan
             FROM (
                 SELECT keluhan FROM service_records
                 UNION ALL
                 SELECT keluhan FROM trash
             ) as all_kerusakan";
$stats = fetchData($conn, $sqlTotal);
$totalService = $stats[0]['total_service'] ?? 0;
$uniqueKeluhan = $stats[0]['unique_keluhan'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Kerusakan HP - Rudi Cell</title>
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
                    <h1 class="h2"><i class="bi bi-graph-up"></i> Analisis Kerusakan HP</h1>
                </div>
                
                <!-- Statistik Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Service (All Time)</h6>
                                        <h2 class="mb-0"><?= $totalService ?></h2>
                                    </div>
                                    <i class="bi bi-tools" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Jenis Kerusakan Unik</h6>
                                        <h2 class="mb-0"><?= $uniqueKeluhan ?></h2>
                                    </div>
                                    <i class="bi bi-clipboard-data" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabel Analisis -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Top 20 Kerusakan Paling Sering</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($dataAnalisis)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">Belum ada data untuk dianalisis</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="5%">Rank</th>
                                            <th width="50%">Jenis Kerusakan / Keluhan</th>
                                            <th width="15%">Frekuensi</th>
                                            <th width="15%">Persentase</th>
                                            <th width="15%">Visual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dataAnalisis as $index => $data): ?>
                                            <?php
                                            $persentase = ($data['jumlah'] / $totalService) * 100;
                                            
                                            // Tentukan warna badge berdasarkan frekuensi
                                            if ($index == 0) {
                                                $badgeClass = 'bg-danger';
                                                $icon = '🥇';
                                            } elseif ($index == 1) {
                                                $badgeClass = 'bg-warning';
                                                $icon = '🥈';
                                            } elseif ($index == 2) {
                                                $badgeClass = 'bg-success';
                                                $icon = '🥉';
                                            } else {
                                                $badgeClass = 'bg-secondary';
                                                $icon = '#' . ($index + 1);
                                            }
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?= $badgeClass ?> fs-6">
                                                        <?= $icon ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($data['keluhan']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-phone"></i> 
                                                        <?= htmlspecialchars(substr($data['jenis_hp_list'], 0, 50)) ?>
                                                        <?= strlen($data['jenis_hp_list']) > 50 ? '...' : '' ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?= $data['jumlah'] ?>x</span>
                                                </td>
                                                <td>
                                                    <strong><?= number_format($persentase, 1) ?>%</strong>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar <?= $badgeClass ?>" 
                                                             role="progressbar" 
                                                             style="width: <?= $persentase ?>%">
                                                        </div>
                                                    </div>
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
                    <strong>Tips:</strong> Gunakan data analisis ini untuk:
                    <ul class="mb-0 mt-2">
                        <li>Menentukan spare part yang harus selalu ready stock</li>
                        <li>Identifikasi kerusakan HP yang paling sering terjadi</li>
                        <li>Persiapan skill teknisi untuk jenis kerusakan tertentu</li>
                        <li>Strategi preventif maintenance untuk customer</li>
                    </ul>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
