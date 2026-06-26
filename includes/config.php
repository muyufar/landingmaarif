<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// Konfigurasi database (Hostinger / production)
define('DB_HOST', 'localhost');
define('DB_NAME', 'u700125577_maarifnu');
define('DB_USER', 'u700125577_maarifnu');
define('DB_PASS', 'Maarifnu@1990');
define('DB_CHARSET', 'utf8mb4');

// Ganti password admin: php -r "echo password_hash('password-baru', PASSWORD_DEFAULT);"
define('ADMIN_PASSWORD_HASH', '$2y$10$U7hCHIPVBMJzyZMHznR76uELgZa5lCgLNh7.fgT3E/0pQ2aRbDTBm'); // default: rakerdinma2026

define('EVENT_TITLE', 'PENDAFTARAN RAPAT KERJA DINAS MA\'ARIF (RAKERDINMA)');
define('EVENT_SUBTITLE', 'LP MA\'ARIF NU PCNU KAB. MAGELANG TAHUN 2026');
