<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = 'ID tidak valid.';
    header('Location: garansi_aktif.php');
    exit();
}

// Ambil record
try {
    $stmt = $pdo->prepare('SELECT * FROM service_records WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        $_SESSION['error'] = 'Data tidak ditemukan.';
        header('Location: garansi_aktif.php');
        exit();
    }
} catch (PDOException $e) {
    error_log('Fetch service error: ' . $e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan.';
    header('Location: garansi_aktif.php');
    exit();
}

$success = $error = '';
$csrf = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $jenis_hp = trim($_POST['jenis_hp'] ?? '');
        $nama_customer = trim($_POST['nama_customer'] ?? '');
        $keluhan = trim($_POST['keluhan'] ?? '');
        $tanggal_service = trim($_POST['tanggal_service'] ?? '');
        $masa_garansi = intval($_POST['masa_garansi'] ?? 0);

        if (empty($jenis_hp) || empty($tanggal_service) || $masa_garansi <= 0) {
            $error = 'Mohon isi Jenis HP, Tanggal Service, dan Masa Garansi (hari).' ;
        } else {
            // Hitung tanggal_expired
            $dt = DateTime::createFromFormat('Y-m-d', $tanggal_service);
            if (!$dt) {
                $error = 'Format tanggal tidak valid. Gunakan YYYY-MM-DD.';
            } else {
                $dt->modify('+' . $masa_garansi . ' days');
                $tanggal_expired = $dt->format('Y-m-d');

                try {
                    $stmt = $pdo->prepare('UPDATE service_records SET jenis_hp = ?, keluhan = ?, tanggal_service = ?, masa_garansi_hari = ?, tanggal_expired = ?, nama_customer = ? WHERE id = ?');
                    $stmt->execute([$jenis_hp, $keluhan, $tanggal_service, $masa_garansi, $tanggal_expired, $nama_customer, $id]);

                    $success = 'Data berhasil diperbarui.';
                    logUserActivity('EDIT_SERVICE', "User {$_SESSION['username']} mengedit service id=$id");

                    // Redirect balik dengan pesan
                    $_SESSION['success'] = $success;
                    header('Location: garansi_aktif.php');
                    exit();
                } catch (PDOException $e) {
                    error_log('Update service error: ' . $e->getMessage());
                    $error = 'Terjadi kesalahan saat menyimpan.';
                }
            }
        }
    }
}

// Pre-fill values (prefer POST values if validation failed)
$jenis_hp_val = htmlspecialchars($_POST['jenis_hp'] ?? $record['jenis_hp']);
$nama_customer_val = htmlspecialchars($_POST['nama_customer'] ?? ($record['nama_customer'] ?? ''));
$keluhan_val = htmlspecialchars($_POST['keluhan'] ?? $record['keluhan']);
$tanggal_service_val = htmlspecialchars($_POST['tanggal_service'] ?? $record['tanggal_service']);
$masa_garansi_val = htmlspecialchars($_POST['masa_garansi'] ?? ($record['masa_garansi_hari'] ?? '30'));
$kode_garansi_val = htmlspecialchars($record['kode_garansi']);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service - Rudi Cell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Service</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

                            <div class="mb-3">
                                <label class="form-label">Kode Garansi</label>
                                <input type="text" class="form-control" value="<?= $kode_garansi_val ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nama Customer (opsional)</label>
                                <input type="text" name="nama_customer" class="form-control" value="<?= $nama_customer_val ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Jenis HP</label>
                                <input type="text" name="jenis_hp" class="form-control" value="<?= $jenis_hp_val ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Keluhan</label>
                                <textarea name="keluhan" class="form-control" rows="3"><?= $keluhan_val ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggal Service</label>
                                    <input type="date" name="tanggal_service" class="form-control" value="<?= $tanggal_service_val ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Masa Garansi (hari)</label>
                                    <input type="number" name="masa_garansi" class="form-control" value="<?= $masa_garansi_val ?>" min="1" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="garansi_aktif.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>