<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Ambil data user dari session
$user = getCurrentUser();
$userId = $user['id'] ?? null;
$profileSuccess = $profileError = $pwdSuccess = $pwdError = '';

// Generate CSRF token
$csrf = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $profileError = $pwdError = 'Token CSRF tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        $formType = $_POST['form_type'] ?? '';

        // Update profil (nama)
        if ($formType === 'profile') {
            $newName = trim($_POST['nama_lengkap'] ?? '');

            if (empty($newName) || strlen($newName) < 2) {
                $profileError = 'Nama lengkap harus diisi minimal 2 karakter.';
            } else {
                try {
                    // Update nama di database menggunakan PDO
                    $stmt = $pdo->prepare('UPDATE users SET nama_lengkap = ? WHERE id = ?');
                    $stmt->execute([$newName, $userId]);

                    // Update session sehingga nama langsung berubah di navbar
                    $_SESSION['nama_lengkap'] = $newName;

                    $profileSuccess = 'Nama profil berhasil diperbarui.';

                    // Regenerate CSRF token after successful POST
                    $csrf = generateCSRFToken();
                } catch (PDOException $e) {
                    error_log('Profile update error: ' . $e->getMessage());
                    $profileError = 'Terjadi kesalahan saat menyimpan. Silakan coba lagi.';
                }
            }
        }

        // Ubah password
        if ($formType === 'change_password') {
            $currentPwd = $_POST['current_password'] ?? '';
            $newPwd = $_POST['new_password'] ?? '';
            $confirmPwd = $_POST['confirm_password'] ?? '';

            // Basic validations
            if (empty($currentPwd) || empty($newPwd) || empty($confirmPwd)) {
                $pwdError = 'Semua field harus diisi.';
            } elseif ($newPwd !== $confirmPwd) {
                $pwdError = 'Password baru dan konfirmasi tidak cocok.';
            } elseif (strlen($newPwd) < 8) {
                $pwdError = 'Password baru harus minimal 8 karakter.';
            } else {
                try {
                    // Ambil hash password saat ini dari DB
                    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
                    $stmt->execute([$userId]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$row || !password_verify($currentPwd, $row['password'])) {
                        $pwdError = 'Password saat ini salah.';
                    } else {
                        // Cegah mengganti dengan password yang sama
                        if (password_verify($newPwd, $row['password'])) {
                            $pwdError = 'Password baru tidak boleh sama dengan password saat ini.';
                        } else {
                            $newHash = password_hash($newPwd, PASSWORD_DEFAULT);
                            $stmt2 = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                            $stmt2->execute([$newHash, $userId]);

                            // Regenerate session dan log aktivitas
                            regenerateSession();
                            logUserActivity('CHANGE_PASSWORD', "User {$_SESSION['username']} mengubah password");

                            $pwdSuccess = 'Password berhasil diubah.';

                            // Regenerate CSRF token after successful POST
                            $csrf = generateCSRFToken();
                        }
                    }
                } catch (PDOException $e) {
                    error_log('Profile password update error: ' . $e->getMessage());
                    $pwdError = 'Terjadi kesalahan saat menyimpan. Silakan coba lagi.';
                }
            }
        }
    }
}

// Ambil nama terakhir dari session
$currentName = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Administrator');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Rudi Cell</title>
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
                        <h5 class="mb-0"><i class="bi bi-person-circle"></i> Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($profileSuccess): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($profileSuccess) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($profileError): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($profileError) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="form_type" value="profile">

                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control" value="<?= $currentName ?>" required minlength="2">
                                <div class="form-text">Nama yang akan muncul di navbar (mengganti teks "Administrator").</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <h5 class="mb-3"><i class="bi bi-key"></i> Ubah Password</h5>

                        <?php if ($pwdSuccess): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($pwdSuccess) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($pwdError): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($pwdError) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="changePasswordForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="form_type" value="change_password">

                            <div class="mb-3">
                                <label class="form-label">Password Saat Ini</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="new_password" id="newPassword" class="form-control" minlength="8" required>
                                <div class="form-text">Minimal 8 karakter.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required>
                                <div class="form-text text-danger small" id="pwMatchMsg" style="display:none;">Password tidak cocok.</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-warning" id="changePwBtn">Ubah Password</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Simple client-side validation for password match
        (function(){
            const newPw = document.getElementById('newPassword');
            const confirmPw = document.getElementById('confirmPassword');
            const msg = document.getElementById('pwMatchMsg');
            const btn = document.getElementById('changePwBtn');

            function checkMatch() {
                if (!newPw || !confirmPw) return;
                if (confirmPw.value === '') {
                    msg.style.display = 'none';
                    btn.disabled = false;
                    return;
                }
                if (newPw.value !== confirmPw.value) {
                    msg.style.display = 'block';
                    btn.disabled = true;
                } else {
                    msg.style.display = 'none';
                    btn.disabled = false;
                }
            }

            if (newPw && confirmPw) {
                newPw.addEventListener('input', checkMatch);
                confirmPw.addEventListener('input', checkMatch);
            }
        })();
    </script>
</body>
</html>