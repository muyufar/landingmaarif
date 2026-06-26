<?php

declare(strict_types=1);

/**
 * Isi nomor_wa_norm, hapus duplikat (simpan ID paling awal), buat indeks unik.
 * php database/migrate_nomor_wa_norm.php
 * php database/migrate_nomor_wa_norm.php --apply
 */

require_once dirname(__DIR__) . '/includes/functions.php';

$apply = in_array('--apply', $argv ?? [], true);
$pdo = getDb();

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute([':table' => $table, ':column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function indexExists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index'
    );
    $stmt->execute([':table' => $table, ':index' => $index]);

    return (int) $stmt->fetchColumn() > 0;
}

if (!columnExists($pdo, 'peserta_rakerdinma', 'nomor_wa_norm')) {
    echo "Kolom nomor_wa_norm belum ada. Jalankan migration_nomor_wa_norm.sql dulu (tanpa CREATE UNIQUE INDEX).\n";
    echo "Atau jalankan ulang script ini setelah --apply menambahkan kolom.\n\n";
    if ($apply) {
        $pdo->exec('ALTER TABLE peserta_rakerdinma ADD COLUMN nomor_wa_norm varchar(20) DEFAULT NULL AFTER nomor_wa');
        echo "Kolom nomor_wa_norm ditambahkan.\n";
    } else {
        exit(1);
    }
}

$rows = $pdo->query('SELECT id, nama, nomor_wa, nomor_wa_norm, created_at FROM peserta_rakerdinma ORDER BY id')->fetchAll();
$update = $pdo->prepare('UPDATE peserta_rakerdinma SET nomor_wa_norm = :norm WHERE id = :id');

foreach ($rows as $row) {
    $norm = normalizeNomorWa($row['nomor_wa'] ?? '');
    echo sprintf("#%-3d %s | %s -> %s\n", $row['id'], $row['nama'], $row['nomor_wa'], $norm);
    if ($apply && $norm !== '') {
        $update->execute([':norm' => $norm, ':id' => $row['id']]);
    }
}

$groups = [];
foreach ($pdo->query('SELECT id, nama, nomor_wa, nomor_wa_norm, created_at FROM peserta_rakerdinma ORDER BY id') as $row) {
    $norm = $row['nomor_wa_norm'] ?: normalizeNomorWa($row['nomor_wa'] ?? '');
    if ($norm === '') {
        continue;
    }
    $groups[$norm][] = $row;
}

echo "\n--- Duplikat nomor WA ---\n";
$toDelete = [];
foreach ($groups as $norm => $group) {
    if (count($group) < 2) {
        continue;
    }
    $keep = $group[0];
    echo "Simpan #{$keep['id']} ({$keep['created_at']}), hapus: ";
    for ($i = 1; $i < count($group); $i++) {
        $toDelete[] = (int) $group[$i]['id'];
        echo "#{$group[$i]['id']} ";
    }
    echo "\n";
}

if (!$apply) {
    echo "\nPreview selesai. " . count($toDelete) . " baris duplikat akan dihapus dengan --apply.\n";
    exit(0);
}

if ($toDelete !== []) {
    $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
    $pdo->prepare("DELETE FROM peserta_rakerdinma WHERE id IN ({$placeholders})")->execute($toDelete);
    echo "\nMenghapus " . count($toDelete) . " duplikat.\n";
}

if (!indexExists($pdo, 'peserta_rakerdinma', 'idx_nomor_wa_norm')) {
    try {
        $pdo->exec('CREATE UNIQUE INDEX idx_nomor_wa_norm ON peserta_rakerdinma (nomor_wa_norm)');
        echo "Indeks unik idx_nomor_wa_norm dibuat.\n";
    } catch (PDOException $e) {
        echo "Gagal buat indeks unik: " . $e->getMessage() . "\n";
    }
}

echo "Selesai.\n";
