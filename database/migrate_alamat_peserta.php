<?php

declare(strict_types=1);

/**
 * Migrasi data alamat manual ke format wilayah terstruktur.
 *
 * Penggunaan:
 *   php database/migrate_alamat_peserta.php --from-sql=database/peserta_rakerdinma.sql
 *   php database/migrate_alamat_peserta.php --from-sql=database/peserta_rakerdinma.sql --export
 *   php database/migrate_alamat_peserta.php --apply
 */

require_once dirname(__DIR__) . '/includes/functions.php';

const DEFAULT_PROVINSI = ['code' => '33', 'name' => 'Jawa Tengah'];
const DEFAULT_KABUPATEN = ['code' => '33.08', 'name' => 'Kabupaten Magelang'];

function fetchWilayah(string $level, string $code = ''): array
{
    $endpoints = [
        'districts' => 'https://wilayah.id/api/districts/' . rawurlencode($code) . '.json',
        'villages' => 'https://wilayah.id/api/villages/' . rawurlencode($code) . '.json',
    ];

    if (!isset($endpoints[$level])) {
        return [];
    }

    $json = @file_get_contents($endpoints[$level]);
    if ($json === false) {
        return [];
    }

    $data = json_decode($json, true);

    return is_array($data['data'] ?? null) ? $data['data'] : [];
}

function normalizeText(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? $text;
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;

    return trim($text);
}

function detectKecamatan(string $alamat, array $kecamatanList): ?array
{
    $normalized = normalizeText($alamat);

    $aliases = [
        'gunungpring' => 'Muntilan',
        'muntilan' => 'Muntilan',
        'mungkid' => 'Mungkid',
        'wanurejo' => 'Borobudur',
        'bejen' => 'Borobudur',
        'secang' => 'Secang',
    ];

    foreach ($aliases as $keyword => $kecamatanName) {
        if (str_contains($normalized, $keyword)) {
            foreach ($kecamatanList as $kecamatan) {
                if (strcasecmp($kecamatan['name'], $kecamatanName) === 0) {
                    return $kecamatan;
                }
            }
        }
    }

    usort($kecamatanList, static function (array $a, array $b): int {
        return strlen($b['name']) <=> strlen($a['name']);
    });

    foreach ($kecamatanList as $kecamatan) {
        $name = normalizeText($kecamatan['name']);
        if ($name !== '' && str_contains($normalized, $name)) {
            return $kecamatan;
        }
    }

    return null;
}

function detectKelurahan(string $alamat, array $villages): ?array
{
    if ($villages === []) {
        return null;
    }

    $normalized = normalizeText($alamat);

    usort($villages, static function (array $a, array $b): int {
        return strlen($b['name']) <=> strlen($a['name']);
    });

    foreach ($villages as $village) {
        $name = normalizeText($village['name']);
        if ($name !== '' && strlen($name) >= 4 && str_contains($normalized, $name)) {
            return $village;
        }
    }

    return null;
}

function parseSqlDump(string $path): array
{
    $content = file_get_contents($path);
    if ($content === false) {
        return [];
    }

    if (!preg_match('/INSERT INTO `peserta_rakerdinma`[^V]+VALUES\s*(.+?);/s', $content, $match)) {
        return [];
    }

    $valuesBlock = $match[1];
    preg_match_all('/\((\d+),\s*\'((?:[^\'\\\\]|\\\\.)*)\',\s*(NULL|\'(?:[^\'\\\\]|\\\\.)*\'),\s*\'((?:[^\'\\\\]|\\\\.)*)\',\s*\'((?:[^\'\\\\]|\\\\.)*)\',\s*\'(\d{4}-\d{2}-\d{2})\',\s*\'((?:[^\'\\\\]|\\\\.)*)\',\s*\'((?:[^\'\\\\]|\\\\.)*)\',\s*\'((?:[^\'\\\\]|\\\\.)*)\',\s*\'((?:[^\'\\\\]|\\\\.)*)\',\s*\'(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\'\)/', $valuesBlock, $matches, PREG_SET_ORDER);

    $rows = [];
    foreach ($matches as $m) {
        $nip = $m[3] === 'NULL' ? null : stripcslashes(trim($m[3], "'"));
        $rows[] = [
            'id' => (int) $m[1],
            'nama' => stripcslashes($m[2]),
            'nip' => $nip,
            'nomor_wa' => stripcslashes($m[4]),
            'tempat_lahir' => stripcslashes($m[5]),
            'tanggal_lahir' => $m[6],
            'jabatan' => stripcslashes($m[7]),
            'asal_lembaga' => stripcslashes($m[8]),
            'alamat_lembaga' => stripcslashes($m[9]),
            'alat_transportasi' => stripcslashes($m[10]),
            'created_at' => $m[11],
        ];
    }

    return $rows;
}

function migrateRecord(array $row, array $kecamatanList, array &$villageCache): array
{
    $alamatAsli = trim($row['alamat_lembaga'] ?? '');

    if ($alamatAsli === '') {
        return $row;
    }

    if (!empty($row['kode_provinsi']) && !empty($row['kode_kabupaten']) && !empty($row['alamat_detail'])) {
        return $row;
    }

    $kecamatan = detectKecamatan($alamatAsli, $kecamatanList);
    if ($kecamatan === null && !empty($row['asal_lembaga'])) {
        $kecamatan = detectKecamatan($row['asal_lembaga'], $kecamatanList);
    }
    $kelurahan = null;

    if ($kecamatan !== null) {
        $kodeKec = $kecamatan['code'];
        if (!isset($villageCache[$kodeKec])) {
            $villageCache[$kodeKec] = fetchWilayah('villages', $kodeKec);
        }
        $kelurahan = detectKelurahan($alamatAsli, $villageCache[$kodeKec]);
    }

    $row['kode_provinsi'] = DEFAULT_PROVINSI['code'];
    $row['nama_provinsi'] = DEFAULT_PROVINSI['name'];
    $row['kode_kabupaten'] = DEFAULT_KABUPATEN['code'];
    $row['nama_kabupaten'] = DEFAULT_KABUPATEN['name'];
    $row['kode_kecamatan'] = $kecamatan['code'] ?? null;
    $row['nama_kecamatan'] = $kecamatan['name'] ?? null;
    $row['kode_kelurahan'] = $kelurahan['code'] ?? null;
    $row['nama_kelurahan'] = $kelurahan['name'] ?? null;
    $row['alamat_detail'] = $alamatAsli;
    $row['alamat_lembaga'] = $alamatAsli;

    return $row;
}

function sqlValue(mixed $value): string
{
    if ($value === null || $value === '') {
        return 'NULL';
    }

    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $value) . "'";
}

function exportSql(array $rows): string
{
    $lines = [];
    $lines[] = '-- Data peserta_rakerdinma setelah migrasi alamat wilayah';
    $lines[] = '-- Default: Jawa Tengah (33), Kabupaten Magelang (33.08)';
    $lines[] = '-- Generated: ' . date('Y-m-d H:i:s');
    $lines[] = '';
    $lines[] = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";';
    $lines[] = 'SET time_zone = "+07:00";';
    $lines[] = '';
    $lines[] = 'DELETE FROM `peserta_rakerdinma`;';
    $lines[] = '';

    foreach ($rows as $row) {
        $columns = [
            'id', 'nama', 'nip', 'nomor_wa', 'tempat_lahir', 'tanggal_lahir',
            'jabatan', 'asal_lembaga', 'alamat_lembaga',
            'kode_provinsi', 'nama_provinsi', 'kode_kabupaten', 'nama_kabupaten',
            'kode_kecamatan', 'nama_kecamatan', 'kode_kelurahan', 'nama_kelurahan',
            'alamat_detail', 'alat_transportasi', 'created_at',
        ];

        $values = [];
        foreach ($columns as $col) {
            $values[] = sqlValue($row[$col] ?? null);
        }

        $lines[] = 'INSERT INTO `peserta_rakerdinma` (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $values) . ');';
    }

    $lines[] = '';
    $maxId = max(array_column($rows, 'id'));
    $lines[] = 'ALTER TABLE `peserta_rakerdinma` AUTO_INCREMENT = ' . ($maxId + 1) . ';';

    return implode(PHP_EOL, $lines) . PHP_EOL;
}

$apply = in_array('--apply', $argv ?? [], true);
$export = in_array('--export', $argv ?? [], true);
$fromSql = null;

foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--from-sql=')) {
        $fromSql = substr($arg, strlen('--from-sql='));
    }
}

echo "Memuat data kecamatan Kabupaten Magelang...\n";
$kecamatanList = fetchWilayah('districts', DEFAULT_KABUPATEN['code']);

if ($kecamatanList === []) {
    fwrite(STDERR, "Gagal memuat data kecamatan dari Wilayah.id\n");
    exit(1);
}

$pdo = null;

if ($fromSql !== null) {
    if (!is_file($fromSql)) {
        fwrite(STDERR, "File tidak ditemukan: {$fromSql}\n");
        exit(1);
    }

    $rows = parseSqlDump($fromSql);
    echo 'Memuat ' . count($rows) . " baris dari {$fromSql}\n";
} else {
    $pdo = getDb();
    $stmt = $pdo->query('SELECT * FROM peserta_rakerdinma ORDER BY id ASC');
    $rows = $stmt->fetchAll();
}

if ($rows === []) {
    echo "Tidak ada data peserta.\n";
    exit(0);
}

$villageCache = [];
$migrated = [];
$stats = ['total' => 0, 'kecamatan' => 0, 'kelurahan' => 0, 'skip' => 0];

foreach ($rows as $row) {
    $stats['total']++;
    $before = $row;
    $after = migrateRecord($row, $kecamatanList, $villageCache);

    if (!empty($before['kode_provinsi']) && !empty($before['alamat_detail'])) {
        $stats['skip']++;
    } else {
        if (!empty($after['kode_kecamatan'])) {
            $stats['kecamatan']++;
        }
        if (!empty($after['kode_kelurahan'])) {
            $stats['kelurahan']++;
        }
    }

    $migrated[] = $after;

    $kecInfo = $after['nama_kecamatan'] ?? '-';
    $kelInfo = $after['nama_kelurahan'] ?? '-';
    echo sprintf(
        "#%d %-25s | Kec: %-12s | Kel: %s\n",
        $after['id'],
        mb_substr($after['nama'], 0, 25),
        $kecInfo,
        $kelInfo
    );
}

echo "\n--- Ringkasan ---\n";
echo "Total data      : {$stats['total']}\n";
echo "Kecamatan cocok : {$stats['kecamatan']}\n";
echo "Kelurahan cocok : {$stats['kelurahan']}\n";
echo "Sudah migrasi   : {$stats['skip']}\n";

if ($export) {
    $path = dirname(__FILE__) . '/peserta_rakerdinma_migrated.sql';
    file_put_contents($path, exportSql($migrated));
    echo "\nExport SQL: {$path}\n";
}

if (!$apply) {
    echo "\nMode preview. Tambahkan --apply untuk simpan ke database.\n";
    echo "Tambahkan --export untuk generate file SQL.\n";
    exit(0);
}

if ($pdo === null) {
    $pdo = getDb();
}

$update = $pdo->prepare(
    'UPDATE peserta_rakerdinma SET
        alamat_lembaga = :alamat_lembaga,
        kode_provinsi = :kode_provinsi,
        nama_provinsi = :nama_provinsi,
        kode_kabupaten = :kode_kabupaten,
        nama_kabupaten = :nama_kabupaten,
        kode_kecamatan = :kode_kecamatan,
        nama_kecamatan = :nama_kecamatan,
        kode_kelurahan = :kode_kelurahan,
        nama_kelurahan = :nama_kelurahan,
        alamat_detail = :alamat_detail
     WHERE id = :id'
);

$insert = $pdo->prepare(
    'INSERT INTO peserta_rakerdinma
        (id, nama, nip, nomor_wa, tempat_lahir, tanggal_lahir, jabatan, asal_lembaga,
         alamat_lembaga, kode_provinsi, nama_provinsi, kode_kabupaten, nama_kabupaten,
         kode_kecamatan, nama_kecamatan, kode_kelurahan, nama_kelurahan, alamat_detail,
         alat_transportasi, created_at)
     VALUES
        (:id, :nama, :nip, :nomor_wa, :tempat_lahir, :tanggal_lahir, :jabatan, :asal_lembaga,
         :alamat_lembaga, :kode_provinsi, :nama_provinsi, :kode_kabupaten, :nama_kabupaten,
         :kode_kecamatan, :nama_kecamatan, :kode_kelurahan, :nama_kelurahan, :alamat_detail,
         :alat_transportasi, :created_at)
     ON DUPLICATE KEY UPDATE
         alamat_lembaga = VALUES(alamat_lembaga),
         kode_provinsi = VALUES(kode_provinsi),
         nama_provinsi = VALUES(nama_provinsi),
         kode_kabupaten = VALUES(kode_kabupaten),
         nama_kabupaten = VALUES(nama_kabupaten),
         kode_kecamatan = VALUES(kode_kecamatan),
         nama_kecamatan = VALUES(nama_kecamatan),
         kode_kelurahan = VALUES(kode_kelurahan),
         nama_kelurahan = VALUES(nama_kelurahan),
         alamat_detail = VALUES(alamat_detail)'
);

$pdo->beginTransaction();
try {
    foreach ($migrated as $row) {
        if ($fromSql !== null) {
            $insert->execute([
                ':id' => $row['id'],
                ':nama' => $row['nama'],
                ':nip' => $row['nip'],
                ':nomor_wa' => $row['nomor_wa'],
                ':tempat_lahir' => $row['tempat_lahir'],
                ':tanggal_lahir' => $row['tanggal_lahir'],
                ':jabatan' => $row['jabatan'],
                ':asal_lembaga' => $row['asal_lembaga'],
                ':alamat_lembaga' => $row['alamat_lembaga'],
                ':kode_provinsi' => $row['kode_provinsi'],
                ':nama_provinsi' => $row['nama_provinsi'],
                ':kode_kabupaten' => $row['kode_kabupaten'],
                ':nama_kabupaten' => $row['nama_kabupaten'],
                ':kode_kecamatan' => $row['kode_kecamatan'],
                ':nama_kecamatan' => $row['nama_kecamatan'],
                ':kode_kelurahan' => $row['kode_kelurahan'],
                ':nama_kelurahan' => $row['nama_kelurahan'],
                ':alamat_detail' => $row['alamat_detail'],
                ':alat_transportasi' => $row['alat_transportasi'],
                ':created_at' => $row['created_at'],
            ]);
            continue;
        }

        if (!empty($row['kode_provinsi']) && !empty($row['alamat_detail'])) {
            continue;
        }

        $update->execute([
            ':id' => $row['id'],
            ':alamat_lembaga' => $row['alamat_lembaga'],
            ':kode_provinsi' => $row['kode_provinsi'],
            ':nama_provinsi' => $row['nama_provinsi'],
            ':kode_kabupaten' => $row['kode_kabupaten'],
            ':nama_kabupaten' => $row['nama_kabupaten'],
            ':kode_kecamatan' => $row['kode_kecamatan'],
            ':nama_kecamatan' => $row['nama_kecamatan'],
            ':kode_kelurahan' => $row['kode_kelurahan'],
            ':nama_kelurahan' => $row['nama_kelurahan'],
            ':alamat_detail' => $row['alamat_detail'],
        ]);
    }
    $pdo->commit();
    echo "\nMigrasi berhasil disimpan ke database.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Gagal migrasi: " . $e->getMessage() . "\n");
    exit(1);
}
