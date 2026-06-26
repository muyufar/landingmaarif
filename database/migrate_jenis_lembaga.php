<?php

declare(strict_types=1);

/**
 * Migrasi jenis_lembaga dari nama lembaga (kata pertama).
 * php database/migrate_jenis_lembaga.php --apply
 */

require_once dirname(__DIR__) . '/includes/functions.php';

$apply = in_array('--apply', $argv ?? [], true);

try {
    $pdo = getDb();
} catch (Throwable $e) {
    fwrite(STDERR, "Gagal koneksi database: " . $e->getMessage() . "\n");
    exit(1);
}

try {
    $pdo->query('SELECT jenis_lembaga FROM peserta_rakerdinma LIMIT 1');
} catch (PDOException $e) {
    echo "Kolom jenis_lembaga belum ada. Jalankan migration_jenis_lembaga.sql dulu.\n";
    exit(1);
}

$rows = $pdo->query('SELECT id, asal_lembaga, jenis_lembaga FROM peserta_rakerdinma ORDER BY id')->fetchAll();
$stats = [];

foreach ($rows as $row) {
    $parsed = parseJenisLembaga($row['asal_lembaga'] ?? '');
    $stats[$parsed] = ($stats[$parsed] ?? 0) + 1;
    echo sprintf("#%-3d %-6s -> %s (%s)\n", $row['id'], $parsed, $row['asal_lembaga'], $row['jenis_lembaga'] ?? '-');
}

echo "\n--- Ringkasan ---\n";
foreach ($stats as $jenis => $count) {
    echo "{$jenis}: {$count}\n";
}

if (!$apply) {
    echo "\nPreview selesai. Jalankan dengan --apply untuk simpan.\n";
    exit(0);
}

$update = $pdo->prepare('UPDATE peserta_rakerdinma SET jenis_lembaga = :jenis WHERE id = :id');
$pdo->beginTransaction();
foreach ($rows as $row) {
    $update->execute([
        ':id' => $row['id'],
        ':jenis' => parseJenisLembaga($row['asal_lembaga'] ?? ''),
    ]);
}
$pdo->commit();
echo "\nMigrasi jenis_lembaga berhasil (" . count($rows) . " data).\n";
