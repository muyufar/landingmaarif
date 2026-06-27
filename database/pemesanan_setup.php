<?php

declare(strict_types=1);

/**
 * Cek & upgrade struktur tabel pemesanan.
 * CLI: php database/pemesanan_setup.php
 * Web: /database/pemesanan_setup.php (hapus setelah dipakai)
 */
require_once dirname(__DIR__) . '/includes/pemesanan_functions.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    ensurePemesananSchema();
    $table = pemesananTableName();
    $cols = pemesananTableColumns();

    echo "OK: tabel `{$table}`\n";
    echo "Kolom (" . count($cols) . "):\n";
    foreach ($cols as $col) {
        echo "  - {$col}\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
