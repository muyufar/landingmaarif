<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', 'localhost');
define('DB_NAME', 'u700125577_maarifnu');
define('DB_CHARSET', 'utf8mb4');

if (is_file(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
} else {
    // Konfigurasi database production (Hostinger)
    define('DB_USER', 'u700125577_maarifnu');
    define('DB_PASS', 'Maarifnu@1990');
}

// Ganti password admin: php -r "echo password_hash('password-baru', PASSWORD_DEFAULT);"
define('ADMIN_PASSWORD_HASH', '$2y$10$U7hCHIPVBMJzyZMHznR76uELgZa5lCgLNh7.fgT3E/0pQ2aRbDTBm'); // default: rakerdinma2026

define('EVENT_TITLE', 'PENDAFTARAN RAPAT KERJA DINAS MA\'ARIF (RAKERDINMA)');
define('EVENT_SUBTITLE', 'LP MA\'ARIF NU PCNU KAB. MAGELANG TAHUN 2026');

define('PEMESANAN_TITLE', 'FORM PEMESANAN MAJALAH MOPDIK & BUKU SAKU IPNU-IPPNU');
define('PEMESANAN_SUBTITLE', 'LP MA\'ARIF NU KABUPATEN MAGELANG');
define('PEMESANAN_PAKET_NAMA', 'Paket Majalah MOPDIK dan Buku Saku IPNU-IPPNU');

define('DOKUMENTASI_DRIVE_FOLDER_ID', '1TqAjnj15HB-ZLUBCRduRB8ekLyBNCdAW');
define('DOKUMENTASI_DRIVE_FOLDER_URL', 'https://drive.google.com/drive/folders/1TqAjnj15HB-ZLUBCRduRB8ekLyBNCdAW?usp=sharing');
define('DOKUMENTASI_JUDUL', 'Dokumentasi Acara RAKERDINMA 2026');

define('SERTIFIKAT_NOMOR_AWAL', 3210);
define('SERTIFIKAT_NOMOR_SUFFIX', '/PC.LPM/E.12/VI/2026');

function basePath(): string
{
    static $path = null;

    if ($path !== null) {
        return $path;
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = realpath(APP_ROOT);

    if ($docRoot === false || $appRoot === false) {
        $path = '';
        return $path;
    }

    $docRoot = str_replace('\\', '/', $docRoot);
    $appRoot = str_replace('\\', '/', $appRoot);

    if (str_starts_with($appRoot, $docRoot)) {
        $path = rtrim(str_replace('\\', '/', substr($appRoot, strlen($docRoot))), '/');
    } else {
        $path = '';
    }

    return $path;
}

function url(string $path = ''): string
{
    $base = basePath();
    $path = ltrim(str_replace('\\', '/', $path), '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}
