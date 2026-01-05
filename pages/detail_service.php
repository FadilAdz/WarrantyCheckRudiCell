<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/encryption.php';

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    header('Location: garansi_aktif.php');
    exit;
}

$conn = getConnection();

$sql = "SELECT sr.*, u.nama_lengkap as created_by_name 
        FROM service_records sr 
        LEFT JOIN users u ON sr.created_by = u.id 
        WHERE sr.id = ?";
$data = fetchData($conn, $sql, [$id], 'i');

if (empty($data)) {
    header('Location: garansi_aktif.php');
    exit;
}

$record = $data[0];

// Dekripsi data sensitif (aman)
$record['nomor_hp'] = '';
$transaksi = [];
try {
    if (!empty($record['nomor_hp_encrypted']) && function_exists('decryptData')) {
        $record['nomor_hp'] = decryptData($record['nomor_hp_encrypted']);
    }
    if (!empty($record['data_transaksi_encrypted']) && function_exists('decryptData')) {
        $dec = decryptData($record['data_transaksi_encrypted']);
        $transaksi = json_decode($dec, true) ?: [];
    }
} catch (Throwable $ex) {
    // fallback: biarkan nilai default kosong
}

$csrf = generateCSRFToken();
$costVal = $transaksi['biaya_service'] ?? null;
$hasCost = is_numeric($costVal) && intval($costVal) > 0; 

$conn->close();

// Hitung status garansi
$today = new DateTime();
$expired = new DateTime($record['tanggal_expired']);
$sisaHari = $today->diff($expired)->days;
$isActive = strtotime($record['tanggal_expired']) >= strtotime(date('Y-m-d'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Service - Rudi Cell</title>
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
                    <h1 class="h2"><i class="bi bi-file-text"></i> Detail Service</h1>
                    <div>
                        <a href="../actions/print_receipt.php?id=<?= $record['id'] ?>" class="btn btn-primary me-2" target="_blank">
                            <i class="bi bi-printer"></i> Cetak Struk
                        </a>
                        <a href="garansi_aktif.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <!-- Status Garansi -->
                        <div class="card mb-3 border-<?= $isActive ? 'success' : 'danger' ?>">
                            <div class="card-header bg-<?= $isActive ? 'success' : 'danger' ?> text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-shield-<?= $isActive ? 'check' : 'x' ?>"></i>
                                    Status Garansi: <?= $isActive ? 'AKTIF' : 'EXPIRED' ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Kode Garansi:</strong></p>
                                        <h4><span class="badge bg-dark"><?= htmlspecialchars($record['kode_garansi']) ?></span></h4>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <?php if ($isActive): ?>
                                            <p class="mb-1"><strong>Sisa Masa Garansi:</strong></p>
                                            <h4><span class="badge bg-success"><?= $sisaHari ?> Hari</span></h4>
                                        <?php else: ?>
                                            <p class="mb-1"><strong>Expired Sejak:</strong></p>
                                            <h4><span class="badge bg-danger"><?= $expired->format('d M Y') ?></span></h4>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Data Customer -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-person"></i> Data Customer</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="30%"><strong>Nomor HP</strong></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="phoneDisplay" class="badge bg-secondary">
                                                    <i class="bi bi-lock-fill"></i> **********
                                                </span>
                                                <button class="btn btn-sm btn-outline-primary" onclick="decryptData('phone')">
                                                    <i class="bi bi-unlock"></i> Lihat
                                                </button>
                                            </div>
                                            <small class="text-muted">Data terenkripsi - hanya admin yang bisa melihat</small>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Detail Service -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-tools"></i> Detail Service</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="30%"><strong>Jenis HP</strong></td>
                                        <td><?= htmlspecialchars($record['jenis_hp']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Keluhan / Kerusakan</strong></td>
                                        <td><?= nl2br(htmlspecialchars($record['keluhan'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Service</strong></td>
                                        <td><?= date('d F Y', strtotime($record['tanggal_service'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Masa Garansi</strong></td>
                                        <td><?= $record['masa_garansi_hari'] ?> hari</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Expired</strong></td>
                                        <td><?= date('d F Y', strtotime($record['tanggal_expired'])) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Data Transaksi (Encrypted) -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-cash-stack"></i> Data Transaksi
                                    <span class="badge bg-info float-end">
                                        <i class="bi bi-lock-fill"></i> Encrypted
                                    </span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="30%"><strong>Biaya Service</strong></td>
                                        <td>
                                            <?php
                                                $costInt = isset($record['biaya_int']) ? intval($record['biaya_int']) : intval($transaksi['biaya_service'] ?? 0);
                                                if ($costInt > 0):
                                            ?>
                                                <span id="costDisplay" class="badge bg-success">Rp <?= number_format($costInt,0,',','.') ?></span>
                                                <?php if (isset($_SESSION['user_id'])): ?>
                                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="decryptData('cost')">Lihat</button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak tersedia</span>
                                                <?php if (isset($_SESSION['user_id'])): ?>
                                                    <button class="btn btn-sm btn-outline-primary ms-2" id="editBiayaBtn">Tambahkan Biaya</button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted">Data terenkripsi - hanya admin yang bisa melihat lebih detail</small>
                                        </td>
                                    </tr> 
                                    <tr>
                                        <td><strong>Metode Pembayaran</strong></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="paymentDisplay" class="badge bg-secondary">
                                                    <i class="bi bi-lock-fill"></i> **********
                                                </span>
                                                <button class="btn btn-sm btn-outline-primary" onclick="decryptData('payment')">
                                                    <i class="bi bi-unlock"></i> Lihat
                                                </button>
                                            </div>
                                            <small class="text-muted">Data terenkripsi - hanya admin yang bisa melihat</small>
                                        </td>
                                    </tr>
                                    <?php if (!empty($transaksi['catatan'])): ?>
                                    <tr>
                                        <td><strong>Catatan</strong></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="notesDisplay" class="badge bg-secondary">
                                                    <i class="bi bi-lock-fill"></i> **********
                                                </span>
                                                <button class="btn btn-sm btn-outline-primary" onclick="decryptData('notes')">
                                                    <i class="bi bi-unlock"></i> Lihat
                                                </button>
                                            </div>
                                            <small class="text-muted">Data terenkripsi - hanya admin yang bisa melihat</small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Dibuat Oleh</strong></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span id="creatorDisplay" class="badge bg-secondary">
                                                    <i class="bi bi-lock-fill"></i> **********
                                                </span>
                                                <button class="btn btn-sm btn-outline-primary" onclick="decryptData('creator')">
                                                    <i class="bi bi-unlock"></i> Lihat
                                                </button>
                                            </div>
                                            <small class="text-muted">Data terenkripsi - hanya admin yang bisa melihat</small>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Info Enkripsi -->
                        <div class="card bg-light mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-shield-lock"></i> Informasi Keamanan</h6>
                            </div>
                            <div class="card-body">
                                <p class="small mb-2"><strong>Data yang Dienkripsi:</strong></p>
                                <ul class="small">
                                    <li>Nomor HP Customer</li>
                                    <li>Biaya Service</li>
                                    <li>Catatan Transaksi Internal</li>
                                </ul>
                                
                                <div class="alert alert-info small mb-0">
                                    <i class="bi bi-info-circle"></i> 
                                    Data dienkripsi menggunakan <strong>AES-256-CBC</strong> untuk melindungi privasi customer.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Timeline -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Timeline</h6>
                            </div>
                            <div class="card-body">
                                <div class="small">
                                    <p class="mb-2">
                                        <i class="bi bi-circle-fill text-primary"></i>
                                        <strong>Dibuat:</strong><br>
                                        <span class="ms-3"><?= date('d M Y, H:i', strtotime($record['created_at'])) ?></span>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-circle-fill text-success"></i>
                                        <strong>Service:</strong><br>
                                        <span class="ms-3"><?= date('d M Y', strtotime($record['tanggal_service'])) ?></span>
                                    </p>
                                    <p class="mb-0">
                                        <i class="bi bi-circle-fill text-<?= $isActive ? 'warning' : 'danger' ?>"></i>
                                        <strong>Expired:</strong><br>
                                        <span class="ms-3"><?= date('d M Y', strtotime($record['tanggal_expired'])) ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Modal untuk Password Admin -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-shield-lock"></i> Verifikasi Admin
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Masukkan password admin untuk melihat data terenkripsi:</p>
                    <input type="password" class="form-control" id="adminPassword" placeholder="Password Admin">
                    <div id="passwordError" class="text-danger mt-2" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="verifyAndDecrypt()">Verifikasi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Biaya (admin) -->
    <div class="modal fade" id="editBiayaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambahkan / Perbarui Biaya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Biaya (Rp)</label>
                        <input type="number" id="detailBiayaInput" class="form-control" min="0" placeholder="200000">
                        <div id="detailBiayaError" class="text-danger mt-2" style="display:none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="detailSaveBiayaBtn">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentDecryptType = '';     let decryptedData = {
        phone: '<?= addslashes($record['nomor_hp']) ?>',
        cost: <?= ($hasCost ? "'Rp " . addslashes(number_format($transaksi['biaya_service'],0,',','.')) . "'" : "''") ?>,
        payment: '<?= addslashes($transaksi['metode_pembayaran'] ?? 'Cash') ?>',
        notes: '<?= addslashes(nl2br($transaksi['catatan'] ?? '')) ?>',
        creator: '<?= addslashes($transaksi['created_by'] ?? '-') ?>'
    };

    function decryptData(type) {
        currentDecryptType = type;
        document.getElementById('adminPassword').value = '';
        document.getElementById('passwordError').style.display = 'none';
        new bootstrap.Modal(document.getElementById('passwordModal')).show();
    }

    function verifyAndDecrypt() {
        const password = document.getElementById('adminPassword').value;

        // debug area
        let dbg = document.getElementById('verifyDebug');
        if (!dbg) {
            dbg = document.createElement('div');
            dbg.id = 'verifyDebug';
            dbg.style.fontSize = '12px';
            dbg.style.marginTop = '8px';
            dbg.style.color = '#666';
            document.querySelector('#passwordModal .modal-body').appendChild(dbg);
        }

        dbg.textContent = '';

        if (!password) {
            document.getElementById('passwordError').textContent = 'Password tidak boleh kosong!';
            document.getElementById('passwordError').style.display = 'block';
            return;
        }

        // Kirim request ke server untuk verifikasi password dan minta data terenkripsi untuk record ini
        const payload = new URLSearchParams();
        payload.append('password', password);
        payload.append('id', '<?= intval($record['id']) ?>');

        fetch('../actions/verify_admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'same-origin', // ensure cookies are sent
            body: payload.toString()
        })
        .then(response => {
            dbg.textContent = 'HTTP ' + response.status + ' - ' + response.statusText;
            return response.text().then(txt => ({ status: response.status, text: txt }));
        })
        .then(resp => {
            dbg.textContent += '\nResponse body (truncated): ' + resp.text.substring(0, 1000);
            let data;
            try {
                data = JSON.parse(resp.text);
            } catch (e) {
                console.error('Invalid JSON', e, resp.text);
                document.getElementById('passwordError').textContent = 'Terjadi kesalahan server (response bukan JSON). Silakan cek log.';
                document.getElementById('passwordError').style.display = 'block';
                return;
            }

            if (data.success) {
                // Jika server mengembalikan data dekripsi, tampilkan semuanya
                if (data.data) {
                    if (data.data.phone) {
                        const el = document.getElementById('phoneDisplay');
                        el.innerHTML = '<i class="bi bi-check-circle"></i> ' + data.data.phone;
                        el.className = 'badge bg-success';
                    }

                    if (data.data.cost) {
                        const el = document.getElementById('costDisplay');
                        el.innerHTML = '<i class="bi bi-check-circle"></i> ' + data.data.cost;
                        el.className = 'badge bg-success';
                    }

                    if (data.data.payment) {
                        const el = document.getElementById('paymentDisplay');
                        el.innerHTML = '<i class="bi bi-check-circle"></i> ' + data.data.payment;
                        el.className = 'badge bg-success';
                    }

                    if (data.data.notes) {
                        const el = document.getElementById('notesDisplay');
                        el.innerHTML = '<i class="bi bi-check-circle"></i> ' + data.data.notes;
                        el.className = 'badge bg-success';
                    }

                    if (data.data.creator) {
                        const el = document.getElementById('creatorDisplay');
                        el.innerHTML = '<i class="bi bi-check-circle"></i> ' + data.data.creator;
                        el.className = 'badge bg-success';
                    }
                } else {
                    // fallback: jika tidak ada data, beri tahu user
                    document.getElementById('passwordError').textContent = 'Data terenkripsi tidak dapat ditemukan atau format tidak valid.';
                    document.getElementById('passwordError').style.display = 'block';
                    return;
                }

                // Tutup modal
                bootstrap.Modal.getInstance(document.getElementById('passwordModal')).hide();

            } else {
                document.getElementById('passwordError').textContent = data.message || 'Password admin salah!';
                document.getElementById('passwordError').style.display = 'block';
            }
        })
        .catch(error => {
            console.error(error);
            document.getElementById('passwordError').textContent = 'Terjadi kesalahan. Silakan coba lagi.';
            document.getElementById('passwordError').style.display = 'block';
            dbg.textContent += '\nError: ' + (error.message || error);
        });
    }

    // Add Edit Biaya modal handlers (if present)
    (function(){
        const editBtn = document.getElementById('editBiayaBtn');
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                const modal = new bootstrap.Modal(document.getElementById('editBiayaModal'));
                document.getElementById('detailBiayaInput').value = '';
                document.getElementById('detailBiayaError').style.display = 'none';
                modal.show();
            });
        }

        const saveBtn = document.getElementById('detailSaveBiayaBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const input = document.getElementById('detailBiayaInput');
                const err = document.getElementById('detailBiayaError');
                err.style.display = 'none';
                let val = input.value.trim();
                if (!val || !/^[0-9]+$/.test(val)) {
                    err.textContent = 'Masukkan angka (mis. 200000)';
                    err.style.display = 'block';
                    return;
                }

                fetch('../actions/update_biaya.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    credentials: 'same-origin',
                    body: new URLSearchParams({
                        csrf_token: '<?= htmlspecialchars($csrf) ?>',
                        id: '<?= intval($record['id']) ?>',
                        biaya: val
                    })
                })
                .then(r => r.json())
                .then(j => {
                    if (j.success) {
                        // Reload to reflect new state
                        location.reload();
                    } else {
                        err.textContent = j.message || 'Terjadi kesalahan';
                        err.style.display = 'block';
                    }
                })
                .catch(e => {
                    err.textContent = 'Terjadi kesalahan jaringan';
                    err.style.display = 'block';
                });
            });
        }
    })();

    // Hapus event listener untuk session check karena kita tidak ingin menyimpan data
    </script>
</body>
</html>
