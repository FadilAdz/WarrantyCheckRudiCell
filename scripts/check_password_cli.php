<?php
// Temporary script to check plaintext password against users table hashes
// Usage: php scripts/check_password_cli.php "plaintext"

require_once __DIR__ . '/../config/database.php';

$plain = $argv[1] ?? '';
if (empty($plain)) {
    echo "Usage: php scripts/check_password_cli.php \"plaintext\"\n";
    exit(1);
}

try {
    // Use PDO connection ($pdo) from config
    if (!isset($pdo) || !$pdo) {
        throw new Exception('PDO not available');
    }

    $stmt = $pdo->prepare('SELECT id, username, nama_lengkap, password FROM users');
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $found = false;
    foreach ($rows as $r) {
        if (password_verify($plain, $r['password'])) {
            echo "MATCH: id={$r['id']} username={$r['username']} nama_lengkap={$r['nama_lengkap']}\n";
            $found = true;
        }
    }

    if (!$found) {
        echo "NO MATCH: plaintext did not match any user password\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
