<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function ensureJalanSehatTables(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo = getDb();

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `jalan_sehat_pendaftaran` (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `nama_madrasah` varchar(200) NOT NULL,
          `alamat` text NOT NULL,
          `kode_kecamatan` varchar(20) DEFAULT NULL,
          `nama_kecamatan` varchar(150) DEFAULT NULL,
          `kode_kelurahan` varchar(20) DEFAULT NULL,
          `nama_kelurahan` varchar(150) DEFAULT NULL,
          `alamat_detail` text DEFAULT NULL,
          `nama_kepala` varchar(150) NOT NULL,
          `nomor_wa` varchar(30) DEFAULT NULL,
          `paket_id` int(10) UNSIGNED DEFAULT NULL,
          `paket_nama` varchar(150) DEFAULT NULL,
          `jumlah_kupon` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `jumlah_kaos` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `kaos_s` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `kaos_m` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `kaos_l` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `kaos_xl` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `kaos_xxl` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `kaos_xxxl` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `kaos_xxxxl` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `catatan` text DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_js_paket` (`paket_id`),
          KEY `idx_js_kecamatan` (`nama_kecamatan`),
          KEY `idx_js_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `jalan_sehat_paket` (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `nama` varchar(150) NOT NULL,
          `jumlah_kupon` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `jumlah_kaos` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `keterangan` varchar(255) DEFAULT NULL,
          `aktif` tinyint(1) NOT NULL DEFAULT 1,
          `urutan` int(10) UNSIGNED NOT NULL DEFAULT 0,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `jalan_sehat_pengaturan` (
          `kunci` varchar(50) NOT NULL,
          `nilai` text DEFAULT NULL,
          PRIMARY KEY (`kunci`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $done = true;
}

/** @return list<string> */
function jalanSehatPendaftaranColumnNames(?PDO $pdo = null, bool $refresh = false): array
{
    static $columns = null;
    if ($refresh) {
        $columns = null;
    }
    if ($columns !== null) {
        return $columns;
    }

    $pdo ??= getDb();
    ensureJalanSehatTables();
    $columns = array_column(
        $pdo->query('SHOW COLUMNS FROM jalan_sehat_pendaftaran')->fetchAll(),
        'Field'
    );

    return $columns;
}

function ensureJalanSehatSchemaMigrations(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    ensureJalanSehatTables();
    $pdo = getDb();

    try {
        $existingColumns = array_column(
            $pdo->query('SHOW COLUMNS FROM jalan_sehat_pendaftaran')->fetchAll(),
            'Field'
        );

    $wilayahColumns = [
        'kode_kecamatan' => 'varchar(20) DEFAULT NULL',
        'nama_kecamatan' => 'varchar(150) DEFAULT NULL',
        'kode_kelurahan' => 'varchar(20) DEFAULT NULL',
        'nama_kelurahan' => 'varchar(150) DEFAULT NULL',
        'alamat_detail' => 'text DEFAULT NULL',
    ];
    $previousColumn = 'alamat';
    foreach ($wilayahColumns as $column => $definition) {
        jalanSehatAddColumnIfMissing($pdo, $existingColumns, $column, $definition, $previousColumn);
        if ($column === 'nama_kecamatan' && in_array('nama_kecamatan', $existingColumns, true)) {
            try {
                $pdo->exec('ALTER TABLE jalan_sehat_pendaftaran ADD KEY `idx_js_kecamatan` (`nama_kecamatan`)');
            } catch (PDOException) {
                // indeks sudah ada atau tidak bisa ditambah
            }
        }
        $previousColumn = $column;
    }

    $phoneColumns = [
        'nomor_wa_norm' => 'varchar(20) DEFAULT NULL',
        'nomor_hp_kepala' => 'varchar(30) DEFAULT NULL',
        'nomor_hp_kepala_norm' => 'varchar(20) DEFAULT NULL',
    ];
    $previousColumn = 'nomor_wa';
    foreach ($phoneColumns as $column => $definition) {
        jalanSehatAddColumnIfMissing($pdo, $existingColumns, $column, $definition, $previousColumn);
        $previousColumn = $column;
    }

    $previousColumn = 'jumlah_kaos';
    foreach (jalanSehatUkuranKaos() as $column) {
        jalanSehatAddColumnIfMissing(
            $pdo,
            $existingColumns,
            $column,
            'int(10) UNSIGNED NOT NULL DEFAULT 0',
            $previousColumn
        );
        $previousColumn = $column;
    }

    $afterKonveksi = 'jumlah_kaos';
    foreach (array_reverse(array_values(jalanSehatUkuranKaos())) as $sizeColumn) {
        if (in_array($sizeColumn, $existingColumns, true)) {
            $afterKonveksi = $sizeColumn;
            break;
        }
    }
    jalanSehatAddColumnIfMissing(
        $pdo,
        $existingColumns,
        'kaos_konveksi_selesai',
        'tinyint(1) NOT NULL DEFAULT 0',
        $afterKonveksi
    );
    jalanSehatAddColumnIfMissing(
        $pdo,
        $existingColumns,
        'pembayaran_lunas',
        'tinyint(1) NOT NULL DEFAULT 0',
        in_array('kaos_konveksi_selesai', $existingColumns, true) ? 'kaos_konveksi_selesai' : $afterKonveksi
    );
    $afterBayar = in_array('pembayaran_lunas', $existingColumns, true)
        ? 'pembayaran_lunas'
        : (in_array('kaos_konveksi_selesai', $existingColumns, true) ? 'kaos_konveksi_selesai' : $afterKonveksi);
    jalanSehatAddColumnIfMissing($pdo, $existingColumns, 'kaos_diambil_pada', 'datetime DEFAULT NULL', $afterBayar);
    jalanSehatAddColumnIfMissing($pdo, $existingColumns, 'kaos_diambil_nama', 'varchar(150) DEFAULT NULL', 'kaos_diambil_pada');
    jalanSehatAddColumnIfMissing($pdo, $existingColumns, 'kaos_diambil_jabatan', 'varchar(120) DEFAULT NULL', 'kaos_diambil_nama');
    jalanSehatAddColumnIfMissing($pdo, $existingColumns, 'kaos_diambil_hp', 'varchar(30) DEFAULT NULL', 'kaos_diambil_jabatan');

        jalanSehatBackfillPhoneNormColumns($pdo);
    } catch (PDOException) {
        // Migrasi otomatis boleh gagal di hosting tanpa hak ALTER; operasi baca tetap jalan
    }

    $done = true;
    jalanSehatPendaftaranColumnNames($pdo, true);
}

function ensureJalanSehatSchema(): void
{
    ensureJalanSehatTables();
    ensureJalanSehatSchemaMigrations();
}

function jalanSehatAddColumnIfMissing(
    PDO $pdo,
    array &$existingColumns,
    string $column,
    string $definition,
    string $afterColumn
): void {
    if (in_array($column, $existingColumns, true)) {
        return;
    }

    try {
        $pdo->exec(
            "ALTER TABLE jalan_sehat_pendaftaran ADD COLUMN `{$column}` {$definition} AFTER `{$afterColumn}`"
        );
        $existingColumns[] = $column;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Duplicate column') || (string) $e->getCode() === '42S21') {
            $existingColumns[] = $column;

            return;
        }
        // Kolom tidak bisa ditambah (mis. AFTER invalid / hak ALTER) — lewati
    }
}

/**
 * @return array<string, array{label: string, columns: array<string, string>}>
 */
function jalanSehatUkuranKaosByJenis(): array
{
    return [
        'pendek' => [
            'label' => 'Kaos Lengan Pendek',
            'columns' => [
                'S' => 'kaos_s',
                'M' => 'kaos_m',
                'L' => 'kaos_l',
                'XL' => 'kaos_xl',
                'XXL' => 'kaos_xxl',
                'XXXL' => 'kaos_xxxl',
                'XXXXL' => 'kaos_xxxxl',
            ],
        ],
        'panjang' => [
            'label' => 'Kaos Lengan Panjang',
            'columns' => [
                'S' => 'kaos_panjang_s',
                'M' => 'kaos_panjang_m',
                'L' => 'kaos_panjang_l',
                'XL' => 'kaos_panjang_xl',
                'XXL' => 'kaos_panjang_xxl',
                'XXXL' => 'kaos_panjang_xxxl',
                'XXXXL' => 'kaos_panjang_xxxxl',
            ],
        ],
    ];
}

/** @return array<string, array{lebar: int, tinggi: int}> */
function jalanSehatPanduanUkuranKaos(): array
{
    return [
        'S' => ['lebar' => 43, 'tinggi' => 68],
        'M' => ['lebar' => 47, 'tinggi' => 70],
        'L' => ['lebar' => 51, 'tinggi' => 72],
        'XL' => ['lebar' => 55, 'tinggi' => 74],
        'XXL' => ['lebar' => 59, 'tinggi' => 76],
        'XXXL' => ['lebar' => 63, 'tinggi' => 78],
        'XXXXL' => ['lebar' => 67, 'tinggi' => 80],
    ];
}

/**
 * @return array<string, string> label tampilan => nama kolom
 */
function jalanSehatUkuranKaos(): array
{
    $flat = [];
    foreach (jalanSehatUkuranKaosByJenis() as $jenis => $info) {
        $singkat = $jenis === 'pendek' ? 'Pendek' : 'Panjang';
        foreach ($info['columns'] as $label => $column) {
            $flat[$label . ' (' . $singkat . ')'] = $column;
        }
    }

    return $flat;
}

function formatRincianUkuranKaos(array $row): string
{
    $parts = [];
    foreach (jalanSehatUkuranKaosByJenis() as $jenis => $info) {
        $singkat = $jenis === 'pendek' ? 'Pendek' : 'Panjang';
        foreach ($info['columns'] as $label => $column) {
            $qty = (int) ($row[$column] ?? 0);
            if ($qty > 0) {
                $parts[] = $singkat . ' ' . $label . ': ' . $qty;
            }
        }
    }

    return implode(', ', $parts);
}

function jalanSehatPengaturanDefaults(): array
{
    return [
        'pendaftaran_dibuka' => '1',
        'izinkan_jumlah_bebas' => '1',
        'harga_kupon' => '5000',
        'harga_kaos' => '0',
        'harga_kaos_pendek' => '80000',
        'harga_kaos_panjang' => '90000',
        'info_surat_edaran' => '',
        'monitor_kaos_aktif' => '0',
        'kupon_mengikuti_kaos' => '1',
        'kupon_per_kaos' => '2',
    ];
}

/** Tarif resmi SE Revisi 0629/PC.1/LPM/E.21/X/2026 (Jalan Santai HSN – Harlah LPMNU 97). */
function jalanSehatTarifSuratEdaranDefaults(): array
{
    return [
        'harga_kupon' => '5000',
        'harga_kaos_pendek' => '80000',
        'harga_kaos_panjang' => '90000',
    ];
}

function ensureJalanSehatTarifSuratEdaran(): void
{
    ensureJalanSehatTables();
    $pdo = getDb();
    $insert = $pdo->prepare(
        'INSERT INTO jalan_sehat_pengaturan (kunci, nilai) VALUES (:kunci, :nilai)
         ON DUPLICATE KEY UPDATE nilai = IF(nilai IS NULL OR TRIM(nilai) = \'\' OR nilai = \'0\', VALUES(nilai), nilai)'
    );
    foreach (jalanSehatTarifSuratEdaranDefaults() as $kunci => $nilai) {
        $insert->execute([':kunci' => $kunci, ':nilai' => $nilai]);
    }
}

function applyJalanSehatTarifSuratEdaran(bool $paksa = false): void
{
    ensureJalanSehatTables();
    if (!$paksa) {
        ensureJalanSehatTarifSuratEdaran();

        return;
    }
    $pdo = getDb();
    $stmt = $pdo->prepare(
        'INSERT INTO jalan_sehat_pengaturan (kunci, nilai) VALUES (:kunci, :nilai)
         ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)'
    );
    foreach (jalanSehatTarifSuratEdaranDefaults() as $kunci => $nilai) {
        $stmt->execute([':kunci' => $kunci, ':nilai' => $nilai]);
    }
}

function getJalanSehatPengaturan(): array
{
    ensureJalanSehatTables();
    ensureJalanSehatTarifSuratEdaran();
    $settings = jalanSehatPengaturanDefaults();
    $rows = getDb()->query('SELECT kunci, nilai FROM jalan_sehat_pengaturan')->fetchAll();
    foreach ($rows as $row) {
        $kunci = (string) $row['kunci'];
        if (array_key_exists($kunci, $settings)) {
            $settings[$kunci] = (string) ($row['nilai'] ?? '');
        } else {
            $settings[$kunci] = (string) ($row['nilai'] ?? '');
        }
    }

    return $settings;
}

function saveJalanSehatPengaturan(array $input): void
{
    ensureJalanSehatSchema();
    $values = [
        'pendaftaran_dibuka' => !empty($input['pendaftaran_dibuka']) ? '1' : '0',
        'izinkan_jumlah_bebas' => !empty($input['izinkan_jumlah_bebas']) ? '1' : '0',
        'harga_kupon' => (string) max(0, (int) preg_replace('/\D/', '', (string) ($input['harga_kupon'] ?? '0'))),
        'harga_kaos' => (string) max(0, (int) preg_replace('/\D/', '', (string) ($input['harga_kaos'] ?? '0'))),
        'harga_kaos_pendek' => (string) max(0, (int) preg_replace('/\D/', '', (string) ($input['harga_kaos_pendek'] ?? '0'))),
        'harga_kaos_panjang' => (string) max(0, (int) preg_replace('/\D/', '', (string) ($input['harga_kaos_panjang'] ?? '0'))),
        'info_surat_edaran' => trim((string) ($input['info_surat_edaran'] ?? '')),
        'monitor_kaos_aktif' => !empty($input['monitor_kaos_aktif']) ? '1' : '0',
        'kupon_mengikuti_kaos' => !empty($input['kupon_mengikuti_kaos']) ? '1' : '0',
        'kupon_per_kaos' => (string) max(1, (int) ($input['kupon_per_kaos'] ?? 2)),
    ];

    $stmt = getDb()->prepare(
        'INSERT INTO jalan_sehat_pengaturan (kunci, nilai) VALUES (:kunci, :nilai)
         ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)'
    );
    foreach ($values as $kunci => $nilai) {
        $stmt->execute([':kunci' => $kunci, ':nilai' => $nilai]);
    }
}

function loadJalanSehatPaket(bool $hanyaAktif = false): array
{
    ensureJalanSehatTables();
    $sql = 'SELECT * FROM jalan_sehat_paket';
    if ($hanyaAktif) {
        $sql .= ' WHERE aktif = 1';
    }
    $sql .= ' ORDER BY urutan ASC, id ASC';

    return getDb()->query($sql)->fetchAll();
}

function getJalanSehatPaketById(int $id): ?array
{
    ensureJalanSehatTables();
    $stmt = getDb()->prepare('SELECT * FROM jalan_sehat_paket WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function validateJalanSehatPaket(array $input): array
{
    $errors = [];
    $data = [
        'nama' => trim((string) ($input['nama'] ?? '')),
        'jumlah_kupon' => max(0, (int) ($input['jumlah_kupon'] ?? 0)),
        'jumlah_kaos' => max(0, (int) ($input['jumlah_kaos'] ?? 0)),
        'keterangan' => trim((string) ($input['keterangan'] ?? '')),
        'urutan' => max(0, (int) ($input['urutan'] ?? 0)),
        'aktif' => !empty($input['aktif']) ? 1 : 0,
    ];

    if ($data['nama'] === '') {
        $errors[] = 'Nama paket wajib diisi.';
    }
    if ($data['jumlah_kupon'] === 0 && $data['jumlah_kaos'] === 0) {
        $errors[] = 'Isi jumlah kupon atau kaos pada paket (minimal salah satu).';
    }

    return ['errors' => $errors, 'data' => $data];
}

function saveJalanSehatPaket(array $data, ?int $id = null): bool
{
    ensureJalanSehatSchema();
    $params = [
        ':nama' => $data['nama'],
        ':jumlah_kupon' => $data['jumlah_kupon'],
        ':jumlah_kaos' => $data['jumlah_kaos'],
        ':keterangan' => $data['keterangan'] !== '' ? $data['keterangan'] : null,
        ':urutan' => $data['urutan'],
        ':aktif' => $data['aktif'],
    ];

    if ($id !== null && $id > 0) {
        $params[':id'] = $id;
        $stmt = getDb()->prepare(
            'UPDATE jalan_sehat_paket SET nama = :nama, jumlah_kupon = :jumlah_kupon, jumlah_kaos = :jumlah_kaos,
                keterangan = :keterangan, urutan = :urutan, aktif = :aktif
             WHERE id = :id'
        );

        return $stmt->execute($params);
    }

    $stmt = getDb()->prepare(
        'INSERT INTO jalan_sehat_paket (nama, jumlah_kupon, jumlah_kaos, keterangan, urutan, aktif)
         VALUES (:nama, :jumlah_kupon, :jumlah_kaos, :keterangan, :urutan, :aktif)'
    );

    return $stmt->execute($params);
}

function deleteJalanSehatPaket(int $id): bool
{
    ensureJalanSehatSchema();
    $stmt = getDb()->prepare('DELETE FROM jalan_sehat_paket WHERE id = :id');
    $stmt->execute([':id' => $id]);

    return $stmt->rowCount() > 0;
}

function toggleJalanSehatPaket(int $id): void
{
    ensureJalanSehatSchema();
    getDb()->prepare('UPDATE jalan_sehat_paket SET aktif = 1 - aktif WHERE id = :id')->execute([':id' => $id]);
}

function formatRupiahJalanSehat(int $nominal): string
{
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

function jalanSehatHargaKaosPendek(array $settings): int
{
    $h = (int) ($settings['harga_kaos_pendek'] ?? 0);

    return $h > 0 ? $h : (int) ($settings['harga_kaos'] ?? 0);
}

function jalanSehatHargaKaosPanjang(array $settings): int
{
    $h = (int) ($settings['harga_kaos_panjang'] ?? 0);

    return $h > 0 ? $h : (int) ($settings['harga_kaos'] ?? 0);
}

/**
 * @return array{pendek: int, panjang: int, total: int}
 */
function jalanSehatTotalKaosByJenis(array $row): array
{
    $pendek = 0;
    $panjang = 0;
    foreach (jalanSehatUkuranKaosByJenis()['pendek']['columns'] as $column) {
        $pendek += (int) ($row[$column] ?? 0);
    }
    foreach (jalanSehatUkuranKaosByJenis()['panjang']['columns'] as $column) {
        $panjang += (int) ($row[$column] ?? 0);
    }

    return [
        'pendek' => $pendek,
        'panjang' => $panjang,
        'total' => $pendek + $panjang,
    ];
}

/** Kupon di luar bonus kaos (2 kupon/kaos sudah termasuk harga kaos). */
function jalanSehatJumlahKuponDitagih(int $jumlahKupon, int $totalKaos, array $settings): int
{
    if ($totalKaos <= 0) {
        return max(0, $jumlahKupon);
    }

    return max(0, $jumlahKupon - jalanSehatHitungKuponDariKaos($totalKaos, $settings));
}

function hitungBiayaJalanSehatFromRow(array $row, array $settings): int
{
    return jalanSehatRincianBiayaFromRow($row, $settings)['total'];
}

function hitungBiayaJalanSehat(int $kupon, int $kaos, array $settings): int
{
    $row = ['jumlah_kupon' => $kupon, 'jumlah_kaos' => $kaos];
    foreach (jalanSehatUkuranKaosByJenis()['pendek']['columns'] as $column) {
        $row[$column] = 0;
    }
    foreach (jalanSehatUkuranKaosByJenis()['panjang']['columns'] as $column) {
        $row[$column] = 0;
    }
    if ($kaos > 0) {
        $firstPendek = array_values(jalanSehatUkuranKaosByJenis()['pendek']['columns'])[0];
        $row[$firstPendek] = $kaos;
    }

    return hitungBiayaJalanSehatFromRow($row, $settings);
}

/**
 * @return array{
 *   harga_kupon: int,
 *   harga_kaos: int,
 *   harga_kaos_pendek: int,
 *   harga_kaos_panjang: int,
 *   qty_pendek: int,
 *   qty_panjang: int,
 *   qty_kupon_bayar: int,
 *   bonus_kupon_kaos: int,
 *   subtotal_kupon: int,
 *   subtotal_kaos_pendek: int,
 *   subtotal_kaos_panjang: int,
 *   subtotal_kaos: int,
 *   total: int
 * }
 */
function jalanSehatRincianBiayaFromRow(array $row, array $settings): array
{
    $hargaKupon = (int) ($settings['harga_kupon'] ?? 0);
    $hargaPendek = jalanSehatHargaKaosPendek($settings);
    $hargaPanjang = jalanSehatHargaKaosPanjang($settings);
    $qty = jalanSehatTotalKaosByJenis($row);
    $jumlahKupon = (int) ($row['jumlah_kupon'] ?? 0);
    $bonusKupon = jalanSehatHitungKuponDariKaos($qty['total'], $settings);
    $kuponBayar = jalanSehatJumlahKuponDitagih($jumlahKupon, $qty['total'], $settings);
    $subKupon = $kuponBayar * $hargaKupon;
    $subPendek = $qty['pendek'] * $hargaPendek;
    $subPanjang = $qty['panjang'] * $hargaPanjang;
    $subKaos = $subPendek + $subPanjang;

    return [
        'harga_kupon' => $hargaKupon,
        'harga_kaos' => $hargaPendek > 0 ? $hargaPendek : $hargaPanjang,
        'harga_kaos_pendek' => $hargaPendek,
        'harga_kaos_panjang' => $hargaPanjang,
        'qty_pendek' => $qty['pendek'],
        'qty_panjang' => $qty['panjang'],
        'qty_kupon_bayar' => $kuponBayar,
        'bonus_kupon_kaos' => $bonusKupon,
        'subtotal_kupon' => $subKupon,
        'subtotal_kaos_pendek' => $subPendek,
        'subtotal_kaos_panjang' => $subPanjang,
        'subtotal_kaos' => $subKaos,
        'total' => $subKupon + $subKaos,
    ];
}

function jalanSehatRincianBiaya(int $kupon, int $kaos, array $settings): array
{
    return jalanSehatRincianBiayaFromRow(
        ['jumlah_kupon' => $kupon, 'jumlah_kaos' => $kaos] + jalanSehatSyntheticRowKaos($kaos),
        $settings
    );
}

/** @return array<string, int> */
function jalanSehatSyntheticRowKaos(int $kaos): array
{
    $row = [];
    foreach (jalanSehatUkuranKaosByJenis()['pendek']['columns'] as $column) {
        $row[$column] = 0;
    }
    foreach (jalanSehatUkuranKaosByJenis()['panjang']['columns'] as $column) {
        $row[$column] = 0;
    }
    if ($kaos > 0) {
        $firstPendek = array_values(jalanSehatUkuranKaosByJenis()['pendek']['columns'])[0];
        $row[$firstPendek] = $kaos;
    }

    return $row;
}

function jalanSehatAdaHarga(array $settings): bool
{
    return (int) ($settings['harga_kupon'] ?? 0) > 0
        || jalanSehatHargaKaosPendek($settings) > 0
        || jalanSehatHargaKaosPanjang($settings) > 0
        || (int) ($settings['harga_kaos'] ?? 0) > 0;
}

function jalanSehatKuponMengikutiKaos(array $settings): bool
{
    return ($settings['kupon_mengikuti_kaos'] ?? '1') === '1';
}

/** Jumlah kupon door prize otomatis per 1 kaos (sesuai surat edaran, default 2). */
function jalanSehatKuponPerKaos(array $settings): int
{
    $n = (int) ($settings['kupon_per_kaos'] ?? 2);

    return $n >= 1 ? $n : 2;
}

function jalanSehatHitungKuponDariKaos(int $kaos, array $settings): int
{
    if ($kaos <= 0) {
        return 0;
    }

    return $kaos * jalanSehatKuponPerKaos($settings);
}

/** Isian jumlah sendiri: kupon door prize mengikuti kaos (sesuai SE, hindari double input). */
function jalanSehatApplyKuponMengikutiKaos(array &$data, array $settings, bool $fromAdmin = false): void
{
    if ($fromAdmin) {
        return;
    }
    if (!jalanSehatKuponMengikutiKaos($settings)) {
        return;
    }
    if ($data['paket_id'] !== null) {
        return;
    }
    $kaos = (int) ($data['jumlah_kaos'] ?? 0);
    if ($kaos > 0) {
        $data['jumlah_kupon'] = jalanSehatHitungKuponDariKaos($kaos, $settings);
    }
}

function setJalanSehatPembayaranLunas(int $id, bool $lunas): array
{
    ensureJalanSehatSchemaMigrations();
    if (!in_array('pembayaran_lunas', jalanSehatPendaftaranColumnNames(), true)) {
        return ['ok' => false, 'message' => 'Kolom pembayaran belum tersedia di database.'];
    }
    if (getJalanSehatPendaftaranById($id) === null) {
        return ['ok' => false, 'message' => 'Pendaftaran tidak ditemukan.'];
    }
    $stmt = getDb()->prepare('UPDATE jalan_sehat_pendaftaran SET pembayaran_lunas = :v WHERE id = :id');
    $stmt->execute([':v' => $lunas ? 1 : 0, ':id' => $id]);

    return ['ok' => true, 'lunas' => $lunas ? 1 : 0];
}

function formatPaketJalanSehat(int $kupon, int $kaos): string
{
    $parts = [];
    if ($kupon > 0) {
        $parts[] = $kupon . ' kupon door prize';
    }
    if ($kaos > 0) {
        $parts[] = $kaos . ' kaos';
    }

    return $parts !== [] ? implode(' + ', $parts) : '-';
}

function jalanSehatFormDefaults(): array
{
    return [
        'nama_madrasah' => '',
        'kode_kecamatan' => '',
        'nama_kecamatan' => '',
        'kode_kelurahan' => '',
        'nama_kelurahan' => '',
        'alamat_detail' => '',
        'nama_kepala' => '',
        'nomor_wa' => '',
        'nomor_hp_kepala' => '',
        'pilihan_paket' => '',
        'jumlah_kupon' => '0',
        'jumlah_kaos' => '0',
        'catatan' => '',
    ] + array_fill_keys(array_values(jalanSehatUkuranKaos()), 0);
}

function jalanSehatBackfillPhoneNormColumns(PDO $pdo): void
{
    static $backfilled = false;
    if ($backfilled) {
        return;
    }
    $backfilled = true;

    $columnNames = array_column(
        $pdo->query('SHOW COLUMNS FROM jalan_sehat_pendaftaran')->fetchAll(),
        'Field'
    );
    if (!in_array('nomor_wa_norm', $columnNames, true)) {
        return;
    }

    $rows = $pdo->query(
        'SELECT id, nomor_wa, nomor_wa_norm, nomor_hp_kepala, nomor_hp_kepala_norm FROM jalan_sehat_pendaftaran'
    )->fetchAll();
    $stmt = $pdo->prepare(
        'UPDATE jalan_sehat_pendaftaran SET nomor_wa_norm = :wa_norm, nomor_hp_kepala = :hp, nomor_hp_kepala_norm = :hp_norm WHERE id = :id'
    );
    foreach ($rows as $row) {
        $waNorm = normalizeNomorWa((string) ($row['nomor_wa'] ?? ''));
        $hp = trim((string) ($row['nomor_hp_kepala'] ?? ''));
        $hpNorm = $hp !== '' ? normalizeNomorWa($hp) : null;
        $currentWaNorm = (string) ($row['nomor_wa_norm'] ?? '');
        $currentHpNorm = (string) ($row['nomor_hp_kepala_norm'] ?? '');
        if ($currentWaNorm === $waNorm && ($hpNorm ?? '') === $currentHpNorm) {
            continue;
        }
        $stmt->execute([
            ':wa_norm' => $waNorm !== '' ? $waNorm : null,
            ':hp' => $hpNorm !== null && $hpNorm !== '' ? $hpNorm : ($hp !== '' ? $hp : null),
            ':hp_norm' => $hpNorm !== null && $hpNorm !== '' ? $hpNorm : null,
            ':id' => (int) $row['id'],
        ]);
    }
}

function jalanSehatNamaMadrasahMatch(string $a, string $b): bool
{
    $normalize = static function (string $value): string {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    };

    $na = $normalize($a);
    $nb = $normalize($b);
    if ($na === '' || $nb === '') {
        return false;
    }
    if ($na === $nb) {
        return true;
    }

    return str_contains($na, $nb) || str_contains($nb, $na);
}

function jalanSehatNormalizeNamaMadrasah(string $nama): string
{
    $value = mb_strtolower(trim($nama));
    $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value) ?? $value;
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

    return trim($value);
}

function getJalanSehatPendaftaranByNamaMadrasah(string $nama, ?int $excludeId = null): ?array
{
    $target = jalanSehatNormalizeNamaMadrasah($nama);
    if ($target === '') {
        return null;
    }

    ensureJalanSehatTables();
    $sql = 'SELECT * FROM jalan_sehat_pendaftaran';
    $params = [];
    if ($excludeId !== null && $excludeId > 0) {
        $sql .= ' WHERE id != :exclude_id';
        $params[':exclude_id'] = $excludeId;
    }
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $row) {
        if (jalanSehatNormalizeNamaMadrasah((string) $row['nama_madrasah']) === $target) {
            return $row;
        }
    }

    return null;
}

function jalanSehatRowHasNomorHp(array $row, string $norm): bool
{
    if ($norm === '') {
        return false;
    }
    if (normalizeNomorWa((string) ($row['nomor_wa'] ?? '')) === $norm) {
        return true;
    }
    if (normalizeNomorWa((string) ($row['nomor_hp_kepala'] ?? '')) === $norm) {
        return true;
    }

    return false;
}

function getJalanSehatPendaftaranByNomorHp(string $nomor, ?int $excludeId = null): ?array
{
    $norm = normalizeNomorWa($nomor);
    if ($norm === '') {
        return null;
    }

    ensureJalanSehatTables();
    $sql = 'SELECT * FROM jalan_sehat_pendaftaran';
    $params = [];
    if ($excludeId !== null && $excludeId > 0) {
        $sql .= ' WHERE id != :exclude_id';
        $params[':exclude_id'] = $excludeId;
    }
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $row) {
        if (jalanSehatRowHasNomorHp($row, $norm)) {
            return $row;
        }
    }

    return null;
}

function jalanSehatDuplicateBlockMessage(array $existing, bool $forAdmin, string $jenis): string
{
    $nama = (string) ($existing['nama_madrasah'] ?? '');
    $label = $jenis === 'nama'
        ? 'Nama madrasah sudah terdaftar (' . $nama . ').'
        : 'Nomor HP sudah dipakai pada pendaftaran madrasah "' . $nama . '".';

    if ($forAdmin) {
        return $label . ' Tidak bisa menambah duplikat. Silakan ubah data yang ada melalui menu Edit (ID #'
            . (int) ($existing['id'] ?? 0) . ').';
    }

    return $label . ' Jika ini madrasah Anda, jangan mendaftar ulang — perbarui data di halaman ubah pendaftaran: '
        . absoluteUrl('jalansehat/ubah') . ' (masukkan nomor HP/WA atau HP kepala yang sama).';
}

/**
 * @return int[] ID pendaftaran yang boleh diedit dengan nomor HP ini (WA form, HP kepala, atau HP kepsek di pengkinian data).
 */
function findJalanSehatPendaftaranIdsByEditPhone(string $nomor): array
{
    ensureJalanSehatSchema();
    $norm = normalizeNomorWa($nomor);
    if ($norm === '') {
        return [];
    }

    $pdo = getDb();
    $ids = [];

    try {
        $stmt = $pdo->prepare(
            'SELECT id FROM jalan_sehat_pendaftaran
             WHERE nomor_wa_norm = :norm1 OR nomor_hp_kepala_norm = :norm2'
        );
        $stmt->execute([':norm1' => $norm, ':norm2' => $norm]);
        foreach ($stmt->fetchAll() as $row) {
            $ids[] = (int) $row['id'];
        }
    } catch (PDOException) {
        // kolom norm belum ada
    }

    $fallback = $pdo->query('SELECT id, nomor_wa, nomor_hp_kepala FROM jalan_sehat_pendaftaran')->fetchAll();
    foreach ($fallback as $row) {
        if (normalizeNomorWa((string) ($row['nomor_wa'] ?? '')) === $norm
            || normalizeNomorWa((string) ($row['nomor_hp_kepala'] ?? '')) === $norm) {
            $ids[] = (int) $row['id'];
        }
    }

    if ($pdo->query("SHOW TABLES LIKE 'pengkinian_data'")->fetch()) {
        $stmt = $pdo->prepare(
            'SELECT DISTINCT nama_satuan_pendidikan FROM pengkinian_data WHERE nomor_hp_kepsek_norm = :norm'
        );
        $stmt->execute([':norm' => $norm]);
        $satuanNames = array_column($stmt->fetchAll(), 'nama_satuan_pendidikan');
        if ($satuanNames !== []) {
            $pendaftar = $pdo->query('SELECT id, nama_madrasah FROM jalan_sehat_pendaftaran')->fetchAll();
            foreach ($pendaftar as $row) {
                foreach ($satuanNames as $satuan) {
                    if (jalanSehatNamaMadrasahMatch((string) $row['nama_madrasah'], (string) $satuan)) {
                        $ids[] = (int) $row['id'];
                        break;
                    }
                }
            }
        }
    }

    $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));

    return $ids;
}

function jalanSehatRegistrationMatchesEditPhone(array $row, string $norm): bool
{
    if ($norm === '') {
        return false;
    }
    if (normalizeNomorWa((string) ($row['nomor_wa'] ?? '')) === $norm) {
        return true;
    }
    if (normalizeNomorWa((string) ($row['nomor_hp_kepala'] ?? '')) === $norm) {
        return true;
    }

    $pdo = getDb();
    if (!$pdo->query("SHOW TABLES LIKE 'pengkinian_data'")->fetch()) {
        return false;
    }
    $stmt = $pdo->prepare(
        'SELECT nama_satuan_pendidikan FROM pengkinian_data WHERE nomor_hp_kepsek_norm = :norm'
    );
    $stmt->execute([':norm' => $norm]);
    foreach ($stmt->fetchAll() as $pengkinian) {
        if (jalanSehatNamaMadrasahMatch((string) $row['nama_madrasah'], (string) $pengkinian['nama_satuan_pendidikan'])) {
            return true;
        }
    }

    return false;
}

function jalanSehatGrantEditAccess(string $nomor, array $ids): void
{
    $norm = normalizeNomorWa($nomor);
    $_SESSION['jalan_sehat_edit_phone_norm'] = $norm;
    $_SESSION['jalan_sehat_edit_allowed_ids'] = array_values(array_map('intval', $ids));
    $_SESSION['jalan_sehat_edit_granted_at'] = time();
}

function jalanSehatRevokeEditAccess(): void
{
    unset(
        $_SESSION['jalan_sehat_edit_phone_norm'],
        $_SESSION['jalan_sehat_edit_allowed_ids'],
        $_SESSION['jalan_sehat_edit_granted_at'],
        $_SESSION['jalan_sehat_editing_id']
    );
}

function jalanSehatCanEditRegistration(int $id): bool
{
    if ($id < 1) {
        return false;
    }
    $grantedAt = (int) ($_SESSION['jalan_sehat_edit_granted_at'] ?? 0);
    if ($grantedAt > 0 && (time() - $grantedAt) > 7200) {
        jalanSehatRevokeEditAccess();

        return false;
    }
    $allowed = $_SESSION['jalan_sehat_edit_allowed_ids'] ?? [];

    return is_array($allowed) && in_array($id, array_map('intval', $allowed), true);
}

function jalanSehatFormDataFromRow(array $row): array
{
    $data = jalanSehatFormDefaults();
    foreach ($data as $key => $_default) {
        if (array_key_exists($key, $row)) {
            $data[$key] = $row[$key];
        }
    }
    $data['pilihan_paket'] = !empty($row['paket_id']) ? (string) $row['paket_id'] : 'custom';
    $data['jumlah_kupon'] = (string) (int) ($row['jumlah_kupon'] ?? 0);
    $data['jumlah_kaos'] = (string) (int) ($row['jumlah_kaos'] ?? 0);

    return $data;
}

/**
 * Nilai kupon/kaos untuk pilihan paket selalu diambil dari database, bukan dari input browser.
 */
function validateJalanSehat(array $input, array $settings, array $paketAktif, ?int $updateId = null, bool $fromAdmin = false): array
{
    $errors = [];
    $duplicateRow = null;
    $isUpdate = $updateId !== null && $updateId > 0;
    $existingRow = $isUpdate ? getJalanSehatPendaftaranById($updateId) : null;
    if ($isUpdate && $existingRow === null) {
        return ['errors' => ['Data pendaftaran tidak ditemukan.'], 'data' => jalanSehatFormDefaults(), 'duplicate_row' => null];
    }

    $data = [
        'nama_madrasah' => trim((string) ($input['nama_madrasah'] ?? '')),
        'kode_kecamatan' => trim((string) ($input['kode_kecamatan'] ?? '')),
        'nama_kecamatan' => trim((string) ($input['nama_kecamatan'] ?? '')),
        'kode_kelurahan' => trim((string) ($input['kode_kelurahan'] ?? '')),
        'nama_kelurahan' => trim((string) ($input['nama_kelurahan'] ?? '')),
        'alamat_detail' => trim((string) ($input['alamat_detail'] ?? '')),
        'nama_kepala' => trim((string) ($input['nama_kepala'] ?? '')),
        'nomor_wa' => trim((string) ($input['nomor_wa'] ?? '')),
        'nomor_hp_kepala' => trim((string) ($input['nomor_hp_kepala'] ?? '')),
        'pilihan_paket' => trim((string) ($input['pilihan_paket'] ?? '')),
        'jumlah_kupon' => max(0, (int) ($input['jumlah_kupon'] ?? 0)),
        'jumlah_kaos' => max(0, (int) ($input['jumlah_kaos'] ?? 0)),
        'catatan' => trim((string) ($input['catatan'] ?? '')),
        'paket_id' => null,
        'paket_nama' => null,
        'nomor_wa_norm' => null,
        'nomor_hp_kepala_norm' => null,
    ];

    $totalUkuran = 0;
    foreach (jalanSehatUkuranKaos() as $column) {
        $data[$column] = max(0, (int) ($input[$column] ?? 0));
        $totalUkuran += $data[$column];
    }
    $data['jumlah_kaos'] = $totalUkuran;

    if ($data['nama_madrasah'] === '') {
        $errors[] = 'Nama madrasah wajib diisi.';
    }
    if ($data['kode_kecamatan'] === '' || $data['nama_kecamatan'] === '') {
        $errors[] = 'Kecamatan wajib dipilih.';
    }
    if ($data['kode_kelurahan'] === '' || $data['nama_kelurahan'] === '') {
        $errors[] = 'Desa/Kelurahan wajib dipilih.';
    }
    if ($data['alamat_detail'] === '') {
        $errors[] = 'Alamat detail (jalan, dusun, RT/RW) wajib diisi.';
    }
    $wilayah = defaultWilayahMagelang();
    $data['alamat'] = buildAlamatLembaga([
        'alamat_detail' => $data['alamat_detail'],
        'nama_kelurahan' => $data['nama_kelurahan'],
        'nama_kecamatan' => $data['nama_kecamatan'],
        'nama_kabupaten' => $wilayah['nama_kabupaten'],
        'nama_provinsi' => $wilayah['nama_provinsi'],
    ]);
    if ($data['nama_kepala'] === '') {
        $errors[] = 'Nama kepala madrasah wajib diisi.';
    }
    if ($data['nomor_wa'] !== '') {
        $normalizedWa = normalizeNomorWa($data['nomor_wa']);
        if (strlen($normalizedWa) < 9 || strlen($normalizedWa) > 15) {
            $errors[] = 'Nomor WA tidak valid. Contoh: 081234567890.';
        } else {
            $data['nomor_wa'] = $normalizedWa;
            $data['nomor_wa_norm'] = $normalizedWa;
        }
    }
    if ($data['nomor_hp_kepala'] !== '') {
        $normalizedHp = normalizeNomorWa($data['nomor_hp_kepala']);
        if (strlen($normalizedHp) < 9 || strlen($normalizedHp) > 15) {
            $errors[] = 'Nomor HP kepala madrasah tidak valid. Contoh: 081234567890.';
        } else {
            $data['nomor_hp_kepala'] = $normalizedHp;
            $data['nomor_hp_kepala_norm'] = $normalizedHp;
        }
    }

    $izinkanBebas = $settings['izinkan_jumlah_bebas'] === '1' || $paketAktif === [] || $isUpdate;
    $pilihan = $data['pilihan_paket'];

    if ($paketAktif !== [] && $pilihan === '') {
        $errors[] = 'Pilih salah satu paket pemesanan.';
    } elseif ($pilihan !== '' && $pilihan !== 'custom') {
        $paket = null;
        foreach ($paketAktif as $p) {
            if ((string) $p['id'] === $pilihan) {
                $paket = $p;
                break;
            }
        }
        if ($paket === null && $isUpdate && $existingRow !== null && (string) ($existingRow['paket_id'] ?? '') === $pilihan) {
            $data['paket_id'] = (int) $existingRow['paket_id'];
            $data['paket_nama'] = (string) ($existingRow['paket_nama'] ?? '');
            $data['jumlah_kaos'] = $totalUkuran;
        } elseif ($paket === null) {
            $errors[] = 'Paket yang dipilih tidak tersedia. Silakan pilih ulang.';
        } else {
            $data['paket_id'] = (int) $paket['id'];
            $data['paket_nama'] = (string) $paket['nama'];
            $data['jumlah_kupon'] = (int) $paket['jumlah_kupon'];
            $data['jumlah_kaos'] = (int) $paket['jumlah_kaos'];

            if ($data['jumlah_kaos'] === 0) {
                foreach (jalanSehatUkuranKaos() as $column) {
                    $data[$column] = 0;
                }
            } elseif (!$isUpdate && $totalUkuran !== $data['jumlah_kaos']) {
                $errors[] = sprintf(
                    'Rincian ukuran kaos harus berjumlah %d pcs sesuai paket (saat ini terisi %d pcs).',
                    $data['jumlah_kaos'],
                    $totalUkuran
                );
            } elseif ($isUpdate) {
                $data['jumlah_kaos'] = $totalUkuran;
            }
        }
    } elseif ($pilihan === 'custom' && !$izinkanBebas) {
        $errors[] = 'Silakan pilih salah satu paket yang tersedia.';
    }

    if ($data['paket_id'] === null && $data['jumlah_kupon'] === 0 && $data['jumlah_kaos'] === 0) {
        $errors[] = 'Isi jumlah kupon door prize atau jumlah kaos per ukuran (minimal salah satu lebih dari 0).';
    }

    jalanSehatApplyKuponMengikutiKaos($data, $settings, $fromAdmin);

    if (!$isUpdate && $errors === []) {
        $dupNama = getJalanSehatPendaftaranByNamaMadrasah($data['nama_madrasah'], null);
        if ($dupNama !== null) {
            $duplicateRow = $dupNama;
            $errors[] = jalanSehatDuplicateBlockMessage($dupNama, $fromAdmin, 'nama');
        } else {
            $phonesChecked = [];
            foreach ([$data['nomor_wa'], $data['nomor_hp_kepala']] as $phone) {
                $phone = trim((string) $phone);
                if ($phone === '') {
                    continue;
                }
                $norm = normalizeNomorWa($phone);
                if ($norm === '' || isset($phonesChecked[$norm])) {
                    continue;
                }
                $phonesChecked[$norm] = true;
                $dupPhone = getJalanSehatPendaftaranByNomorHp($phone, null);
                if ($dupPhone !== null) {
                    $duplicateRow = $dupPhone;
                    $errors[] = jalanSehatDuplicateBlockMessage($dupPhone, $fromAdmin, 'hp');
                    break;
                }
            }
        }
    }

    if ($isUpdate && $errors === []) {
        $dupNama = getJalanSehatPendaftaranByNamaMadrasah($data['nama_madrasah'], $updateId);
        if ($dupNama !== null) {
            $errors[] = 'Nama madrasah sudah dipakai pendaftaran lain (ID #' . (int) $dupNama['id'] . ').';
        }
        foreach ([$data['nomor_wa'], $data['nomor_hp_kepala']] as $phone) {
            if (trim((string) $phone) === '') {
                continue;
            }
            $dupPhone = getJalanSehatPendaftaranByNomorHp($phone, $updateId);
            if ($dupPhone !== null) {
                $errors[] = 'Nomor HP sudah dipakai pendaftaran lain (' . (string) $dupPhone['nama_madrasah'] . ').';
                break;
            }
        }
    }

    return ['errors' => $errors, 'data' => $data, 'duplicate_row' => $duplicateRow];
}

function addJalanSehatPendaftaran(array $data): int
{
    ensureJalanSehatSchema();
    [$writeColumns, $params] = jalanSehatPendaftaranWritePayload($data);
    $placeholders = array_map(static fn (string $col): string => ':' . $col, $writeColumns);
    $stmt = getDb()->prepare(
        'INSERT INTO jalan_sehat_pendaftaran (`' . implode('`, `', $writeColumns) . '`) VALUES ('
        . implode(', ', $placeholders) . ')'
    );
    $stmt->execute($params);

    return (int) getDb()->lastInsertId();
}

/**
 * @param list<string> $ukuranColumns
 * @return array<string, mixed>
 */
function jalanSehatPendaftaranParams(array $data, array $ukuranColumns): array
{
    $params = [
        ':nama_madrasah' => $data['nama_madrasah'],
        ':alamat' => $data['alamat'],
        ':kode_kecamatan' => $data['kode_kecamatan'],
        ':nama_kecamatan' => $data['nama_kecamatan'],
        ':kode_kelurahan' => $data['kode_kelurahan'],
        ':nama_kelurahan' => $data['nama_kelurahan'],
        ':alamat_detail' => $data['alamat_detail'],
        ':nama_kepala' => $data['nama_kepala'],
        ':nomor_wa' => $data['nomor_wa'] !== '' ? $data['nomor_wa'] : null,
        ':nomor_wa_norm' => !empty($data['nomor_wa_norm']) ? $data['nomor_wa_norm'] : null,
        ':nomor_hp_kepala' => $data['nomor_hp_kepala'] !== '' ? $data['nomor_hp_kepala'] : null,
        ':nomor_hp_kepala_norm' => !empty($data['nomor_hp_kepala_norm']) ? $data['nomor_hp_kepala_norm'] : null,
        ':paket_id' => $data['paket_id'],
        ':paket_nama' => $data['paket_nama'],
        ':jumlah_kupon' => $data['jumlah_kupon'],
        ':jumlah_kaos' => $data['jumlah_kaos'],
        ':catatan' => $data['catatan'] !== '' ? $data['catatan'] : null,
    ];
    foreach ($ukuranColumns as $column) {
        $params[':' . $column] = (int) ($data[$column] ?? 0);
    }

    return $params;
}

function updateJalanSehatPendaftaran(int $id, array $data): bool
{
    ensureJalanSehatSchema();
    if ($id < 1) {
        return false;
    }

    [$writeColumns, $params] = jalanSehatPendaftaranWritePayload($data);
    $setParts = array_map(
        static fn (string $col): string => '`' . $col . '` = :' . $col,
        $writeColumns
    );
    $params[':id'] = $id;

    $stmt = getDb()->prepare(
        'UPDATE jalan_sehat_pendaftaran SET ' . implode(', ', $setParts) . ' WHERE id = :id'
    );

    return $stmt->execute($params);
}

/**
 * @return array{0: list<string>, 1: array<string, mixed>}
 */
function jalanSehatPendaftaranWritePayload(array $data): array
{
    $ukuranAll = array_values(jalanSehatUkuranKaos());
    $paramsAll = jalanSehatPendaftaranParams($data, $ukuranAll);
    $available = array_flip(jalanSehatPendaftaranColumnNames(null, true));
    $ordered = [
        'nama_madrasah', 'alamat', 'kode_kecamatan', 'nama_kecamatan', 'kode_kelurahan', 'nama_kelurahan',
        'alamat_detail', 'nama_kepala', 'nomor_wa', 'nomor_wa_norm', 'nomor_hp_kepala', 'nomor_hp_kepala_norm',
        'paket_id', 'paket_nama', 'jumlah_kupon', 'jumlah_kaos',
    ];
    foreach ($ukuranAll as $column) {
        $ordered[] = $column;
    }
    $ordered[] = 'catatan';

    $writeColumns = [];
    $params = [];
    foreach ($ordered as $column) {
        if (!isset($available[$column])) {
            continue;
        }
        $writeColumns[] = $column;
        $params[':' . $column] = $paramsAll[':' . $column];
    }

    return [$writeColumns, $params];
}

function jalanSehatUrutanOptions(): array
{
    return [
        'terbaru' => 'Terbaru',
        'wilayah' => 'Kecamatan & Desa (A–Z)',
        'madrasah' => 'Nama Madrasah (A–Z)',
    ];
}

function formatTanggalIndonesia(?string $datetime = null): string
{
    $timestamp = $datetime !== null && $datetime !== '' ? strtotime($datetime) : time();
    if ($timestamp === false) {
        $timestamp = time();
    }
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    return (int) date('j', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

function nomorSuratTandaTerimaKaos(array $row): string
{
    $saat = (string) ($row['kaos_diambil_pada'] ?? '');
    $tahun = $saat !== '' ? date('Y', strtotime($saat) ?: time()) : date('Y');

    return 'STT-JS/' . str_pad((string) (int) ($row['id'] ?? 0), 5, '0', STR_PAD_LEFT) . '/' . $tahun;
}

/**
 * @return array{ok: bool, message: string}
 */
function saveJalanSehatTandaTerimaKaos(int $id, array $input): array
{
    ensureJalanSehatSchema();
    if ($id < 1 || getJalanSehatPendaftaranById($id) === null) {
        return ['ok' => false, 'message' => 'Data pendaftaran tidak ditemukan.'];
    }

    $nama = trim((string) ($input['penerima_nama'] ?? ''));
    $jabatan = trim((string) ($input['penerima_jabatan'] ?? ''));
    $hp = trim((string) ($input['penerima_hp'] ?? ''));
    $tanggal = trim((string) ($input['tanggal_ambil'] ?? ''));

    if ($nama === '') {
        return ['ok' => false, 'message' => 'Nama penerima wajib diisi.'];
    }
    if ($tanggal === '' || strtotime($tanggal) === false) {
        return ['ok' => false, 'message' => 'Tanggal pengambilan tidak valid.'];
    }
    if ($hp !== '') {
        $hpNorm = normalizeNomorWa($hp);
        if (strlen($hpNorm) < 9 || strlen($hpNorm) > 15) {
            return ['ok' => false, 'message' => 'Nomor HP penerima tidak valid.'];
        }
        $hp = $hpNorm;
    }

    $columns = array_flip(jalanSehatPendaftaranColumnNames(null, true));
    $sets = [];
    $params = [':id' => $id];
    $values = [
        'kaos_diambil_pada' => date('Y-m-d H:i:s', strtotime($tanggal . ' ' . date('H:i:s'))),
        'kaos_diambil_nama' => $nama,
        'kaos_diambil_jabatan' => $jabatan !== '' ? $jabatan : null,
        'kaos_diambil_hp' => $hp !== '' ? $hp : null,
    ];
    foreach ($values as $column => $value) {
        if (!isset($columns[$column])) {
            return ['ok' => false, 'message' => 'Kolom tanda terima belum tersedia di database.'];
        }
        $sets[] = '`' . $column . '` = :' . $column;
        $params[':' . $column] = $value;
    }

    $stmt = getDb()->prepare('UPDATE jalan_sehat_pendaftaran SET ' . implode(', ', $sets) . ' WHERE id = :id');
    $stmt->execute($params);

    return ['ok' => true, 'message' => 'Tanda terima pengambilan kaos tersimpan.'];
}

function batalJalanSehatTandaTerimaKaos(int $id): bool
{
    ensureJalanSehatSchema();
    if ($id < 1) {
        return false;
    }
    $columns = array_flip(jalanSehatPendaftaranColumnNames(null, true));
    $sets = [];
    foreach (['kaos_diambil_pada', 'kaos_diambil_nama', 'kaos_diambil_jabatan', 'kaos_diambil_hp'] as $column) {
        if (isset($columns[$column])) {
            $sets[] = '`' . $column . '` = NULL';
        }
    }
    if ($sets === []) {
        return false;
    }
    $stmt = getDb()->prepare('UPDATE jalan_sehat_pendaftaran SET ' . implode(', ', $sets) . ' WHERE id = :id');
    $stmt->execute([':id' => $id]);

    return $stmt->rowCount() > 0;
}

function jalanSehatJenisPesananFilterOptions(): array
{
    return [
        '' => 'Semua jenis pesanan',
        'kupon_saja' => 'Kupon saja (tanpa kaos)',
        'dengan_kaos' => 'Dengan kaos',
    ];
}

function jalanSehatNormalizeJenisPesananFilter(string $value): string
{
    $value = trim($value);

    return array_key_exists($value, jalanSehatJenisPesananFilterOptions()) ? $value : '';
}

/** @return list<string> */
function jalanSehatJenjangOptions(): array
{
    return ['MI', 'SD', 'MTS', 'MA', 'SMA', 'SMK'];
}

function jalanSehatNormalizeJenjangFilter(string $jenjang): string
{
    $raw = trim($jenjang);
    if ($raw === '') {
        return '';
    }
    if (strcasecmp($raw, 'MTs') === 0) {
        return 'MTS';
    }

    $upper = strtoupper($raw);
    if (in_array($upper, jalanSehatJenjangOptions(), true)) {
        return $upper;
    }

    return '';
}

function jalanSehatJenjangFromNamaMadrasah(string $nama): string
{
    $nama = trim($nama);
    if ($nama === '') {
        return 'Lainnya';
    }
    if (preg_match('/^SMA[\s.]/iu', $nama)) {
        return 'SMA';
    }
    if (preg_match('/^SMK[\s.]/iu', $nama)) {
        return 'SMK';
    }
    if (preg_match('/^MA[\s.]/iu', $nama)) {
        return 'MA';
    }
    if (preg_match('/^(MTS|MTs)[\s.]/iu', $nama)) {
        return 'MTS';
    }
    if (preg_match('/^SD[\s.]/iu', $nama)) {
        return 'SD';
    }
    if (preg_match('/^MI[\s.]/iu', $nama)) {
        return 'MI';
    }

    return 'Lainnya';
}

function jalanSehatJenjangSqlCondition(string $jenjang): string
{
    $jenjang = jalanSehatNormalizeJenjangFilter($jenjang);

    return match ($jenjang) {
        'MI' => "(nama_madrasah LIKE 'MI %' OR nama_madrasah LIKE 'MI.%')",
        'SD' => "(nama_madrasah LIKE 'SD %' OR nama_madrasah LIKE 'SD.%')",
        'MTS' => "(nama_madrasah LIKE 'MTs %' OR nama_madrasah LIKE 'MTs.%' OR nama_madrasah LIKE 'MTS %' OR nama_madrasah LIKE 'MTS.%')",
        'MA' => "(nama_madrasah LIKE 'MA %' OR nama_madrasah LIKE 'MA.%')",
        'SMA' => "(nama_madrasah LIKE 'SMA %' OR nama_madrasah LIKE 'SMA.%')",
        'SMK' => "(nama_madrasah LIKE 'SMK %' OR nama_madrasah LIKE 'SMK.%')",
        default => '',
    };
}

/**
 * @param array{paket?: string, kecamatan?: string, kelurahan?: string, urut?: string, jenjang?: string, jenis_pesanan?: string, pengambilan?: string} $filters
 */
function loadJalanSehatPendaftaran(string $search = '', array $filters = []): array
{
    ensureJalanSehatTables();
    $sql = 'SELECT * FROM jalan_sehat_pendaftaran WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (nama_madrasah LIKE :q1 OR nama_kepala LIKE :q2 OR alamat LIKE :q3 OR nomor_wa LIKE :q4)';
        $like = '%' . $search . '%';
        $params[':q1'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
        $params[':q4'] = $like;
    }

    $paketFilter = (string) ($filters['paket'] ?? '');
    if ($paketFilter === 'custom') {
        $sql .= ' AND paket_id IS NULL';
    } elseif ($paketFilter !== '' && ctype_digit($paketFilter)) {
        $sql .= ' AND paket_id = :paket_id';
        $params[':paket_id'] = (int) $paketFilter;
    }

    $kecamatan = (string) ($filters['kecamatan'] ?? '');
    if ($kecamatan !== '') {
        $sql .= ' AND nama_kecamatan = :kecamatan';
        $params[':kecamatan'] = $kecamatan;
    }

    $kelurahan = (string) ($filters['kelurahan'] ?? '');
    if ($kelurahan !== '') {
        $sql .= ' AND nama_kelurahan = :kelurahan';
        $params[':kelurahan'] = $kelurahan;
    }

    $jenjang = jalanSehatNormalizeJenjangFilter((string) ($filters['jenjang'] ?? ''));
    if ($jenjang !== '' && in_array($jenjang, jalanSehatJenjangOptions(), true)) {
        $jenjangSql = jalanSehatJenjangSqlCondition($jenjang);
        if ($jenjangSql !== '') {
            $sql .= ' AND ' . $jenjangSql;
        }
    }

    $jenisPesanan = jalanSehatNormalizeJenisPesananFilter((string) ($filters['jenis_pesanan'] ?? ''));
    if ($jenisPesanan === 'kupon_saja') {
        $sql .= ' AND jumlah_kaos = 0 AND jumlah_kupon > 0';
    } elseif ($jenisPesanan === 'dengan_kaos') {
        $sql .= ' AND jumlah_kaos > 0';
    }

    $pengambilan = (string) ($filters['pengambilan'] ?? '');
    if (in_array($pengambilan, ['sudah', 'belum'], true)
        && in_array('kaos_diambil_pada', jalanSehatPendaftaranColumnNames(), true)) {
        $sql .= $pengambilan === 'sudah'
            ? ' AND kaos_diambil_pada IS NOT NULL'
            : ' AND kaos_diambil_pada IS NULL AND jumlah_kaos > 0';
    }

    $sql .= match ($filters['urut'] ?? 'terbaru') {
        'wilayah' => " ORDER BY (nama_kecamatan IS NULL OR nama_kecamatan = '') ASC, nama_kecamatan ASC, nama_kelurahan ASC, nama_madrasah ASC",
        'madrasah' => ' ORDER BY nama_madrasah ASC, id ASC',
        default => ' ORDER BY created_at DESC, id DESC',
    };

    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * @return array<string, string[]> nama kecamatan => daftar nama desa/kelurahan yang sudah ada pendaftarnya
 */
function getJalanSehatWilayahOptions(): array
{
    ensureJalanSehatTables();
    $rows = getDb()->query(
        "SELECT DISTINCT nama_kecamatan, nama_kelurahan FROM jalan_sehat_pendaftaran
         WHERE nama_kecamatan IS NOT NULL AND nama_kecamatan <> ''
         ORDER BY nama_kecamatan ASC, nama_kelurahan ASC"
    )->fetchAll();

    $options = [];
    foreach ($rows as $row) {
        $kec = (string) $row['nama_kecamatan'];
        $options[$kec] ??= [];
        if (!empty($row['nama_kelurahan'])) {
            $options[$kec][] = (string) $row['nama_kelurahan'];
        }
    }

    return $options;
}

function getJalanSehatPendaftaranById(int $id): ?array
{
    ensureJalanSehatTables();
    $stmt = getDb()->prepare('SELECT * FROM jalan_sehat_pendaftaran WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function deleteJalanSehatPendaftaran(int $id): bool
{
    ensureJalanSehatTables();
    $stmt = getDb()->prepare('DELETE FROM jalan_sehat_pendaftaran WHERE id = :id');
    $stmt->execute([':id' => $id]);

    return $stmt->rowCount() > 0;
}

function getJalanSehatRingkasan(array $rows, array $settings): array
{
    $ringkasan = [
        'total_madrasah' => count($rows),
        'total_kupon' => 0,
        'total_kaos' => 0,
        'total_biaya' => 0,
        'total_biaya_lunas' => 0,
        'total_biaya_belum_lunas' => 0,
        'madrasah_lunas' => 0,
        'subtotal_kupon' => 0,
        'subtotal_kaos' => 0,
        'per_paket' => [],
        'per_ukuran' => array_fill_keys(array_keys(jalanSehatUkuranKaos()), 0),
        'per_kecamatan' => [],
    ];
    $hasLunasCol = in_array('pembayaran_lunas', jalanSehatPendaftaranColumnNames(), true);

    foreach ($rows as $row) {
        $kupon = (int) ($row['jumlah_kupon'] ?? 0);
        $kaos = (int) ($row['jumlah_kaos'] ?? 0);
        $biaya = hitungBiayaJalanSehatFromRow($row, $settings);
        $rincian = jalanSehatRincianBiayaFromRow($row, $settings);
        $lunas = $hasLunasCol && !empty($row['pembayaran_lunas']);

        $kec = trim((string) ($row['nama_kecamatan'] ?? ''));
        $kec = $kec !== '' ? $kec : 'Belum ada kecamatan';
        $ringkasan['per_kecamatan'][$kec] ??= ['madrasah' => 0, 'kupon' => 0, 'kaos' => 0];
        $ringkasan['per_kecamatan'][$kec]['madrasah']++;
        $ringkasan['per_kecamatan'][$kec]['kupon'] += $kupon;
        $ringkasan['per_kecamatan'][$kec]['kaos'] += $kaos;

        foreach (jalanSehatUkuranKaos() as $label => $column) {
            $ringkasan['per_ukuran'][$label] += (int) ($row[$column] ?? 0);
        }
        $ringkasan['total_kupon'] += $kupon;
        $ringkasan['total_kaos'] += $kaos;
        $ringkasan['total_biaya'] += $biaya;
        $ringkasan['subtotal_kupon'] += $rincian['subtotal_kupon'];
        $ringkasan['subtotal_kaos'] += $rincian['subtotal_kaos'];
        if ($lunas) {
            $ringkasan['total_biaya_lunas'] += $biaya;
            $ringkasan['madrasah_lunas']++;
        } else {
            $ringkasan['total_biaya_belum_lunas'] += $biaya;
        }

        $label = !empty($row['paket_nama']) ? (string) $row['paket_nama'] : 'Jumlah sendiri';
        $ringkasan['per_paket'][$label] = ($ringkasan['per_paket'][$label] ?? 0) + 1;
    }

    arsort($ringkasan['per_paket']);
    ksort($ringkasan['per_kecamatan'], SORT_NATURAL | SORT_FLAG_CASE);

    return $ringkasan;
}

function jalanSehatMonitorKaosEnabled(array $settings): bool
{
    return ($settings['monitor_kaos_aktif'] ?? '0') === '1';
}

function jalanSehatTotalKaosFromRow(array $row): int
{
    $total = 0;
    foreach (jalanSehatUkuranKaos() as $column) {
        $total += (int) ($row[$column] ?? 0);
    }

    return $total;
}

/**
 * @return array{ok: bool, selesai?: int, message?: string}
 */
function setJalanSehatKaosKonveksiSelesai(int $id, bool $selesai): array
{
    ensureJalanSehatSchemaMigrations();
    if (!in_array('kaos_konveksi_selesai', jalanSehatPendaftaranColumnNames(), true)) {
        return ['ok' => false, 'message' => 'Fitur centang belum tersedia di database. Hubungi panitia.'];
    }

    $row = getJalanSehatPendaftaranById($id);
    if ($row === null) {
        return ['ok' => false, 'message' => 'Data madrasah tidak ditemukan.'];
    }
    if (jalanSehatTotalKaosFromRow($row) <= 0) {
        return ['ok' => false, 'message' => 'Madrasah ini tidak memesan kaos.'];
    }

    $stmt = getDb()->prepare(
        'UPDATE jalan_sehat_pendaftaran SET kaos_konveksi_selesai = :selesai WHERE id = :id'
    );
    $stmt->execute([':selesai' => $selesai ? 1 : 0, ':id' => $id]);

    return ['ok' => true, 'selesai' => $selesai ? 1 : 0];
}

/**
 * Rekap pemesanan kaos untuk konveksi/pencetak (tanpa data kontak).
 *
 * @return array{
 *   total_pcs: int,
 *   total_pendek: int,
 *   total_panjang: int,
 *   madrasah_ada_kaos: int,
 *   per_ukuran: array{pendek: array<string, int>, panjang: array<string, int>},
 *   per_madrasah: list<array{
 *     id: int,
 *     nama_madrasah: string,
 *     kecamatan: string,
 *     total: int,
 *     pendek: int,
 *     panjang: int,
 *     ukuran_pendek: array<string, int>,
 *     ukuran_panjang: array<string, int>,
 *     selesai: bool,
 *     jenjang: string
 *   }>,
 *   madrasah_selesai: int
 * }
 */
function getJalanSehatRekapKaosPencetak(array $rows): array
{
    $byJenis = jalanSehatUkuranKaosByJenis();
    $sizes = array_keys($byJenis['pendek']['columns']);
    $hasSelesaiCol = in_array('kaos_konveksi_selesai', jalanSehatPendaftaranColumnNames(), true);

    $rekap = [
        'total_pcs' => 0,
        'total_pendek' => 0,
        'total_panjang' => 0,
        'madrasah_ada_kaos' => 0,
        'madrasah_selesai' => 0,
        'per_ukuran' => [
            'pendek' => array_fill_keys($sizes, 0),
            'panjang' => array_fill_keys($sizes, 0),
        ],
        'per_madrasah' => [],
    ];

    foreach ($rows as $row) {
        $pendek = 0;
        $panjang = 0;
        $ukuranPendek = [];
        $ukuranPanjang = [];

        foreach ($byJenis['pendek']['columns'] as $label => $col) {
            $q = (int) ($row[$col] ?? 0);
            $rekap['per_ukuran']['pendek'][$label] += $q;
            $pendek += $q;
            if ($q > 0) {
                $ukuranPendek[$label] = $q;
            }
        }
        foreach ($byJenis['panjang']['columns'] as $label => $col) {
            $q = (int) ($row[$col] ?? 0);
            $rekap['per_ukuran']['panjang'][$label] += $q;
            $panjang += $q;
            if ($q > 0) {
                $ukuranPanjang[$label] = $q;
            }
        }

        $totalRow = $pendek + $panjang;
        $rekap['total_pendek'] += $pendek;
        $rekap['total_panjang'] += $panjang;
        $rekap['total_pcs'] += $totalRow;

        if ($totalRow > 0) {
            $rekap['madrasah_ada_kaos']++;
            $selesai = $hasSelesaiCol && !empty($row['kaos_konveksi_selesai']);
            if ($selesai) {
                $rekap['madrasah_selesai']++;
            }
            $rekap['per_madrasah'][] = [
                'id' => (int) $row['id'],
                'nama_madrasah' => (string) $row['nama_madrasah'],
                'kecamatan' => trim((string) ($row['nama_kecamatan'] ?? '')) !== ''
                    ? trim((string) $row['nama_kecamatan'])
                    : '—',
                'total' => $totalRow,
                'pendek' => $pendek,
                'panjang' => $panjang,
                'ukuran_pendek' => $ukuranPendek,
                'ukuran_panjang' => $ukuranPanjang,
                'selesai' => $selesai,
                'jenjang' => jalanSehatJenjangFromNamaMadrasah((string) $row['nama_madrasah']),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }
    }

    usort(
        $rekap['per_madrasah'],
        static function (array $a, array $b): int {
            $ta = strtotime($a['created_at'] ?? '') ?: 0;
            $tb = strtotime($b['created_at'] ?? '') ?: 0;
            if ($ta !== $tb) {
                return $tb <=> $ta;
            }

            return ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0));
        }
    );

    return $rekap;
}

function alamatDetailJalanSehat(array $row): string
{
    $detail = trim((string) ($row['alamat_detail'] ?? ''));

    return $detail !== '' ? $detail : trim((string) ($row['alamat'] ?? ''));
}

function isJalanSehatAdminLoggedIn(): bool
{
    return isMaarifAdminLoggedIn();
}

function jalanSehatAdminListScripts(): string
{
    $url = json_encode(url('adminjalansehat/'), JSON_UNESCAPED_UNICODE);

    return <<<HTML
<script>
(function () {
  var adminUrl = {$url};
  document.querySelectorAll('.js-pembayaran-lunas').forEach(function (cb) {
    cb.addEventListener('change', function () {
      var id = cb.getAttribute('data-id');
      var lunas = cb.checked;
      var fd = new FormData();
      fd.append('action', 'toggle_pembayaran_lunas');
      fd.append('id', id || '');
      fd.append('lunas', lunas ? '1' : '0');
      cb.disabled = true;
      fetch(adminUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res || !res.ok) {
            cb.checked = !lunas;
            alert((res && res.message) ? res.message : 'Gagal menyimpan status pembayaran.');
            return;
          }
          cb.closest('tr')?.classList.toggle('bg-emerald-50/80', lunas);
        })
        .catch(function () {
          cb.checked = !lunas;
          alert('Koneksi gagal.');
        })
        .finally(function () { cb.disabled = false; });
    });
  });
})();
</script>
HTML;
}

function jalanSehatAdminFormScripts(array $settings): string
{
    $kuponMengikuti = jalanSehatKuponMengikutiKaos($settings);
    $kuponPerKaos = jalanSehatKuponPerKaos($settings);
    ob_start();
    require dirname(__DIR__) . '/pesertakerdinma/_wilayah_registrasi_script.php';
    ?>
<script>
(function () {
  const inputs = document.querySelectorAll('.js-kaos-input');
  const totalEl = document.getElementById('total-kaos');
  const kuponEl = document.getElementById('jumlah_kupon');
  const paketSelect = document.querySelector('select[name="pilihan_paket"]');
  const kuponMengikutiKaos = <?= $kuponMengikuti ? 'true' : 'false' ?>;
  const kuponPerKaos = <?= (int) $kuponPerKaos ?>;

  function totalKaos() {
    let t = 0;
    inputs.forEach(function (el) {
      const n = parseInt(el.value, 10);
      if (Number.isFinite(n) && n > 0) t += n;
    });
    return t;
  }

  function isJumlahSendiri() {
    return !paketSelect || paketSelect.value === 'custom';
  }

  function syncKuponDariKaos() {
    if (!kuponMengikutiKaos || !kuponEl || !isJumlahSendiri()) return;
    const t = totalKaos();
    if (t > 0) {
      kuponEl.value = String(t * kuponPerKaos);
    }
  }

  function update() {
    const t = totalKaos();
    if (totalEl) totalEl.textContent = String(t);
  }
  inputs.forEach(function (el) { el.addEventListener('input', update); });
  if (paketSelect) paketSelect.addEventListener('change', update);
  var btnSync = document.getElementById('admin-sync-kupon-kaos');
  if (btnSync) {
    btnSync.addEventListener('click', function (e) {
      e.preventDefault();
      syncKuponDariKaos();
    });
  }
  update();
})();
</script>
    <?php

    return ob_get_clean();
}

/**
 * Filter rekap monitor kaos (kecamatan, jenjang, status kerja, pencarian nama).
 *
 * @param array{kecamatan?: string, jenjang?: string, status?: string, q?: string} $filters
 * @return array rekap dengan total dihitung ulang dari baris terfilter
 */
function filterJalanSehatMonitorRekap(array $rekap, array $filters = []): array
{
    $byJenis = jalanSehatUkuranKaosByJenis();
    $sizes = array_keys($byJenis['pendek']['columns']);
    $kecamatan = trim((string) ($filters['kecamatan'] ?? ''));
    $jenjang = trim((string) ($filters['jenjang'] ?? ''));
    $status = trim((string) ($filters['status'] ?? 'all'));
    $q = strtolower(trim((string) ($filters['q'] ?? '')));

    $filtered = [];
    foreach ($rekap['per_madrasah'] as $row) {
        $blob = strtolower($row['nama_madrasah'] . ' ' . $row['kecamatan']);
        if ($q !== '' && !str_contains($blob, $q)) {
            continue;
        }
        if ($kecamatan !== '' && (string) $row['kecamatan'] !== $kecamatan) {
            continue;
        }
        if ($jenjang !== '' && (string) $row['jenjang'] !== $jenjang) {
            continue;
        }
        $selesai = !empty($row['selesai']);
        if ($status === 'done' && !$selesai) {
            continue;
        }
        if ($status === 'pending' && $selesai) {
            continue;
        }
        $filtered[] = $row;
    }

    $out = [
        'total_pcs' => 0,
        'total_pendek' => 0,
        'total_panjang' => 0,
        'madrasah_ada_kaos' => count($filtered),
        'madrasah_selesai' => 0,
        'per_ukuran' => [
            'pendek' => array_fill_keys($sizes, 0),
            'panjang' => array_fill_keys($sizes, 0),
        ],
        'per_madrasah' => $filtered,
    ];

    foreach ($filtered as $row) {
        $out['total_pcs'] += (int) $row['total'];
        $out['total_pendek'] += (int) $row['pendek'];
        $out['total_panjang'] += (int) $row['panjang'];
        if (!empty($row['selesai'])) {
            $out['madrasah_selesai']++;
        }
        foreach ($sizes as $sz) {
            $out['per_ukuran']['pendek'][$sz] += (int) ($row['ukuran_pendek'][$sz] ?? 0);
            $out['per_ukuran']['panjang'][$sz] += (int) ($row['ukuran_panjang'][$sz] ?? 0);
        }
    }

    return $out;
}

/** Export Excel untuk halaman monitor kaos (konveksi). */
function exportJalanSehatMonitorKaosXls(array $rekap, array $filters = []): void
{
    $filename = 'monitor_kaos_jalan_sehat_' . date('Y-m-d_His') . '.xls';
    $byJenis = jalanSehatUkuranKaosByJenis();
    $sizes = array_keys($byJenis['pendek']['columns']);
    $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $pu = $rekap['per_ukuran']['pendek'];
    $pj = $rekap['per_ukuran']['panjang'];

    $filterLabels = [];
    if (($filters['kecamatan'] ?? '') !== '') {
        $filterLabels[] = 'Kecamatan: ' . $filters['kecamatan'];
    }
    if (($filters['jenjang'] ?? '') !== '') {
        $filterLabels[] = 'Jenjang: ' . $filters['jenjang'];
    }
    if (($filters['status'] ?? 'all') === 'done') {
        $filterLabels[] = 'Status: Sudah selesai';
    } elseif (($filters['status'] ?? 'all') === 'pending') {
        $filterLabels[] = 'Status: Belum dikerjakan';
    }
    if (($filters['q'] ?? '') !== '') {
        $filterLabels[] = 'Cari: ' . $filters['q'];
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<h2>' . $escape('Monitor Kaos — Jalan Sehat LP Ma\'arif NU Kab. Magelang') . '</h2>';
    echo '<p>' . $escape('Diekspor: ' . date('d/m/Y H:i') . ' WIB') . '</p>';
    if ($filterLabels !== []) {
        echo '<p>' . $escape('Filter: ' . implode(' · ', $filterLabels)) . '</p>';
    }

    echo '<table border="1"><tr><th colspan="2">' . $escape('RINGKASAN') . '</th></tr>';
    echo '<tr><td>Total kaos (pcs)</td><td>' . (int) $rekap['total_pcs'] . '</td></tr>';
    echo '<tr><td>Lengan pendek</td><td>' . (int) $rekap['total_pendek'] . '</td></tr>';
    echo '<tr><td>Lengan panjang</td><td>' . (int) $rekap['total_panjang'] . '</td></tr>';
    echo '<tr><td>Madrasah pesan kaos</td><td>' . (int) $rekap['madrasah_ada_kaos'] . '</td></tr>';
    echo '<tr><td>Sudah dikerjakan</td><td>' . (int) $rekap['madrasah_selesai'] . '</td></tr>';
    echo '</table><br>';

    echo '<table border="1"><tr><th>' . $escape('Jenis kaos') . '</th>';
    foreach ($sizes as $sz) {
        echo '<th>' . $escape($sz) . '</th>';
    }
    echo '<th>' . $escape('Jumlah') . '</th></tr>';
    echo '<tr><td>' . $escape('Lengan pendek') . '</td>';
    foreach ($sizes as $sz) {
        echo '<td>' . (int) $pu[$sz] . '</td>';
    }
    echo '<td>' . (int) $rekap['total_pendek'] . '</td></tr>';
    echo '<tr><td>' . $escape('Lengan panjang') . '</td>';
    foreach ($sizes as $sz) {
        echo '<td>' . (int) $pj[$sz] . '</td>';
    }
    echo '<td>' . (int) $rekap['total_panjang'] . '</td></tr>';
    echo '</table><br>';

    echo '<table border="1">';
    echo '<tr>';
    echo '<th rowspan="2">' . $escape('No') . '</th>';
    echo '<th rowspan="2">' . $escape('Status') . '</th>';
    echo '<th rowspan="2">' . $escape('Madrasah') . '</th>';
    echo '<th rowspan="2">' . $escape('Jenjang') . '</th>';
    echo '<th rowspan="2">' . $escape('Kecamatan') . '</th>';
    echo '<th colspan="' . count($sizes) . '">' . $escape('Lengan pendek') . '</th>';
    echo '<th colspan="' . count($sizes) . '">' . $escape('Lengan panjang') . '</th>';
    echo '<th rowspan="2">' . $escape('Total') . '</th>';
    echo '</tr><tr>';
    foreach ($sizes as $sz) {
        echo '<th>' . $escape('Pdk ' . $sz) . '</th>';
    }
    foreach ($sizes as $sz) {
        echo '<th>' . $escape('Pjg ' . $sz) . '</th>';
    }
    echo '</tr>';

    foreach ($rekap['per_madrasah'] as $index => $m) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . $escape(!empty($m['selesai']) ? 'Selesai' : 'Belum') . '</td>';
        echo '<td>' . $escape((string) $m['nama_madrasah']) . '</td>';
        echo '<td>' . $escape((string) ($m['jenjang'] ?? '')) . '</td>';
        echo '<td>' . $escape((string) $m['kecamatan']) . '</td>';
        foreach ($sizes as $sz) {
            echo '<td>' . (int) ($m['ukuran_pendek'][$sz] ?? 0) . '</td>';
        }
        foreach ($sizes as $sz) {
            echo '<td>' . (int) ($m['ukuran_panjang'][$sz] ?? 0) . '</td>';
        }
        echo '<td>' . (int) $m['total'] . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

function exportJalanSehatXls(array $rows, array $settings): void
{
    $filename = 'pendaftaran_jalan_sehat_hsn2026_' . date('Y-m-d_His') . '.xls';
    $adaHarga = jalanSehatAdaHarga($settings);
    $ringkasan = getJalanSehatRingkasan($rows, $settings);
    $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1"><tr>';
    $headers = ['No', 'Tanggal Daftar', 'Nama Madrasah', 'Kecamatan', 'Desa/Kelurahan', 'Alamat Detail', 'Nama Kepala', 'Nomor WA', 'Paket', 'Kupon Door Prize'];
    foreach (array_keys(jalanSehatUkuranKaos()) as $label) {
        $headers[] = 'Kaos ' . $label;
    }
    $headers[] = 'Total Kaos (pcs)';
    if ($adaHarga) {
        $headers[] = 'Biaya Kupon';
        $headers[] = 'Biaya Kaos';
        $headers[] = 'Total Tagihan';
    }
    $headers[] = 'Pembayaran Lunas';
    $headers[] = 'Catatan';
    foreach ($headers as $header) {
        echo '<th>' . $escape($header) . '</th>';
    }
    echo '</tr>';

    foreach ($rows as $index => $row) {
        $kupon = (int) ($row['jumlah_kupon'] ?? 0);
        $kaos = (int) ($row['jumlah_kaos'] ?? 0);
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . $escape((string) ($row['created_at'] ?? '')) . '</td>';
        echo '<td>' . $escape((string) ($row['nama_madrasah'] ?? '')) . '</td>';
        echo '<td>' . $escape((string) ($row['nama_kecamatan'] ?? '')) . '</td>';
        echo '<td>' . $escape((string) ($row['nama_kelurahan'] ?? '')) . '</td>';
        echo '<td>' . $escape(alamatDetailJalanSehat($row)) . '</td>';
        echo '<td>' . $escape((string) ($row['nama_kepala'] ?? '')) . '</td>';
        echo '<td style="mso-number-format:\'\\@\';">' . $escape((string) ($row['nomor_wa'] ?? '')) . '</td>';
        echo '<td>' . $escape(!empty($row['paket_nama']) ? (string) $row['paket_nama'] : 'Jumlah sendiri') . '</td>';
        echo '<td>' . $kupon . '</td>';
        foreach (jalanSehatUkuranKaos() as $column) {
            echo '<td>' . (int) ($row[$column] ?? 0) . '</td>';
        }
        echo '<td>' . $kaos . '</td>';
        if ($adaHarga) {
            $rincian = jalanSehatRincianBiayaFromRow($row, $settings);
            echo '<td>' . $rincian['subtotal_kupon'] . '</td>';
            echo '<td>' . $rincian['subtotal_kaos'] . '</td>';
            echo '<td>' . $rincian['total'] . '</td>';
        }
        echo '<td>' . (!empty($row['pembayaran_lunas']) ? 'Ya' : 'Belum') . '</td>';
        echo '<td>' . $escape((string) ($row['catatan'] ?? '')) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<br><table border="1">';
    echo '<tr><th colspan="2">' . $escape('RINGKASAN') . '</th></tr>';
    echo '<tr><td>Total Madrasah</td><td>' . $ringkasan['total_madrasah'] . '</td></tr>';
    echo '<tr><td>Total Kupon Door Prize</td><td>' . $ringkasan['total_kupon'] . '</td></tr>';
    echo '<tr><td>Total Kaos (pcs)</td><td>' . $ringkasan['total_kaos'] . '</td></tr>';
    foreach ($ringkasan['per_ukuran'] as $label => $qty) {
        echo '<tr><td>' . $escape('Kaos ukuran ' . $label) . '</td><td>' . (int) $qty . '</td></tr>';
    }
    if ($adaHarga) {
        echo '<tr><td>Subtotal Kupon</td><td>' . (int) $ringkasan['subtotal_kupon'] . '</td></tr>';
        echo '<tr><td>Subtotal Kaos</td><td>' . (int) $ringkasan['subtotal_kaos'] . '</td></tr>';
        echo '<tr><td>Total Tagihan</td><td>' . (int) $ringkasan['total_biaya'] . '</td></tr>';
        echo '<tr><td>Sudah Dibayar (lunas)</td><td>' . (int) $ringkasan['total_biaya_lunas'] . '</td></tr>';
        echo '<tr><td>Belum Dibayar</td><td>' . (int) $ringkasan['total_biaya_belum_lunas'] . '</td></tr>';
    }
    echo '</table>';

    echo '<br><table border="1">';
    echo '<tr><th>Kecamatan</th><th>Jumlah Madrasah</th><th>Kupon Door Prize</th><th>Kaos (pcs)</th></tr>';
    foreach ($ringkasan['per_kecamatan'] as $kec => $item) {
        echo '<tr><td>' . $escape((string) $kec) . '</td><td>' . (int) $item['madrasah'] . '</td><td>'
            . (int) $item['kupon'] . '</td><td>' . (int) $item['kaos'] . '</td></tr>';
    }
    echo '</table></body></html>';
    exit;
}
