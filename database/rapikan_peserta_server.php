<?php

declare(strict_types=1);

/**
 * Rapikan data dari dump server terbaru.
 * php database/rapikan_peserta_server.php
 * php database/rapikan_peserta_server.php --apply
 */

require_once dirname(__DIR__) . '/includes/functions.php';

const SERVER_DUMP = __DIR__ . '/peserta_rakerdinma (1).sql';
const OUTPUT_SQL = __DIR__ . '/peserta_rakerdinma_migrated.sql';
const BACKUP_SQL = __DIR__ . '/peserta_rakerdinma.sql';

$apply = in_array('--apply', $argv ?? [], true);

function columnExists(PDO $pdo, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute([':table' => 'peserta_rakerdinma', ':column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function ensureSchema(PDO $pdo): void
{
    $migrations = [
        'kode_provinsi' => file_get_contents(__DIR__ . '/migration_alamat_wilayah.sql'),
        'jenis_lembaga' => file_get_contents(__DIR__ . '/migration_jenis_lembaga.sql'),
        'nomor_wa_norm' => file_get_contents(__DIR__ . '/migration_nomor_wa_norm.sql'),
    ];

    foreach ($migrations as $column => $sql) {
        if (columnExists($pdo, $column)) {
            echo "Kolom {$column} sudah ada.\n";
            continue;
        }
        echo "Menambah kolom {$column}...\n";
        $pdo->exec($sql);
    }
}

function importServerDump(PDO $pdo): void
{
    if (!is_file(SERVER_DUMP)) {
        throw new RuntimeException('File dump tidak ditemukan: ' . SERVER_DUMP);
    }

    echo "Import dump server: " . basename(SERVER_DUMP) . "\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('DROP TABLE IF EXISTS peserta_rakerdinma');

    $sql = file_get_contents(SERVER_DUMP);
    $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
    $sql = preg_replace('/\/\*![^*]*\*+([^\/][^*]*\*+)*\//', '', $sql) ?? $sql;

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement === '' || stripos($statement, 'START TRANSACTION') === 0) {
            continue;
        }
        if (stripos($statement, 'COMMIT') === 0) {
            continue;
        }
        $pdo->exec($statement);
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $count = (int) $pdo->query('SELECT COUNT(*) FROM peserta_rakerdinma')->fetchColumn();
    echo "Import selesai: {$count} baris.\n";
}

function loadOriginalJabatanFromDump(): array
{
    if (!is_file(SERVER_DUMP)) {
        return [];
    }

    $map = [];
    $sql = file_get_contents(SERVER_DUMP);
    if (!preg_match_all(
        "/\((\d+),\s*'(?:[^'\\\\]|\\\\.)*',\s*(?:NULL|'(?:[^'\\\\]|\\\\.)*'),\s*'(?:[^'\\\\]|\\\\.)*',\s*'(?:[^'\\\\]|\\\\.)*',\s*'(?:[^'\\\\]|\\\\.)*',\s*'((?:[^'\\\\]|\\\\.)*)'/",
        $sql,
        $matches,
        PREG_SET_ORDER
    )) {
        return [];
    }

    foreach ($matches as $match) {
        $map[(int) $match[1]] = stripcslashes($match[2]);
    }

    return $map;
}

function sqlValue(mixed $value): string
{
    if ($value === null || $value === '') {
        return 'NULL';
    }

    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $value) . "'";
}

function exportFullSql(PDO $pdo, string $path): void
{
    $rows = $pdo->query(
        'SELECT id, nama, nip, nomor_wa, nomor_wa_norm, tempat_lahir,
                DATE_FORMAT(tanggal_lahir, "%Y-%m-%d") AS tanggal_lahir,
                jabatan, asal_lembaga, jenis_lembaga, alamat_lembaga,
                kode_provinsi, nama_provinsi, kode_kabupaten, nama_kabupaten,
                kode_kecamatan, nama_kecamatan, kode_kelurahan, nama_kelurahan,
                alamat_detail, alat_transportasi,
                DATE_FORMAT(created_at, "%Y-%m-%d %H:%i:%s") AS created_at
         FROM peserta_rakerdinma ORDER BY id'
    )->fetchAll();

    $lines = [
        '-- Data peserta_rakerdinma setelah rapikan (dari dump server)',
        '-- Sumber: peserta_rakerdinma (1).sql',
        '-- Generated: ' . date('Y-m-d H:i:s'),
        '',
        'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
        'SET time_zone = "+07:00";',
        '',
        'DELETE FROM `peserta_rakerdinma`;',
        '',
    ];

    $columns = [
        'id', 'nama', 'nip', 'nomor_wa', 'nomor_wa_norm', 'tempat_lahir', 'tanggal_lahir',
        'jabatan', 'asal_lembaga', 'jenis_lembaga', 'alamat_lembaga',
        'kode_provinsi', 'nama_provinsi', 'kode_kabupaten', 'nama_kabupaten',
        'kode_kecamatan', 'nama_kecamatan', 'kode_kelurahan', 'nama_kelurahan',
        'alamat_detail', 'alat_transportasi', 'created_at',
    ];

    foreach ($rows as $row) {
        $values = array_map(static fn (string $col) => sqlValue($row[$col] ?? null), $columns);
        $lines[] = 'INSERT INTO `peserta_rakerdinma` (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $values) . ');';
    }

    $maxId = max(array_column($rows, 'id'));
    $lines[] = '';
    $lines[] = 'ALTER TABLE `peserta_rakerdinma` AUTO_INCREMENT = ' . ($maxId + 1) . ';';
    $lines[] = '';

    file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
    echo "Export: {$path} (" . count($rows) . " baris)\n";
}

function printStats(PDO $pdo): void
{
    $total = (int) $pdo->query('SELECT COUNT(*) FROM peserta_rakerdinma')->fetchColumn();
    echo "\n=== Ringkasan ===\n";
    echo "Total peserta: {$total}\n";

    echo "\nJabatan:\n";
    foreach ($pdo->query('SELECT jabatan, COUNT(*) c FROM peserta_rakerdinma GROUP BY jabatan ORDER BY c DESC') as $r) {
        echo "  {$r['jabatan']}: {$r['c']}\n";
    }

    echo "\nTransportasi:\n";
    foreach ($pdo->query('SELECT alat_transportasi, COUNT(*) c FROM peserta_rakerdinma GROUP BY alat_transportasi ORDER BY c DESC') as $r) {
        echo "  {$r['alat_transportasi']}: {$r['c']}\n";
    }

    echo "\nJenis lembaga:\n";
    foreach ($pdo->query('SELECT jenis_lembaga, COUNT(*) c FROM peserta_rakerdinma GROUP BY jenis_lembaga ORDER BY c DESC') as $r) {
        echo "  " . ($r['jenis_lembaga'] ?: '(kosong)') . ": {$r['c']}\n";
    }
}

if (!$apply) {
    echo "Mode preview. Jalankan dengan --apply untuk import, migrasi, dan export.\n\n";
}

$pdo = getDb();

if ($apply) {
    importServerDump($pdo);
    ensureSchema($pdo);

    echo "\n--- Migrasi alamat ---\n";
    passthru('php ' . escapeshellarg(__DIR__ . '/migrate_alamat_peserta.php') . ' --apply', $code1);

    echo "\n--- Migrasi jenis lembaga ---\n";
    passthru('php ' . escapeshellarg(__DIR__ . '/migrate_jenis_lembaga.php') . ' --apply', $code2);

    echo "\n--- Migrasi jabatan & transportasi ---\n";
    $originalJabatan = loadOriginalJabatanFromDump();
    $rows = $pdo->query('SELECT id, jabatan, alat_transportasi FROM peserta_rakerdinma ORDER BY id')->fetchAll();
    $update = $pdo->prepare(
        'UPDATE peserta_rakerdinma SET jabatan = :jabatan, alat_transportasi = :transport WHERE id = :id'
    );
    foreach ($rows as $row) {
        $source = $originalJabatan[$row['id']] ?? $row['jabatan'];
        $jabatan = normalizeJabatan($source);
        $transport = normalizeTransportasi($row['alat_transportasi'] ?? '');
        echo sprintf("#%-3d jabatan: %-25s -> %-20s | transport -> %s\n", $row['id'], $source, $jabatan, $transport);
        $update->execute([':id' => $row['id'], ':jabatan' => $jabatan, ':transport' => $transport]);
    }

    echo "\n--- Migrasi nomor WA (dedupe) ---\n";
    passthru('php ' . escapeshellarg(__DIR__ . '/migrate_nomor_wa_norm.php') . ' --apply', $code3);

    exportFullSql($pdo, OUTPUT_SQL);
    copy(SERVER_DUMP, BACKUP_SQL);
    echo "Backup asli disalin ke: peserta_rakerdinma.sql\n";
} else {
    echo "Dump server: " . basename(SERVER_DUMP) . "\n";
    echo "Baris dalam dump: ";
    $content = file_get_contents(SERVER_DUMP);
    preg_match_all('/^\(\d+,/m', $content, $m);
    echo count($m[0]) . "\n";
    echo "\nDuplikat WA terdeteksi di dump:\n";
    preg_match_all("/\((\d+), '([^']*(?:\\'[^']*)*)', (?:NULL|'[^']*'), '(\d+)'/", $content, $matches, PREG_SET_ORDER);
    $byWa = [];
    foreach ($matches as $match) {
        $wa = normalizeNomorWa(stripcslashes($match[3]));
        $byWa[$wa][] = ['id' => (int) $match[1], 'nama' => stripcslashes($match[2])];
    }
    foreach ($byWa as $wa => $group) {
        if (count($group) > 1) {
            echo "  WA {$wa}: ";
            foreach ($group as $g) {
                echo "#{$g['id']} {$g['nama']} ";
            }
            echo "\n";
        }
    }
}

printStats($pdo);

if ($apply) {
    // export sudah dipanggil di blok apply di atas
} elseif (in_array('--export-only', $argv ?? [], true)) {
    exportFullSql($pdo, OUTPUT_SQL);
}
