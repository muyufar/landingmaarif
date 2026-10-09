<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function ensureJalanSehatSchema(): void
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
        if (!in_array($column, $existingColumns, true)) {
            $pdo->exec("ALTER TABLE jalan_sehat_pendaftaran ADD COLUMN `{$column}` {$definition} AFTER `{$previousColumn}`");
            if ($column === 'nama_kecamatan') {
                $pdo->exec('ALTER TABLE jalan_sehat_pendaftaran ADD KEY `idx_js_kecamatan` (`nama_kecamatan`)');
            }
        }
        $previousColumn = $column;
    }

    $previousColumn = 'jumlah_kaos';
    foreach (jalanSehatUkuranKaos() as $column) {
        if (!in_array($column, $existingColumns, true)) {
            $pdo->exec("ALTER TABLE jalan_sehat_pendaftaran ADD COLUMN `{$column}` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `{$previousColumn}`");
        }
        $previousColumn = $column;
    }

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
    ];
}

function getJalanSehatPengaturan(): array
{
    ensureJalanSehatSchema();
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
    ensureJalanSehatSchema();
    $sql = 'SELECT * FROM jalan_sehat_paket';
    if ($hanyaAktif) {
        $sql .= ' WHERE aktif = 1';
    }
    $sql .= ' ORDER BY urutan ASC, id ASC';

    return getDb()->query($sql)->fetchAll();
}

function getJalanSehatPaketById(int $id): ?array
{
    ensureJalanSehatSchema();
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
        'pilihan_paket' => '',
        'jumlah_kupon' => '0',
        'jumlah_kaos' => '0',
        'catatan' => '',
    ] + array_fill_keys(array_values(jalanSehatUkuranKaos()), 0);
}

/**
 * Nilai kupon/kaos untuk pilihan paket selalu diambil dari database, bukan dari input browser.
 */
function validateJalanSehat(array $input, array $settings, array $paketAktif): array
{
    $errors = [];
    $data = [
        'nama_madrasah' => trim((string) ($input['nama_madrasah'] ?? '')),
        'kode_kecamatan' => trim((string) ($input['kode_kecamatan'] ?? '')),
        'nama_kecamatan' => trim((string) ($input['nama_kecamatan'] ?? '')),
        'kode_kelurahan' => trim((string) ($input['kode_kelurahan'] ?? '')),
        'nama_kelurahan' => trim((string) ($input['nama_kelurahan'] ?? '')),
        'alamat_detail' => trim((string) ($input['alamat_detail'] ?? '')),
        'nama_kepala' => trim((string) ($input['nama_kepala'] ?? '')),
        'nomor_wa' => trim((string) ($input['nomor_wa'] ?? '')),
        'pilihan_paket' => trim((string) ($input['pilihan_paket'] ?? '')),
        'jumlah_kupon' => max(0, (int) ($input['jumlah_kupon'] ?? 0)),
        'jumlah_kaos' => max(0, (int) ($input['jumlah_kaos'] ?? 0)),
        'catatan' => trim((string) ($input['catatan'] ?? '')),
        'paket_id' => null,
        'paket_nama' => null,
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
        }
    }

    $izinkanBebas = $settings['izinkan_jumlah_bebas'] === '1' || $paketAktif === [];
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
        if ($paket === null) {
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
            } elseif ($totalUkuran !== $data['jumlah_kaos']) {
                $errors[] = sprintf(
                    'Rincian ukuran kaos harus berjumlah %d pcs sesuai paket (saat ini terisi %d pcs).',
                    $data['jumlah_kaos'],
                    $totalUkuran
                );
            }
        }
    } elseif ($pilihan === 'custom' && !$izinkanBebas) {
        $errors[] = 'Silakan pilih salah satu paket yang tersedia.';
    }

    if ($data['paket_id'] === null && $data['jumlah_kupon'] === 0 && $data['jumlah_kaos'] === 0) {
        $errors[] = 'Isi jumlah kupon door prize atau jumlah kaos per ukuran (minimal salah satu lebih dari 0).';
    }

    return ['errors' => $errors, 'data' => $data];
}

function addJalanSehatPendaftaran(array $data): int
{
    ensureJalanSehatSchema();
    $ukuranColumns = array_values(jalanSehatUkuranKaos());
    $stmt = getDb()->prepare(
        'INSERT INTO jalan_sehat_pendaftaran
            (nama_madrasah, alamat, kode_kecamatan, nama_kecamatan, kode_kelurahan, nama_kelurahan, alamat_detail,
             nama_kepala, nomor_wa, paket_id, paket_nama, jumlah_kupon, jumlah_kaos, '
            . implode(', ', $ukuranColumns) . ', catatan)
         VALUES
            (:nama_madrasah, :alamat, :kode_kecamatan, :nama_kecamatan, :kode_kelurahan, :nama_kelurahan, :alamat_detail,
             :nama_kepala, :nomor_wa, :paket_id, :paket_nama, :jumlah_kupon, :jumlah_kaos, :'
            . implode(', :', $ukuranColumns) . ', :catatan)'
    );
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
        ':paket_id' => $data['paket_id'],
        ':paket_nama' => $data['paket_nama'],
        ':jumlah_kupon' => $data['jumlah_kupon'],
        ':jumlah_kaos' => $data['jumlah_kaos'],
        ':catatan' => $data['catatan'] !== '' ? $data['catatan'] : null,
    ];
    foreach ($ukuranColumns as $column) {
        $params[':' . $column] = (int) ($data[$column] ?? 0);
    }
    $stmt->execute($params);

    return (int) getDb()->lastInsertId();
}

function jalanSehatUrutanOptions(): array
{
    return [
        'terbaru' => 'Terbaru',
        'wilayah' => 'Kecamatan & Desa (A–Z)',
        'madrasah' => 'Nama Madrasah (A–Z)',
    ];
}

/**
 * @param array{paket?: string, kecamatan?: string, kelurahan?: string, urut?: string} $filters
 */
function loadJalanSehatPendaftaran(string $search = '', array $filters = []): array
{
    ensureJalanSehatSchema();
    $sql = 'SELECT * FROM jalan_sehat_pendaftaran WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (nama_madrasah LIKE :q OR nama_kepala LIKE :q OR alamat LIKE :q OR nomor_wa LIKE :q)';
        $params[':q'] = '%' . $search . '%';
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
    ensureJalanSehatSchema();
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
    ensureJalanSehatSchema();
    $stmt = getDb()->prepare('SELECT * FROM jalan_sehat_pendaftaran WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function deleteJalanSehatPendaftaran(int $id): bool
{
    ensureJalanSehatSchema();
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

function alamatDetailJalanSehat(array $row): string
{
    $detail = trim((string) ($row['alamat_detail'] ?? ''));

    return $detail !== '' ? $detail : trim((string) ($row['alamat'] ?? ''));
}

function isJalanSehatAdminLoggedIn(): bool
{
    return isMaarifAdminLoggedIn();
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
