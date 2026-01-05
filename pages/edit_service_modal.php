<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo '<div class="alert alert-danger">ID tidak valid.</div>';
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT * FROM service_records WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        echo '<div class="alert alert-danger">Data tidak ditemukan.</div>';
        exit();
    }
} catch (PDOException $e) {
    error_log('Fetch service modal error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">Terjadi kesalahan.</div>';
    exit();
}

$csrf = generateCSRFToken();
?>
<form id="editServiceFormAjax">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars($record['id']) ?>">

    <div class="mb-3">
        <label class="form-label">Kode Garansi</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($record['kode_garansi']) ?>" readonly>
    </div>

    <div class="mb-3">
        <label class="form-label">Nama Customer (opsional)</label>
        <input type="text" name="nama_customer" class="form-control" value="<?= htmlspecialchars($record['nama_customer'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Jenis HP</label>
        <input type="text" name="jenis_hp" class="form-control" value="<?= htmlspecialchars($record['jenis_hp']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Keluhan</label>
        <textarea name="keluhan" class="form-control" rows="3"><?= htmlspecialchars($record['keluhan']) ?></textarea>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Tanggal Service</label>
            <input type="date" name="tanggal_service" class="form-control" value="<?= htmlspecialchars($record['tanggal_service']) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Masa Garansi (hari)</label>
            <input type="number" name="masa_garansi" class="form-control" value="<?= htmlspecialchars($record['masa_garansi_hari']) ?>" min="1" required>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </div>
</form>