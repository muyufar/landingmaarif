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

    $afterKonveksi = in_array('kaos_panjang_xxxl', $existingColumns, true)
        ? 'kaos_panjang_xxxl'
        : (in_array('kaos_xxxl', $existingColumns, true) ? 'kaos_xxxl' : 'jumlah_kaos');
    jalanSehatAddColumnIfMissing(
        $pdo,
        $existingColumns,
        'kaos_konveksi_selesai',
        'tinyint(1) NOT NULL DEFAULT 0',
        $afterKonveksi
    );

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
        'harga_kupon' => '0',
        'harga_kaos' => '0',
        'info_surat_edaran' => '',
        'monitor_kaos_aktif' => '0',
    ];
}

function getJalanSehatPengaturan(): array
{
    ensureJalanSehatTables();
    $settings = jalanSehatPengaturanDefaults();
    $rows = getDb()->query('SELECT kunci, nilai FROM jalan_sehat_pengaturan')->fetchAll();
    foreach ($rows as $row) {
        if (array_key_exists($row['kunci'], $settings)) {
            $settings[$row['kunci']] = (string) ($row['nilai'] ?? '');
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
        'info_surat_edaran' => trim((string) ($input['info_surat_edaran'] ?? '')),
        'monitor_kaos_aktif' => !empty($input['monitor_kaos_aktif']) ? '1' : '0',
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

function hitungBiayaJalanSehat(int $kupon, int $kaos, array $settings): int
{
    return $kupon * (int) $settings['harga_kupon'] + $kaos * (int) $settings['harga_kaos'];
}

function jalanSehatAdaHarga(array $settings): bool
{
    return (int) $settings['harga_kupon'] > 0 || (int) $settings['harga_kaos'] > 0;
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
 * @param array{paket?: string, kecamatan?: string, kelurahan?: string, urut?: string, jenjang?: string} $filters
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
        'per_paket' => [],
        'per_ukuran' => array_fill_keys(array_keys(jalanSehatUkuranKaos()), 0),
        'per_kecamatan' => [],
    ];

    foreach ($rows as $row) {
        $kupon = (int) ($row['jumlah_kupon'] ?? 0);
        $kaos = (int) ($row['jumlah_kaos'] ?? 0);

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
        $ringkasan['total_biaya'] += hitungBiayaJalanSehat($kupon, $kaos, $settings);

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
 *     selesai: bool
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
            ];
        }
    }

    usort(
        $rekap['per_madrasah'],
        static function (array $a, array $b): int {
            $doneA = !empty($a['selesai']) ? 1 : 0;
            $doneB = !empty($b['selesai']) ? 1 : 0;
            if ($doneA !== $doneB) {
                return $doneA <=> $doneB;
            }

            return strnatcasecmp($a['nama_madrasah'], $b['nama_madrasah']);
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

function jalanSehatAdminFormScripts(): string
{
    ob_start();
    require dirname(__DIR__) . '/pesertakerdinma/_wilayah_registrasi_script.php';
    ?>
<script>
(function () {
  const inputs = document.querySelectorAll('.js-kaos-input');
  const totalEl = document.getElementById('total-kaos');
  function update() {
    let t = 0;
    inputs.forEach(function (el) {
      const n = parseInt(el.value, 10);
      if (Number.isFinite(n) && n > 0) t += n;
    });
    if (totalEl) totalEl.textContent = String(t);
  }
  inputs.forEach(function (el) { el.addEventListener('input', update); });
  update();
})();
</script>
    <?php

    return ob_get_clean();
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
        $headers[] = 'Estimasi Biaya';
    }
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
            echo '<td>' . hitungBiayaJalanSehat($kupon, $kaos, $settings) . '</td>';
        }
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
        echo '<tr><td>Total Estimasi Biaya</td><td>' . $ringkasan['total_biaya'] . '</td></tr>';
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
