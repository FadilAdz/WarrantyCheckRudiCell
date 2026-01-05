<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/encryption.php';

header('Content-Type: application/json');

// Ambil password dan optional id dari POST
$password = $_POST['password'] ?? '';
$id = intval($_POST['id'] ?? 0);

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password tidak boleh kosong']);
    exit;
}

try {
    $conn = getConnection();

    // Logging request (for debugging): jangan simpan password utuh
    $logFile = __DIR__ . '/../logs/verify_admin.log';
    $maskPwd = substr($password, 0, 1) . str_repeat('*', max(3, strlen($password)-2)) . substr($password, -1);
    $logEntry = sprintf("[%s] IP=%s | PHPSESSID=%s | SESSION_USER=%s | POST_id=%s | POST_password=%s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REMOTE_ADDR'] ?? 'cli',
        session_id() ?? '-',
        $_SESSION['username'] ?? '-',
        $id,
        $maskPwd
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND);

    // Coba verifikasi dengan password akun yang sedang login terlebih dahulu
    $verified = false;
    $verifiedBy = null;

    if (!empty($_SESSION['user_id'])) {
        $sqlUser = "SELECT password, username FROM users WHERE id = ? LIMIT 1";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param('i', $_SESSION['user_id']);
        $stmtUser->execute();
        $resUser = $stmtUser->get_result()->fetch_assoc();

        if ($resUser && password_verify($password, $resUser['password'])) {
            $verified = true;
            $verifiedBy = $resUser['username'] ?? 'user';
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Verified by session user ($verifiedBy)\n", FILE_APPEND);
        }
    }

    // Jika belum terverifikasi, coba akun admin sebagai fallback (legacy)
    if (!$verified) {
        $sql = "SELECT password FROM users WHERE username = 'admin' LIMIT 1";
        $result = fetchData($conn, $sql);

        if (!empty($result)) {
            $storedPassword = $result[0]['password'];
            if (password_verify($password, $storedPassword)) {
                $verified = true;
                $verifiedBy = 'admin';
                file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Verified by admin fallback\n", FILE_APPEND);
            }
        }
    }

    if (!$verified) {
        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Verification FAILED for session user: " . ($_SESSION['username'] ?? '-') . "\n", FILE_APPEND);
        logUserActivity('VERIFY_ADMIN_FAILED', "User {$_SESSION['username']} gagal verifikasi sensitive action");
        echo json_encode(['success' => false, 'message' => 'Password salah']);
        exit;
    }

    // Jika verifikasi sukses dan id diberikan, kembalikan data terenkripsi yang didekripsi
    $response = ['success' => true, 'message' => 'Password benar', 'verified_by' => $verifiedBy];

    if ($id > 0) {
        // Ambil record dan nama pembuat
        $sql2 = "SELECT sr.*, u.nama_lengkap as created_by_name FROM service_records sr LEFT JOIN users u ON sr.created_by = u.id WHERE sr.id = ? LIMIT 1";
        $stmt = $conn->prepare($sql2);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res) {
            // Dekripsi nomor hp dan data transaksi jika tersedia
            $phonePlain = '';
            $dataTransaksi = [];

            // Log presence and size of encrypted fields for debugging
            $encPhoneLen = isset($res['nomor_hp_encrypted']) ? strlen($res['nomor_hp_encrypted']) : 0;
            $encTransLen = isset($res['data_transaksi_encrypted']) ? strlen($res['data_transaksi_encrypted']) : 0;
            file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Encrypted lengths for id=$id: nomor_hp_encrypted_len={$encPhoneLen}, data_transaksi_encrypted_len={$encTransLen}\n", FILE_APPEND);

            if (!empty($res['nomor_hp_encrypted']) && function_exists('decryptData')) {
                try {
                    $phonePlain = decryptData($res['nomor_hp_encrypted']);
                } catch (Throwable $ex) {
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] decryptData error for phone: " . $ex->getMessage() . "\n", FILE_APPEND);
                    $phonePlain = '';
                }
            }

            if (!empty($res['data_transaksi_encrypted']) && function_exists('decryptData')) {
                try {
                    $decrypted = decryptData($res['data_transaksi_encrypted']);
                } catch (Throwable $ex) {
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] decryptData error for transaksi: " . $ex->getMessage() . "\n", FILE_APPEND);
                    $decrypted = '';
                }
                if (!empty($decrypted)) {
                    // Log decrypted snippet for debugging
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Decrypted payload for id=$id (truncated): " . substr($decrypted,0,1000) . "\n", FILE_APPEND);

                    // Pastikan hasil adalah JSON
                    $json = json_decode($decrypted, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        $dataTransaksi = $json;
                        // Log decoded keys and biaya value
                        $keys = array_keys($dataTransaksi);
                        $biayaVal = isset($dataTransaksi['biaya_service']) ? $dataTransaksi['biaya_service'] : '(not set)';
                        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Decoded keys for id=$id: " . json_encode($keys) . " | biaya_service=" . json_encode($biayaVal) . "\n", FILE_APPEND);
                    } else {
                        $dataTransaksi = [];
                        file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] JSON decode failed for id=$id\n", FILE_APPEND);
                    }
                } else {
                    $dataTransaksi = [];
                }
            }

            // Tentukan nilai yang akan dikembalikan
            $costVal = null;
            if (isset($dataTransaksi['biaya_service'])) {
                $costVal = $dataTransaksi['biaya_service'];
            } else {
                // coba kunci alternatif umum
                foreach (['biaya','harga','total','amount'] as $k) {
                    if (isset($dataTransaksi[$k])) { $costVal = $dataTransaksi[$k]; break; }
                }
            }

            // Jika tidak ada, coba ekstrak angka dari payload mentah
            if ((is_null($costVal) || $costVal === '') && !empty($decrypted)) {
                if (preg_match('/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $decrypted, $m)) {
                    $num = preg_replace('/[^\d]/', '', $m[1]);
                    $costVal = $num;
                    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Fallback extracted biaya for id=$id: " . $costVal . "\n", FILE_APPEND);
                }
            }

            // Prefer biaya_int column if available and >0
            $costInt = 0;
            if (isset($res['biaya_int']) && intval($res['biaya_int']) > 0) {
                $costInt = intval($res['biaya_int']);
            } else {
                $costInt = ($costVal !== null && $costVal !== '') ? intval(preg_replace('/\D/', '', $costVal)) : 0;
            }
            $cost = ($costInt > 0) ? formatRupiah($costInt) : '';
            $payment = $dataTransaksi['metode_pembayaran'] ?? 'Cash';
            $notes = $dataTransaksi['catatan'] ?? '';
            $creator = $dataTransaksi['created_by'] ?? ($res['created_by_name'] ?? '-');

            $response['data'] = [
                'phone' => $phonePlain,
                'cost' => $cost,
                'payment' => $payment,
                'notes' => nl2br(htmlspecialchars($notes)),
                'creator' => htmlspecialchars($creator)
            ];
        }
    }

    echo json_encode($response);
    $conn->close();

} catch (Exception $e) {
    // Log the exception message for debugging
    $logFile = __DIR__ . '/../logs/verify_admin.log';
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>