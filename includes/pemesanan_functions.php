<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function pemesananTableName(): string
{
    static $name = null;

    if ($name !== null) {
        return $name;
    }

    ensurePemesananSchema();

    $pdo = getDb();
    if ($pdo->query("SHOW TABLES LIKE 'pemesanan'")->fetch()) {
        $name = 'pemesanan';
    } elseif ($pdo->query("SHOW TABLES LIKE 'pemesanan_buku'")->fetch()) {
        $name = 'pemesanan_buku';
    } else {
        $name = 'pemesanan';
    }

    return $name;
}

function pemesananTableColumns(): array
{
    $pdo = getDb();
    $table = pemesananTableName();

    return array_column(
        $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC),
        'Field'
    );
}

function ensurePemesananSchema(): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $pdo = getDb();
    $hasPemesanan = (bool) $pdo->query("SHOW TABLES LIKE 'pemesanan'")->fetch();
    $hasOld = (bool) $pdo->query("SHOW TABLES LIKE 'pemesanan_buku'")->fetch();

    if (!$hasPemesanan && !$hasOld) {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `pemesanan` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `jenis_layanan` varchar(30) NOT NULL DEFAULT 'mopdik',
              `nama_madrasah` varchar(200) NOT NULL,
              `nama_kepala` varchar(150) NOT NULL,
              `nomor_wa` varchar(30) NOT NULL,
              `nomor_wa_norm` varchar(20) DEFAULT NULL,
              `jenjang` varchar(30) DEFAULT NULL,
              `jumlah` int(10) unsigned DEFAULT NULL,
              `jenis_batik` varchar(150) DEFAULT NULL,
              `satuan_jenis_1` varchar(20) DEFAULT NULL,
              `satuan_jumlah_1` int(10) unsigned DEFAULT NULL,
              `satuan_jenis_2` varchar(20) DEFAULT NULL,
              `satuan_jumlah_2` int(10) unsigned DEFAULT NULL,
              `ukuran_s` int(10) unsigned NOT NULL DEFAULT 0,
              `ukuran_m` int(10) unsigned NOT NULL DEFAULT 0,
              `ukuran_l` int(10) unsigned NOT NULL DEFAULT 0,
              `ukuran_xl` int(10) unsigned NOT NULL DEFAULT 0,
              `ukuran_xxl` int(10) unsigned NOT NULL DEFAULT 0,
              `kelas_iv_mi` int(10) unsigned NOT NULL DEFAULT 0,
              `kelas_v_mi` int(10) unsigned NOT NULL DEFAULT 0,
              `kelas_vi_mi` int(10) unsigned NOT NULL DEFAULT 0,
              `kelas_vii_mts` int(10) unsigned NOT NULL DEFAULT 0,
              `kelas_viii_mts` int(10) unsigned NOT NULL DEFAULT 0,
              `kelas_ix_mts` int(10) unsigned NOT NULL DEFAULT 0,
              `kelas_x_ma` int(10) unsigned NOT NULL DEFAULT 0,
              `kelas_xi_ma` int(10) unsigned NOT NULL DEFAULT 0,
              `kelas_xii_ma` int(10) unsigned NOT NULL DEFAULT 0,
              `catatan` text DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_jenis_layanan` (`jenis_layanan`),
              KEY `idx_jenjang` (`jenjang`),
              KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $done = true;

        return;
    }

    $table = $hasPemesanan ? 'pemesanan' : 'pemesanan_buku';
    $columns = array_column($pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC), 'Field');

    $additions = [
        'jenis_layanan' => "`jenis_layanan` varchar(30) NOT NULL DEFAULT 'mopdik'",
        'jumlah' => '`jumlah` int(10) unsigned DEFAULT NULL',
        'jenis_batik' => '`jenis_batik` varchar(150) DEFAULT NULL',
        'satuan_jenis_1' => '`satuan_jenis_1` varchar(20) DEFAULT NULL',
        'satuan_jumlah_1' => '`satuan_jumlah_1` int(10) unsigned DEFAULT NULL',
        'satuan_jenis_2' => '`satuan_jenis_2` varchar(20) DEFAULT NULL',
        'satuan_jumlah_2' => '`satuan_jumlah_2` int(10) unsigned DEFAULT NULL',
        'ukuran_s' => '`ukuran_s` int(10) unsigned NOT NULL DEFAULT 0',
        'ukuran_m' => '`ukuran_m` int(10) unsigned NOT NULL DEFAULT 0',
        'ukuran_l' => '`ukuran_l` int(10) unsigned NOT NULL DEFAULT 0',
        'ukuran_xl' => '`ukuran_xl` int(10) unsigned NOT NULL DEFAULT 0',
        'ukuran_xxl' => '`ukuran_xxl` int(10) unsigned NOT NULL DEFAULT 0',
        'kelas_iv_mi' => '`kelas_iv_mi` int(10) unsigned NOT NULL DEFAULT 0',
        'kelas_v_mi' => '`kelas_v_mi` int(10) unsigned NOT NULL DEFAULT 0',
        'kelas_vi_mi' => '`kelas_vi_mi` int(10) unsigned NOT NULL DEFAULT 0',
        'kelas_vii_mts' => '`kelas_vii_mts` int(10) unsigned NOT NULL DEFAULT 0',
        'kelas_viii_mts' => '`kelas_viii_mts` int(10) unsigned NOT NULL DEFAULT 0',
        'kelas_ix_mts' => '`kelas_ix_mts` int(10) unsigned NOT NULL DEFAULT 0',
        'kelas_x_ma' => '`kelas_x_ma` int(10) unsigned NOT NULL DEFAULT 0',
        'kelas_xi_ma' => '`kelas_xi_ma` int(10) unsigned NOT NULL DEFAULT 0',
        'kelas_xii_ma' => '`kelas_xii_ma` int(10) unsigned NOT NULL DEFAULT 0',
    ];

    foreach ($additions as $column => $definition) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN {$definition}");
            $columns[] = $column;
        }
    }

    if (in_array('jenis_batik', $columns, true)) {
        try {
            $pdo->exec("ALTER TABLE `{$table}` MODIFY COLUMN `jenis_batik` varchar(150) DEFAULT NULL");
        } catch (PDOException) {
            // Abaikan jika MODIFY tidak didukung host
        }
    }

    if (in_array('jumlah', $columns, true)) {
        try {
            $pdo->exec("ALTER TABLE `{$table}` MODIFY COLUMN `jumlah` int(10) unsigned DEFAULT NULL");
        } catch (PDOException) {
            // Abaikan jika MODIFY gagal
        }
    }

    if (!$hasPemesanan && $hasOld) {
        $pdo->exec('RENAME TABLE `pemesanan_buku` TO `pemesanan`');
    }

    $done = true;
}

function jenjangPemesananOptions(): array
{
    return ['MI/SD', 'MTS/SMP', 'MA/SMA/SMK'];
}

function normalizeJenjangPemesanan(?string $jenjang): string
{
    $jenjang = trim((string) $jenjang);
    $legacy = [
        'MI' => 'MI/SD',
        'SD' => 'MI/SD',
        'MTS' => 'MTS/SMP',
        'SMP' => 'MTS/SMP',
        'MA' => 'MA/SMA/SMK',
        'SMA' => 'MA/SMA/SMK',
        'SMK' => 'MA/SMA/SMK',
    ];

    return $legacy[$jenjang] ?? $jenjang;
}

function jenjangPemesananFilterValues(string $jenjang): array
{
    $jenjang = normalizeJenjangPemesanan($jenjang);
    $groups = [
        'MI/SD' => ['MI/SD', 'MI', 'SD'],
        'MTS/SMP' => ['MTS/SMP', 'MTS', 'SMP'],
        'MA/SMA/SMK' => ['MA/SMA/SMK', 'MA', 'SMA', 'SMK'],
    ];

    return $groups[$jenjang] ?? [$jenjang];
}

function pemesananLayananCatalog(): array
{
    $jenjangSemua = jenjangPemesananOptions();

    return [
        'mopdik' => [
            'label' => 'Paket Majalah MOPDIK dan Buku Saku IPNU-IPPNU',
            'title' => 'FORM PEMESANAN MAJALAH MOPDIK & BUKU SAKU IPNU-IPPNU',
            'subtitle' => 'LP MA\'ARIF NU KABUPATEN MAGELANG',
            'icon' => '📚',
            'jenjang' => ['MTS/SMP', 'MA/SMA/SMK'],
            'jenjang_label' => 'Pilih Jenjang',
            'tipe' => 'jumlah',
            'jumlah_label' => 'Jumlah Paket',
        ],
        'batik' => [
            'label' => 'Batik Ma\'arif Siswa dan Guru',
            'title' => 'FORM PEMESANAN BATIK MA\'ARIF',
            'subtitle' => 'Kain Batik Siswa & Guru — Semua Jenjang',
            'icon' => '👔',
            'jenjang' => $jenjangSemua,
            'jenjang_label' => 'Pilih Jenjang',
            'tipe' => 'batik',
        ],
        'buku_kenuan' => [
            'label' => 'Buku Ke-NU-an',
            'title' => 'FORM PEMESANAN BUKU KE NU AN',
            'subtitle' => 'Pilih jenjang lalu isi jumlah buku per kelas',
            'icon' => '📖',
            'jenjang' => $jenjangSemua,
            'tipe' => 'kenuan',
        ],
        'buku_aswaja' => [
            'label' => 'Buku Tulis Karakter Aswaja',
            'title' => 'FORM PEMESANAN BUKU TULIS KARAKTER ASWAJA',
            'subtitle' => 'Semua Jenjang',
            'icon' => '📝',
            'jenjang' => $jenjangSemua,
            'jenjang_label' => 'Pilih Jenjang',
            'tipe' => 'jumlah',
            'jumlah_label' => 'Jumlah Buku',
        ],
    ];
}

function getPemesananLayanan(string $jenis): ?array
{
    $catalog = pemesananLayananCatalog();

    return $catalog[$jenis] ?? null;
}

function jenisBatikOptions(): array
{
    return ['Kain Batik Siswa', 'Kain Batik Guru'];
}

function parseJenisBatikSelected(mixed $value): array
{
    if (is_array($value)) {
        $items = $value;
    } else {
        $value = trim((string) $value);
        $items = $value === '' ? [] : array_map('trim', explode(',', $value));
    }

    $selected = [];
    foreach ($items as $item) {
        if (in_array($item, jenisBatikOptions(), true) && !in_array($item, $selected, true)) {
            $selected[] = $item;
        }
    }

    return $selected;
}

function satuanBatikOptions(): array
{
    return ['Roll', 'Meter'];
}

function bukuKenuanJenjangOptions(): array
{
    return jenjangPemesananOptions();
}

function bukuKenuanKelasFields(): array
{
    return [
        'kelas_iv_mi' => 'Kelas IV MI',
        'kelas_v_mi' => 'Kelas V MI',
        'kelas_vi_mi' => 'Kelas VI MI',
        'kelas_vii_mts' => 'Kelas VII MTS',
        'kelas_viii_mts' => 'Kelas VIII MTS',
        'kelas_ix_mts' => 'Kelas IX MTS',
        'kelas_x_ma' => 'Kelas X MA',
        'kelas_xi_ma' => 'Kelas XI MA',
        'kelas_xii_ma' => 'Kelas XII MA',
    ];
}

function bukuKenuanKelasGroups(): array
{
    return [
        'MI/SD' => ['kelas_iv_mi', 'kelas_v_mi', 'kelas_vi_mi'],
        'MTS/SMP' => ['kelas_vii_mts', 'kelas_viii_mts', 'kelas_ix_mts'],
        'MA/SMA/SMK' => ['kelas_x_ma', 'kelas_xi_ma', 'kelas_xii_ma'],
    ];
}

function getTotalKenuanKelas(array $row): int
{
    $total = 0;
    foreach (array_keys(bukuKenuanKelasFields()) as $key) {
        $total += max(0, (int) ($row[$key] ?? 0));
    }

    return $total;
}

function pemesananFormDefaults(string $jenis, ?array $row = null): array
{
    $defaults = [
        'jenis_layanan' => $jenis,
        'nama_madrasah' => '',
        'nama_kepala' => '',
        'nomor_wa' => '',
        'jenjang' => '',
        'jumlah' => '1',
        'jenis_batik' => '',
        'satuan_jenis_1' => '',
        'satuan_jumlah_1' => '',
        'satuan_jenis_2' => '',
        'satuan_jumlah_2' => '',
        'ukuran_s' => '0',
        'ukuran_m' => '0',
        'ukuran_l' => '0',
        'ukuran_xl' => '0',
        'ukuran_xxl' => '0',
        'catatan' => '',
    ];

    foreach (array_keys(bukuKenuanKelasFields()) as $kelasKey) {
        $defaults[$kelasKey] = '0';
    }

    if ($row === null) {
        return $defaults;
    }

    return array_merge($defaults, array_intersect_key($row, $defaults));
}

function getJumlahPemesanan(array $row): int
{
    if (($row['jenis_layanan'] ?? '') === 'buku_kenuan') {
        return getTotalKenuanKelas($row) ?: max(0, (int) ($row['jumlah'] ?? 0));
    }

    if (array_key_exists('jumlah', $row) && $row['jumlah'] !== null) {
        return max(0, (int) $row['jumlah']);
    }

    return max(
        !empty($row['pesan_mopdik']) ? (int) ($row['jumlah_mopdik'] ?? 0) : 0,
        !empty($row['pesan_buku_saku']) ? (int) ($row['jumlah_buku_saku'] ?? 0) : 0
    );
}

function validatePemesanan(array $input, string $jenis): array
{
    $layanan = getPemesananLayanan($jenis);
    if ($layanan === null) {
        return ['errors' => ['Jenis pemesanan tidak valid.'], 'data' => []];
    }

    $errors = [];
    $data = ['jenis_layanan' => $jenis];

    $namaMadrasah = trim($input['nama_madrasah'] ?? '');
    if ($namaMadrasah === '') {
        $errors[] = 'Field Nama Madrasah/Sekolah wajib diisi.';
    } else {
        $data['nama_madrasah'] = $namaMadrasah;
    }

    $namaKepala = trim($input['nama_kepala'] ?? '');
    if ($namaKepala === '') {
        $errors[] = 'Field Nama Kepala/Kepsek wajib diisi.';
    } else {
        $data['nama_kepala'] = $namaKepala;
    }

    $nomorWa = trim($input['nomor_wa'] ?? '');
    if ($nomorWa === '') {
        $errors[] = 'Field Nomor WA wajib diisi.';
    } elseif (normalizeNomorWa($nomorWa) === '') {
        $errors[] = 'Nomor WA tidak valid.';
    } else {
        $data['nomor_wa'] = $nomorWa;
    }

    $needsJenjang = !empty($layanan['jenjang']) && ($layanan['tipe'] ?? '') !== 'kenuan';
    if ($needsJenjang) {
        $jenjang = normalizeJenjangPemesanan($input['jenjang'] ?? '');
        if ($jenjang === '' || !in_array($jenjang, $layanan['jenjang'], true)) {
            $errors[] = 'Field ' . ($layanan['jenjang_label'] ?? 'Jenjang') . ' wajib dipilih.';
        } else {
            $data['jenjang'] = $jenjang;
        }
    }

    $data['catatan'] = trim($input['catatan'] ?? '');

    if ($layanan['tipe'] === 'jumlah') {
        $jumlah = (int) ($input['jumlah'] ?? 0);
        if ($jumlah < 1) {
            $errors[] = ($layanan['jumlah_label'] ?? 'Jumlah') . ' minimal 1.';
        } else {
            $data['jumlah'] = $jumlah;
        }
    } elseif ($layanan['tipe'] === 'batik') {
        $selectedBatik = parseJenisBatikSelected($input['jenis_batik'] ?? []);
        if ($selectedBatik === []) {
            $errors[] = 'Pilih minimal satu Jenis Pemesanan Batik.';
        } else {
            $data['jenis_batik'] = implode(', ', $selectedBatik);
        }

        $satuan1 = trim($input['satuan_jenis_1'] ?? '');
        $qty1 = max(0, (int) ($input['satuan_jumlah_1'] ?? 0));
        if ($satuan1 !== '' && !in_array($satuan1, satuanBatikOptions(), true)) {
            $errors[] = 'Satuan pertama tidak valid.';
        } elseif ($satuan1 !== '' && $qty1 < 1) {
            $errors[] = 'Isi jumlah untuk satuan pertama.';
        } elseif ($satuan1 === '' && $qty1 > 0) {
            $errors[] = 'Pilih jenis satuan pertama.';
        } else {
            $data['satuan_jenis_1'] = $satuan1 !== '' ? $satuan1 : null;
            $data['satuan_jumlah_1'] = $qty1 > 0 ? $qty1 : null;
        }

        $satuan2 = trim($input['satuan_jenis_2'] ?? '');
        $qty2 = max(0, (int) ($input['satuan_jumlah_2'] ?? 0));
        if ($satuan2 !== '' && !in_array($satuan2, satuanBatikOptions(), true)) {
            $errors[] = 'Satuan kedua tidak valid.';
        } elseif ($satuan2 !== '' && $qty2 < 1) {
            $errors[] = 'Isi jumlah untuk satuan kedua.';
        } elseif ($satuan2 === '' && $qty2 > 0) {
            $errors[] = 'Pilih jenis satuan kedua.';
        } else {
            $data['satuan_jenis_2'] = $satuan2 !== '' ? $satuan2 : null;
            $data['satuan_jumlah_2'] = $qty2 > 0 ? $qty2 : null;
        }

        $sizes = ['ukuran_s', 'ukuran_m', 'ukuran_l', 'ukuran_xl', 'ukuran_xxl'];
        $totalUkuran = 0;
        foreach ($sizes as $size) {
            $val = max(0, (int) ($input[$size] ?? 0));
            $data[$size] = $val;
            $totalUkuran += $val;
        }

        $hasSatuan = ($data['satuan_jumlah_1'] ?? null) || ($data['satuan_jumlah_2'] ?? null);
        if (!$hasSatuan && $totalUkuran < 1) {
            $errors[] = 'Isi satuan (Roll/Meter) atau jumlah ukuran (S–XXL) minimal satu.';
        }

        $data['jumlah'] = null;
    } elseif ($layanan['tipe'] === 'kenuan') {
        $jenjangKenuan = normalizeJenjangPemesanan($input['jenjang'] ?? '');
        if ($jenjangKenuan === '' || !in_array($jenjangKenuan, bukuKenuanJenjangOptions(), true)) {
            $errors[] = 'Pilih jenjang terlebih dahulu (MI/SD, MTS/SMP, atau MA/SMA/SMK).';
        } else {
            $data['jenjang'] = $jenjangKenuan;
        }

        foreach (array_keys(bukuKenuanKelasFields()) as $key) {
            $data[$key] = 0;
        }

        $totalKelas = 0;
        $groupKeys = bukuKenuanKelasGroups()[$jenjangKenuan] ?? [];
        foreach ($groupKeys as $key) {
            $qty = max(0, (int) ($input[$key] ?? 0));
            $data[$key] = $qty;
            $totalKelas += $qty;
        }

        if ($jenjangKenuan !== '' && $totalKelas < 1) {
            $errors[] = 'Isi jumlah buku minimal satu kelas untuk jenjang ' . $jenjangKenuan . '.';
        } elseif ($totalKelas > 0) {
            $data['jumlah'] = $totalKelas;
        }
    }

    return ['errors' => $errors, 'data' => $data];
}

function addPemesanan(array $data): bool
{
    ensurePemesananSchema();

    $pdo = getDb();
    $table = pemesananTableName();
    $norm = normalizeNomorWa($data['nomor_wa']);

    $row = [
        'jenis_layanan' => $data['jenis_layanan'] ?? 'mopdik',
        'nama_madrasah' => $data['nama_madrasah'],
        'nama_kepala' => $data['nama_kepala'],
        'nomor_wa' => $data['nomor_wa'],
        'nomor_wa_norm' => $norm !== '' ? $norm : null,
        'jenjang' => $data['jenjang'] ?? null,
        'jumlah' => $data['jumlah'] ?? null,
        'jenis_batik' => $data['jenis_batik'] ?? null,
        'satuan_jenis_1' => $data['satuan_jenis_1'] ?? null,
        'satuan_jumlah_1' => $data['satuan_jumlah_1'] ?? null,
        'satuan_jenis_2' => $data['satuan_jenis_2'] ?? null,
        'satuan_jumlah_2' => $data['satuan_jumlah_2'] ?? null,
        'ukuran_s' => (int) ($data['ukuran_s'] ?? 0),
        'ukuran_m' => (int) ($data['ukuran_m'] ?? 0),
        'ukuran_l' => (int) ($data['ukuran_l'] ?? 0),
        'ukuran_xl' => (int) ($data['ukuran_xl'] ?? 0),
        'ukuran_xxl' => (int) ($data['ukuran_xxl'] ?? 0),
        'catatan' => ($data['catatan'] ?? '') !== '' ? $data['catatan'] : null,
    ];

    foreach (array_keys(bukuKenuanKelasFields()) as $kelasKey) {
        $row[$kelasKey] = (int) ($data[$kelasKey] ?? 0);
    }

    $columns = pemesananTableColumns();
    $insert = [];
    foreach ($row as $key => $value) {
        if (!in_array($key, $columns, true)) {
            continue;
        }
        if ($value === null) {
            continue;
        }
        if (str_starts_with($key, 'kelas_') && (int) $value === 0) {
            continue;
        }
        $insert[$key] = $value;
    }

    if (!isset($insert['nama_madrasah'], $insert['nama_kepala'], $insert['nomor_wa'])) {
        throw new PDOException('Struktur tabel pemesanan belum lengkap. Jalankan migration_pemesanan_upgrade.sql.');
    }

    $colList = implode(', ', array_map(static fn (string $c): string => "`{$c}`", array_keys($insert)));
    $placeholders = implode(', ', array_map(static fn (string $c): string => ":{$c}", array_keys($insert)));

    $stmt = $pdo->prepare("INSERT INTO `{$table}` ({$colList}) VALUES ({$placeholders})");
    $params = [];
    foreach ($insert as $key => $value) {
        $params[":{$key}"] = $value;
    }

    return $stmt->execute($params);
}

/** @deprecated */
function validatePemesananBuku(array $input): array
{
    return validatePemesanan($input, 'mopdik');
}

/** @deprecated */
function addPemesananBuku(array $data): bool
{
    $data['jenis_layanan'] = 'mopdik';

    return addPemesanan($data);
}

function loadPemesanan(string $search = '', array $filters = []): array
{
    $pdo = getDb();
    $table = pemesananTableName();
    $sql = "SELECT * FROM {$table} WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (nama_madrasah LIKE :q OR nama_kepala LIKE :q OR nomor_wa LIKE :q OR catatan LIKE :q)';
        $params[':q'] = '%' . $search . '%';
    }

    if (!empty($filters['jenjang'])) {
        $jenjangValues = jenjangPemesananFilterValues($filters['jenjang']);
        $placeholders = [];
        foreach ($jenjangValues as $i => $value) {
            $key = ':jenjang_' . $i;
            $placeholders[] = $key;
            $params[$key] = $value;
        }
        $sql .= ' AND jenjang IN (' . implode(', ', $placeholders) . ')';
    }

    if (!empty($filters['jenis_layanan'])) {
        $sql .= ' AND jenis_layanan = :jenis_layanan';
        $params[':jenis_layanan'] = $filters['jenis_layanan'];
    }

    $sql .= ' ORDER BY created_at DESC, id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getPemesananById(int $id): ?array
{
    $pdo = getDb();
    $table = pemesananTableName();
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function deletePemesanan(int $id): bool
{
    $pdo = getDb();
    $table = pemesananTableName();
    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
    $stmt->execute([':id' => $id]);

    return $stmt->rowCount() > 0;
}

function getPemesananDashboardStats(array $rows): array
{
    $catalog = pemesananLayananCatalog();
    $stats = [
        'total' => count($rows),
        'total_jumlah' => 0,
        'jenjang' => [],
        'jenis' => [],
    ];

    foreach (array_keys($catalog) as $key) {
        $stats['jenis'][$key] = 0;
    }

    foreach ($rows as $row) {
        $jenis = $row['jenis_layanan'] ?? 'mopdik';
        $stats['jenis'][$jenis] = ($stats['jenis'][$jenis] ?? 0) + 1;

        if (in_array($catalog[$jenis]['tipe'] ?? '', ['jumlah', 'kenuan'], true)) {
            $stats['total_jumlah'] += getJumlahPemesanan($row);
        }

        $jenjang = normalizeJenjangPemesanan($row['jenjang'] ?? '') ?: 'Lainnya';
        $stats['jenjang'][$jenjang] = ($stats['jenjang'][$jenjang] ?? 0) + 1;
    }

    arsort($stats['jenjang']);

    return $stats;
}

function labelJenisLayanan(string $jenis): string
{
    return getPemesananLayanan($jenis)['label'] ?? $jenis;
}

function formatRingkasanPemesanan(array $row): string
{
    $jenis = $row['jenis_layanan'] ?? 'mopdik';
    $layanan = getPemesananLayanan($jenis);

    if (($layanan['tipe'] ?? '') === 'batik') {
        $parts = [];
        foreach (parseJenisBatikSelected($row['jenis_batik'] ?? '') as $jenis) {
            $parts[] = $jenis;
        }
        if (!empty($row['satuan_jenis_1']) && !empty($row['satuan_jumlah_1'])) {
            $parts[] = $row['satuan_jumlah_1'] . ' ' . $row['satuan_jenis_1'];
        }
        if (!empty($row['satuan_jenis_2']) && !empty($row['satuan_jumlah_2'])) {
            $parts[] = $row['satuan_jumlah_2'] . ' ' . $row['satuan_jenis_2'];
        }
        $sizes = [];
        foreach (['S' => 'ukuran_s', 'M' => 'ukuran_m', 'L' => 'ukuran_l', 'XL' => 'ukuran_xl', 'XXL' => 'ukuran_xxl'] as $label => $col) {
            if (!empty($row[$col])) {
                $sizes[] = $label . ':' . (int) $row[$col];
            }
        }
        if ($sizes !== []) {
            $parts[] = implode(', ', $sizes);
        }

        return $parts !== [] ? implode(' · ', $parts) : '-';
    }

    if (($layanan['tipe'] ?? '') === 'kenuan') {
        $parts = [];
        foreach (bukuKenuanKelasFields() as $key => $label) {
            $qty = (int) ($row[$key] ?? 0);
            if ($qty > 0) {
                $parts[] = $label . ': ' . $qty;
            }
        }

        return $parts !== [] ? implode(', ', $parts) : '-';
    }

    $jumlah = getJumlahPemesanan($row);

    return $jumlah > 0 ? $jumlah . ' ' . strtolower(str_replace('Jumlah ', '', $layanan['jumlah_label'] ?? 'item')) : '-';
}

/** @deprecated */
function formatPaketPemesanan(array $row): string
{
    return formatRingkasanPemesanan($row);
}

function exportPemesananCsv(array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pemesanan_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    $kenuanLabels = bukuKenuanKelasFields();
    fputcsv($output, array_merge([
        'ID', 'Tanggal', 'Jenis Layanan', 'Nama Madrasah/Sekolah', 'Nama Kepala', 'Nomor WA', 'Jenjang',
        'Jumlah Total', 'Jenis Batik', 'Satuan 1', 'Jml Satuan 1', 'Satuan 2', 'Jml Satuan 2',
        'S', 'M', 'L', 'XL', 'XXL',
    ], array_values($kenuanLabels), ['Catatan']));

    foreach ($rows as $row) {
        fputcsv($output, array_merge([
            $row['id'] ?? '',
            $row['created_at'] ?? '',
            labelJenisLayanan($row['jenis_layanan'] ?? 'mopdik'),
            $row['nama_madrasah'] ?? '',
            $row['nama_kepala'] ?? '',
            $row['nomor_wa'] ?? '',
            normalizeJenjangPemesanan($row['jenjang'] ?? ''),
            getJumlahPemesanan($row),
            $row['jenis_batik'] ?? '',
            $row['satuan_jenis_1'] ?? '',
            $row['satuan_jumlah_1'] ?? '',
            $row['satuan_jenis_2'] ?? '',
            $row['satuan_jumlah_2'] ?? '',
            $row['ukuran_s'] ?? 0,
            $row['ukuran_m'] ?? 0,
            $row['ukuran_l'] ?? 0,
            $row['ukuran_xl'] ?? 0,
            $row['ukuran_xxl'] ?? 0,
        ], array_map(static fn (string $key): int => (int) ($row[$key] ?? 0), array_keys($kenuanLabels)), [
            $row['catatan'] ?? '',
        ]));
    }

    fclose($output);
    exit;
}

function isPemesananAdminLoggedIn(): bool
{
    return !empty($_SESSION['pemesanan_buku_admin']);
}

