<?php
require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/functions.php';
require dirname(__DIR__) . '/includes/pengkinian_data_functions.php';
require dirname(__DIR__) . '/includes/distribusi_lkpd_functions.php';

$path = 'C:/Users/PC/Downloads/REKAP SISWA DAN KEBUTUHAN BUKU LKS MI MAARIF MGL.xlsx';
$r = parseDistribusiImportRows($path);
echo 'rows: ' . count($r['rows']) . PHP_EOL;
echo 'errors: ' . count($r['errors']) . PHP_EOL;
if (!empty($r['rows'])) {
    echo 'sample: ' . json_encode($r['rows'][0], JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
