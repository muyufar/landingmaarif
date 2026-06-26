<?php

declare(strict_types=1);

/**
 * Rapikan jabatan dan transportasi peserta.
 * php database/migrate_jabatan_transportasi.php --apply
 */

require_once dirname(__DIR__) . '/includes/functions.php';

$apply = in_array('--apply', $argv ?? [], true);

function loadOriginalJabatanBackup(): array
{
    foreach (['peserta_rakerdinma (1).sql', 'peserta_rakerdinma.sql'] as $file) {
        $path = __DIR__ . '/' . $file;
        if (!is_file($path)) {
            continue;
        }

        $map = [];
        $sql = file_get_contents($path);
        if (!preg_match_all(
            "/\((\d+),\s*'(?:[^'\\\\]|\\\\.)*',\s*(?:NULL|'(?:[^'\\\\]|\\\\.)*'),\s*'(?:[^'\\\\]|\\\\.)*',\s*'(?:[^'\\\\]|\\\\.)*',\s*'(?:[^'\\\\]|\\\\.)*',\s*'((?:[^'\\\\]|\\\\.)*)'/",
            $sql,
            $matches,
            PREG_SET_ORDER
        )) {
            continue;
        }

        foreach ($matches as $match) {
            $map[(int) $match[1]] = stripcslashes($match[2]);
        }

        if ($map !== []) {
            return $map;
        }
    }

    return [];
}

$originalJabatan = loadOriginalJabatanBackup();

$pdo = getDb();
$rows = $pdo->query('SELECT id, jabatan, alat_transportasi FROM peserta_rakerdinma ORDER BY id')->fetchAll();
$jabatanStats = [];
$transportStats = [];

foreach ($rows as $row) {
    $jabatanSource = $originalJabatan[$row['id']] ?? $row['jabatan'];
    $jabatanBaru = normalizeJabatan($jabatanSource);
    $transportBaru = normalizeTransportasi($row['alat_transportasi'] ?? '');
    $jabatanStats[$jabatanBaru] = ($jabatanStats[$jabatanBaru] ?? 0) + 1;
    $transportStats[$transportBaru] = ($transportStats[$transportBaru] ?? 0) + 1;

    echo sprintf(
        "#%-3d Jabatan: %-25s -> %-20s | Transport: %-15s -> %s\n",
        $row['id'],
        $jabatanSource,
        $jabatanBaru,
        $row['alat_transportasi'],
        $transportBaru
    );
}

echo "\n--- Jabatan ---\n";
foreach ($jabatanStats as $k => $v) {
    echo "{$k}: {$v}\n";
}
echo "\n--- Transportasi ---\n";
foreach ($transportStats as $k => $v) {
    echo "{$k}: {$v}\n";
}

if (!$apply) {
    echo "\nPreview selesai. Jalankan dengan --apply untuk simpan.\n";
    exit(0);
}

$update = $pdo->prepare('UPDATE peserta_rakerdinma SET jabatan = :jabatan, alat_transportasi = :transport WHERE id = :id');
$pdo->beginTransaction();
foreach ($rows as $row) {
    $jabatanSource = $originalJabatan[$row['id']] ?? $row['jabatan'];
    $update->execute([
        ':id' => $row['id'],
        ':jabatan' => normalizeJabatan($jabatanSource),
        ':transport' => normalizeTransportasi($row['alat_transportasi'] ?? ''),
    ]);
}
$pdo->commit();
echo "\nMigrasi berhasil (" . count($rows) . " data).\n";
