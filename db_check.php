<?php

declare(strict_types=1);

/**
 * Cek koneksi DB di live server. Hapus file ini setelah selesai debug.
 * Buka: https://maarifnumagelang.or.id/db_check.php
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/includes/config.php';

echo "=== Cek Database RAKERDINMA ===\n\n";
echo 'DB_HOST: ' . DB_HOST . "\n";
echo 'DB_NAME: ' . DB_NAME . "\n";
echo 'DB_USER: ' . (defined('DB_USER') ? DB_USER : '(belum didefinisikan)') . "\n";
echo 'config.local.php: ' . (is_file(__DIR__ . '/includes/config.local.php') ? 'ADA (hapus di live!)' : 'tidak ada') . "\n\n";

try {
    require_once __DIR__ . '/includes/database.php';
    $pdo = getDb();
    echo "Koneksi PDO: OK\n\n";

    $required = [
        'jenis_lembaga', 'kode_provinsi', 'nama_kecamatan', 'alamat_detail', 'nomor_wa_norm',
    ];

    foreach ($required as $col) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
        );
        $stmt->execute([':t' => 'peserta_rakerdinma', ':c' => $col]);
        $ok = (int) $stmt->fetchColumn() > 0;
        echo ($ok ? '[OK]' : '[MISSING]') . " kolom {$col}\n";
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM peserta_rakerdinma')->fetchColumn();
    echo "\nJumlah peserta: {$count}\n";

    $pdo->query(
        'SELECT id, jenis_lembaga, kode_provinsi, nomor_wa_norm FROM peserta_rakerdinma LIMIT 1'
    );
    echo "Query aplikasi: OK\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
