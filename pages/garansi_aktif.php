<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/encryption.php';

// Jalankan auto-move expired garansi ke trash sebelum menampilkan data
require_once '../actions/move_to_trash.php';

$conn = getConnection();

// Get all active warranties
$sql = "SELECT * FROM service_records WHERE tanggal_expired >= CURDATE() ORDER BY tanggal_expired ASC";
$garansiAktif = fetchData($conn, $sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garansi Aktif - Rudi Cell</title>
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
                    <h1 class="h2"><i class="bi bi-shield-check"></i> Garansi Aktif</h1>
                    <div>
                        <span class="badge bg-success fs-6"><?= count($garansiAktif) ?> Garansi Aktif</span>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">Daftar Garansi yang Masih Aktif</h5>
                            </div>
                            <div class="col-auto">
                                <input type="text" class="form-control" id="searchInput" placeholder="Cari...">
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-end p-2">
                            <span id="activeCountBadge" class="badge bg-success fs-6 me-2"><?= count($garansiAktif) ?> Garansi Aktif</span>
                        </div>
                    </div>
                    <div class="card-body">

                        <?php // Tampilkan pesan sukses / error jika ada (dari redirect POST)
                        if (!empty($_SESSION['success'])): ?>
                            <div class="alert alert-success" role="alert">
                                <?= htmlspecialchars($_SESSION['success']); ?>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (!empty($_SESSION['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($_SESSION['error']); ?>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <?php if (empty($garansiAktif)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">Belum ada garansi aktif</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="garansiTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode Garansi</th>
                                            <th>Jenis HP</th>
                                            <th>Tanggal Service</th>
                                            <th>Expired</th>
                                            <th>Sisa Hari</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($garansiAktif as $index => $data): ?>
                                            <?php
                                            $today = new DateTime();
                                            $expired = new DateTime($data['tanggal_expired']);
                                            $sisa = $today->diff($expired)->days;
                                            if ($expired < $today) { $sisa = 0; }
                                            
                                            // Tentukan warna badge berdasarkan sisa hari
                                            if ($sisa <= 3) {
                                                $badgeClass = 'bg-danger';
                                            } elseif ($sisa <= 7) {
                                                $badgeClass = 'bg-warning';
                                            } else {
                                                $badgeClass = 'bg-success';
                                            }
                                            ?>
                                            <tr id="row-<?= $data['id'] ?>">
                                                <td class="col-no"><?= $index + 1 ?></td>
                                                <td class="col-kode"><small><?= htmlspecialchars($data['kode_garansi']) ?></small></td>
                                                <td class="col-jenis"><?= htmlspecialchars($data['jenis_hp']) ?></td>
                                                <td class="col-tanggal"><?= date('d/m/Y', strtotime($data['tanggal_service'])) ?></td>
                                                <td class="col-expired"><?= date('d/m/Y', strtotime($data['tanggal_expired'])) ?></td>
                                                <td class="col-sisa">
                                                    <span class="badge <?= $badgeClass ?>">
                                                        <?= $sisa ?> hari
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="detail_service.php?id=<?= $data['id'] ?>" 
                                                       class="btn btn-sm btn-info text-white">
                                                        <i class="bi bi-eye"></i> Detail
                                                    </a>

                                                    <button type="button" class="btn btn-sm btn-primary ms-1 editBtn" data-id="<?= $data['id'] ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>

                                                    <button type="button" class="btn btn-sm btn-danger ms-1 deleteBtn" data-id="<?= $data['id'] ?>" data-csrf="<?= htmlspecialchars(generateCSRFToken()) ?>">
                                                        <i class="bi bi-trash"></i> Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Simple search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('garansiTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();

                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // Open edit modal and load form via AJAX
        document.addEventListener('click', function(e) {
            if (e.target.closest('.editBtn')) {
                const btn = e.target.closest('.editBtn');
                const id = btn.getAttribute('data-id');
                openEditModal(id);
            }

            if (e.target.closest('.deleteBtn')) {
                const btn = e.target.closest('.deleteBtn');
                const id = btn.getAttribute('data-id');
                const csrf = btn.getAttribute('data-csrf');
                handleDelete(id, csrf);
            }
        });

        function openEditModal(id) {
            // Fetch modal HTML
            fetch('edit_service_modal.php?id=' + encodeURIComponent(id))
                .then(res => res.text())
                .then(html => {
                    // Create modal if not exists
                    let modalEl = document.getElementById('editModal');
                    if (!modalEl) {
                        modalEl = document.createElement('div');
                        modalEl.className = 'modal fade';
                        modalEl.id = 'editModal';
                        modalEl.tabIndex = -1;
                        modalEl.innerHTML = '<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Service</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"></div></div></div>';
                        document.body.appendChild(modalEl);
                    }

                    modalEl.querySelector('.modal-body').innerHTML = html;
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();

                    // Attach submit handler
                    const form = modalEl.querySelector('#editServiceFormAjax');
                    if (form) {
                        form.addEventListener('submit', function(ev) {
                            ev.preventDefault();
                            const formData = new FormData(form);
                            formData.append('ajax', '1');

                            fetch('../actions/update_service.php', {
                                method: 'POST',
                                body: formData,
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    // Update row in table
                                    const d = data.data;
                                    const row = document.getElementById('row-' + d.id);
                                    if (row) {
                                        row.querySelector('.col-jenis').textContent = d.jenis_hp;
                                        row.querySelector('.col-tanggal').textContent = d.tanggal_service;
                                        row.querySelector('.col-expired').textContent = d.tanggal_expired;
                                        const s = row.querySelector('.col-sisa > .badge');
                                        s.textContent = d.sisa + ' hari';
                                        s.className = 'badge ' + d.badgeClass;
                                    }

                                    // Close modal
                                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();

                                    // Flash small alert
                                    showTempAlert(data.message || 'Berhasil diperbarui.', 'success');
                                } else {
                                    showTempAlert(data.message || 'Gagal menyimpan.', 'danger');
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                showTempAlert('Terjadi kesalahan jaringan.', 'danger');
                            });
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    showTempAlert('Gagal memuat form edit.', 'danger');
                });
        }

        // Delete handler using AJAX
        function handleDelete(id, csrf) {
            if (!confirm('Yakin ingin menghapus garansi ini?')) return;

            const fd = new FormData();
            fd.append('id', id);
            fd.append('csrf_token', csrf);
            fd.append('ajax', '1');

            fetch('../actions/delete_service.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Remove row
                    const row = document.getElementById('row-' + id);
                    if (row) row.remove();

                    // Update counts
                    const badge = document.getElementById('activeCountBadge');
                    if (badge) {
                        const parts = badge.textContent.trim().split(' ');
                        const count = parseInt(parts[0] || '0') - 1;
                        badge.textContent = count + ' Garansi Aktif';
                    }

                    showTempAlert(data.message || 'Berhasil dihapus.', 'success');
                } else {
                    showTempAlert(data.message || 'Gagal menghapus.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showTempAlert('Terjadi kesalahan jaringan.', 'danger');
            });
        }

        function showTempAlert(message, type) {
            const el = document.createElement('div');
            el.className = 'alert alert-' + type + ' mt-2';
            el.textContent = message;
            const container = document.querySelector('.container-fluid');
            container.prepend(el);
            setTimeout(() => el.remove(), 3500);
        }
    </script>
</body>
</html>
