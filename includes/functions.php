<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function validatePendaftaran(array $input, ?int $excludeId = null, bool $simpleWilayah = false): array
{
    $errors = [];
    $data = [];

    $required = [
        'nama' => 'Nama',
        'nomor_wa' => 'Nomor WA',
        'tempat_lahir' => 'Tempat Lahir',
        'tanggal_lahir' => 'Tanggal Lahir',
        'jenis_lembaga' => 'Jenis Lembaga',
        'asal_lembaga' => 'Asal Lembaga',
    ];

    foreach ($required as $field => $label) {
        $value = trim($input[$field] ?? '');
        if ($value === '') {
            $errors[] = "Field {$label} wajib diisi.";
            continue;
        }
        $data[$field] = $value;
    }

    $data['nip'] = trim($input['nip'] ?? '');

    $jabatanResult = resolveChoiceField($input, 'jabatan_pilihan', jabatanOptions(), 'jabatan_lainnya', 'Jabatan');
    if ($jabatanResult['error'] !== null) {
        $errors[] = $jabatanResult['error'];
    } else {
        $data['jabatan'] = $jabatanResult['value'];
    }

    $transportResult = resolveChoiceField($input, 'transportasi_pilihan', transportasiOptions(), 'transportasi_lainnya', 'Alat Transportasi');
    if ($transportResult['error'] !== null) {
        $errors[] = $transportResult['error'];
    } else {
        $data['alat_transportasi'] = $transportResult['value'];
    }

    $jenis = trim($input['jenis_lembaga'] ?? '');
    $jenisValid = false;
    foreach (jenisLembagaOptions() as $opt) {
        if (strcasecmp($jenis, $opt) === 0) {
            $data['jenis_lembaga'] = $opt === 'Lainnya' ? 'Lainnya' : strtoupper($opt);
            $jenisValid = true;
            break;
        }
    }
    if (!$jenisValid) {
        $errors[] = 'Field Jenis Lembaga wajib dipilih.';
    }

    $wilayahFields = $simpleWilayah
        ? [
            'kode_kecamatan' => 'Kecamatan',
            'nama_kecamatan' => 'Kecamatan',
            'kode_kelurahan' => 'Desa/Kelurahan',
            'nama_kelurahan' => 'Desa/Kelurahan',
        ]
        : [
            'kode_provinsi' => 'Provinsi',
            'nama_provinsi' => 'Provinsi',
            'kode_kabupaten' => 'Kabupaten/Kota',
            'nama_kabupaten' => 'Kabupaten/Kota',
            'kode_kecamatan' => 'Kecamatan',
            'nama_kecamatan' => 'Kecamatan',
            'kode_kelurahan' => 'Kelurahan/Desa',
            'nama_kelurahan' => 'Kelurahan/Desa',
        ];

    if ($simpleWilayah) {
        foreach (defaultWilayahMagelang() as $key => $value) {
            $data[$key] = $value;
        }
    }

    foreach ($wilayahFields as $field => $label) {
        $value = trim($input[$field] ?? '');
        if ($value === '') {
            $errors[] = "Field {$label} wajib dipilih.";
            continue;
        }
        $data[$field] = $value;
    }

    $data['alamat_detail'] = trim($input['alamat_detail'] ?? '');
    if ($data['alamat_detail'] === '') {
        $errors[] = 'Field Alamat Detail (jalan, RT/RW, dll.) wajib diisi.';
    }

    $data['alamat_lembaga'] = buildAlamatLembaga($data);

    if (!empty($data['nomor_wa']) && !preg_match('/^[0-9+\-\s()]{8,20}$/', $data['nomor_wa'])) {
        $errors[] = 'Nomor WA tidak valid.';
    } elseif (!empty($data['nomor_wa']) && nomorWaSudahTerdaftar($data['nomor_wa'], $excludeId)) {
        $errors[] = 'Nomor WA sudah terdaftar. Satu nomor hanya dapat mendaftar sekali.';
    }

    if (!empty($data['tanggal_lahir'])) {
        $date = DateTime::createFromFormat('Y-m-d', $data['tanggal_lahir']);
        if (!$date || $date->format('Y-m-d') !== $data['tanggal_lahir']) {
            $errors[] = 'Tanggal lahir tidak valid.';
        }
    }

    $data['jabatan_pilihan'] = trim($input['jabatan_pilihan'] ?? '');
    $data['jabatan_lainnya'] = trim($input['jabatan_lainnya'] ?? '');
    $data['transportasi_pilihan'] = trim($input['transportasi_pilihan'] ?? '');
    $data['transportasi_lainnya'] = trim($input['transportasi_lainnya'] ?? '');

    return ['errors' => $errors, 'data' => $data];
}

function normalizeNomorWa(string $nomor): string
{
    $digits = preg_replace('/\D/', '', trim($nomor)) ?? '';
    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '62')) {
        $digits = '0' . substr($digits, 2);
    } elseif (!str_starts_with($digits, '0') && str_starts_with($digits, '8')) {
        $digits = '0' . $digits;
    }

    return $digits;
}

function nomorWaSudahTerdaftar(string $nomorWa, ?int $excludeId = null): bool
{
    $target = normalizeNomorWa($nomorWa);
    if ($target === '') {
        return false;
    }

    $pdo = getDb();
    $sql = 'SELECT id FROM peserta_rakerdinma WHERE nomor_wa_norm = :norm';
    $params = [':norm' => $target];

    if ($excludeId !== null && $excludeId > 0) {
        $sql .= ' AND id != :id';
        $params[':id'] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch();
    } catch (PDOException) {
        // Fallback jika kolom nomor_wa_norm belum dimigrasi
        $sql = 'SELECT id, nomor_wa FROM peserta_rakerdinma';
        $params = [];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' WHERE id != :id';
            $params[':id'] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        foreach ($stmt->fetchAll() as $row) {
            if (normalizeNomorWa($row['nomor_wa'] ?? '') === $target) {
                return true;
            }
        }

        return false;
    }
}

function buildAlamatLembaga(array $data): string
{
    $parts = [$data['alamat_detail']];

    if (!empty($data['nama_kelurahan'])) {
        $parts[] = 'Kel. ' . $data['nama_kelurahan'];
    }
    if (!empty($data['nama_kecamatan'])) {
        $parts[] = 'Kec. ' . $data['nama_kecamatan'];
    }
    if (!empty($data['nama_kabupaten'])) {
        $parts[] = $data['nama_kabupaten'];
    }
    if (!empty($data['nama_provinsi'])) {
        $parts[] = $data['nama_provinsi'];
    }

    return implode(', ', array_filter($parts));
}

function loadPeserta(string $search = '', array $filters = []): array
{
    $pdo = getDb();

    $sql = 'SELECT id, nama, nip, nomor_wa, tempat_lahir,
                   DATE_FORMAT(tanggal_lahir, "%Y-%m-%d") AS tanggal_lahir,
                   jabatan, jenis_lembaga, asal_lembaga, alamat_lembaga,
                   kode_provinsi, nama_provinsi, kode_kabupaten, nama_kabupaten,
                   kode_kecamatan, nama_kecamatan, kode_kelurahan, nama_kelurahan,
                   alamat_detail, alat_transportasi,
                   DATE_FORMAT(created_at, "%Y-%m-%d %H:%i:%s") AS created_at
            FROM peserta_rakerdinma WHERE 1=1';

    $params = [];

    if ($search !== '') {
        $sql .= ' AND (nama LIKE :q OR nip LIKE :q OR nomor_wa LIKE :q
                  OR asal_lembaga LIKE :q OR jabatan LIKE :q
                  OR nama_provinsi LIKE :q OR nama_kabupaten LIKE :q
                  OR nama_kecamatan LIKE :q OR nama_kelurahan LIKE :q
                  OR alamat_detail LIKE :q OR alamat_lembaga LIKE :q)';
        $params[':q'] = '%' . $search . '%';
    }

    if (!empty($filters['kecamatan'])) {
        $sql .= ' AND nama_kecamatan = :kecamatan';
        $params[':kecamatan'] = $filters['kecamatan'];
    }

    if (!empty($filters['jabatan'])) {
        $sql .= ' AND jabatan = :jabatan';
        $params[':jabatan'] = $filters['jabatan'];
    }

    if (!empty($filters['transportasi'])) {
        $sql .= ' AND alat_transportasi = :transportasi';
        $params[':transportasi'] = $filters['transportasi'];
    }

    if (!empty($filters['jenis_lembaga'])) {
        $sql .= ' AND jenis_lembaga = :jenis_lembaga';
        $params[':jenis_lembaga'] = $filters['jenis_lembaga'];
    }

    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function getPesertaById(int $id): ?array
{
    $pdo = getDb();
    $stmt = $pdo->prepare(
        'SELECT id, nama, nip, nomor_wa, tempat_lahir,
                DATE_FORMAT(tanggal_lahir, "%Y-%m-%d") AS tanggal_lahir,
                jabatan, jenis_lembaga, asal_lembaga, alamat_lembaga,
                kode_provinsi, nama_provinsi, kode_kabupaten, nama_kabupaten,
                kode_kecamatan, nama_kecamatan, kode_kelurahan, nama_kelurahan,
                alamat_detail, alat_transportasi,
                DATE_FORMAT(created_at, "%Y-%m-%d %H:%i:%s") AS created_at
         FROM peserta_rakerdinma WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function updatePeserta(int $id, array $data): bool
{
    $pdo = getDb();

    $stmt = $pdo->prepare(
        'UPDATE peserta_rakerdinma SET
            nama = :nama, nip = :nip, nomor_wa = :nomor_wa, nomor_wa_norm = :nomor_wa_norm,
            tempat_lahir = :tempat_lahir, tanggal_lahir = :tanggal_lahir,
            jabatan = :jabatan, jenis_lembaga = :jenis_lembaga, asal_lembaga = :asal_lembaga,
            alamat_lembaga = :alamat_lembaga,
            kode_provinsi = :kode_provinsi, nama_provinsi = :nama_provinsi,
            kode_kabupaten = :kode_kabupaten, nama_kabupaten = :nama_kabupaten,
            kode_kecamatan = :kode_kecamatan, nama_kecamatan = :nama_kecamatan,
            kode_kelurahan = :kode_kelurahan, nama_kelurahan = :nama_kelurahan,
            alamat_detail = :alamat_detail, alat_transportasi = :alat_transportasi
         WHERE id = :id'
    );

    $norm = normalizeNomorWa($data['nomor_wa']);

    try {
        return $stmt->execute([
            ':id' => $id,
            ':nama' => $data['nama'],
            ':nip' => $data['nip'] !== '' ? $data['nip'] : null,
            ':nomor_wa' => $data['nomor_wa'],
            ':nomor_wa_norm' => $norm !== '' ? $norm : null,
            ':tempat_lahir' => $data['tempat_lahir'],
            ':tanggal_lahir' => $data['tanggal_lahir'],
            ':jabatan' => $data['jabatan'],
            ':jenis_lembaga' => $data['jenis_lembaga'],
            ':asal_lembaga' => $data['asal_lembaga'],
            ':alamat_lembaga' => $data['alamat_lembaga'],
            ':kode_provinsi' => $data['kode_provinsi'],
            ':nama_provinsi' => $data['nama_provinsi'],
            ':kode_kabupaten' => $data['kode_kabupaten'],
            ':nama_kabupaten' => $data['nama_kabupaten'],
            ':kode_kecamatan' => $data['kode_kecamatan'],
            ':nama_kecamatan' => $data['nama_kecamatan'],
            ':kode_kelurahan' => $data['kode_kelurahan'],
            ':nama_kelurahan' => $data['nama_kelurahan'],
            ':alamat_detail' => $data['alamat_detail'],
            ':alat_transportasi' => $data['alat_transportasi'],
        ]);
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return false;
        }

        throw $e;
    }
}

function defaultWilayahMagelang(): array
{
    return [
        'kode_provinsi' => '33',
        'nama_provinsi' => 'Jawa Tengah',
        'kode_kabupaten' => '33.08',
        'nama_kabupaten' => 'Kabupaten Magelang',
    ];
}

function jenisLembagaOptions(): array
{
    return ['MI', 'MTS', 'MA', 'SD', 'SMP', 'SMK', 'SMA', 'SLB', 'Pengurus LP Maarif MWC', 'Lainnya'];
}

function jabatanOptions(): array
{
    return ['Kepala', 'Guru', 'Lainnya'];
}

function transportasiOptions(): array
{
    return ['Sepeda Motor', 'Mobil', 'Lainnya'];
}

function resolveChoiceField(array $input, string $selectField, array $options, string $otherField, string $label): array
{
    $choice = trim($input[$selectField] ?? '');
    if ($choice === '' || !in_array($choice, $options, true)) {
        return ['error' => "Field {$label} wajib dipilih.", 'value' => ''];
    }

    if ($choice === 'Lainnya') {
        $custom = trim($input[$otherField] ?? '');
        if ($custom === '') {
            return ['error' => "Field {$label} (lainnya) wajib diisi.", 'value' => ''];
        }

        return ['error' => null, 'value' => $custom];
    }

    return ['error' => null, 'value' => $choice];
}

function choiceFieldFormState(string $value, array $options): array
{
    $standard = array_values(array_filter($options, static fn (string $opt): bool => $opt !== 'Lainnya'));

    if (in_array($value, $standard, true)) {
        return ['pilihan' => $value, 'lainnya' => ''];
    }

    if ($value === '') {
        return ['pilihan' => '', 'lainnya' => ''];
    }

    return ['pilihan' => 'Lainnya', 'lainnya' => $value];
}

function normalizeJabatan(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'Lainnya';
    }

    if (in_array($value, ['Kepala', 'Guru'], true)) {
        return $value;
    }

    $upper = strtoupper($value);

    // Wakil kepala / wakasek bukan kategori Kepala — simpan teks asli (Lainnya)
    if (preg_match('/WAKASEK|WAKIL|\bWAKA\b/', $upper)) {
        return $value;
    }

    if (preg_match('/\b(GURU|GMP)\b/', $upper) && !preg_match('/KEPALA|KAMAD|SEKOLAH|MADRASAH/', $upper)) {
        return 'Guru';
    }

    if (preg_match('/KEPALA|KAMAD|KEPADA/', $upper)) {
        return 'Kepala';
    }

    return $value;
}

function normalizeTransportasi(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'Lainnya';
    }

    if (in_array($value, ['Sepeda Motor', 'Mobil'], true)) {
        return $value;
    }

    $lower = strtolower($value);

    if (preg_match('/motor|mtr|mtor|mator|sepeda/', $lower)) {
        return 'Sepeda Motor';
    }

    if (preg_match('/mobil|bus|angkot|pick\s?up|minibus/', $lower)) {
        return 'Mobil';
    }

    return $value;
}

function parseJenisLembaga(string $asal): string
{
    $asal = trim($asal);
    if ($asal === '') {
        return 'Lainnya';
    }

    $parts = preg_split('/[\s\.]+/', $asal);
    $token = strtoupper(rtrim($parts[0] ?? '', '.'));
    $token = preg_replace('/[^A-Z]/', '', $token) ?? $token;

    if ($token === 'MIS') {
        return 'MI';
    }

    if (preg_match('/\bLP\b.*\bMAARIF\b|\bMAARIF\b.*\bLP\b/i', $asal)) {
        return 'PENGURUS LP MAARIF MWC';
    }

    foreach (['SMP', 'SMK', 'SMA', 'MTS', 'SLB', 'MI', 'MA', 'SD'] as $type) {
        if ($token === $type) {
            return $type;
        }
    }

    return 'Lainnya';
}

function resolveJenisLembaga(string $asal, string $explicit = ''): string
{
    $explicit = trim($explicit);
    if ($explicit !== '') {
        foreach (jenisLembagaOptions() as $opt) {
            if (strcasecmp($explicit, $opt) === 0) {
                return $opt === 'Lainnya' ? 'Lainnya' : strtoupper($opt);
            }
        }
    }

    return parseJenisLembaga($asal);
}

function hitungUmur(string $tanggalLahir): ?int
{
    $date = DateTime::createFromFormat('Y-m-d', $tanggalLahir);
    if (!$date) {
        return null;
    }

    return (int) $date->diff(new DateTime('today'))->y;
}

function kelompokUmur(?int $umur): string
{
    if ($umur === null) {
        return 'Tidak diketahui';
    }
    if ($umur < 30) {
        return '< 30 tahun';
    }
    if ($umur <= 40) {
        return '30–40 tahun';
    }
    if ($umur <= 50) {
        return '41–50 tahun';
    }
    if ($umur <= 60) {
        return '51–60 tahun';
    }

    return '> 60 tahun';
}

function getFilterOptions(): array
{
    $pdo = getDb();

    $fetchDistinct = static function (string $column) use ($pdo): array {
        $stmt = $pdo->query(
            "SELECT DISTINCT {$column} AS val FROM peserta_rakerdinma
             WHERE {$column} IS NOT NULL AND {$column} != ''
             ORDER BY val ASC"
        );

        return array_column($stmt->fetchAll(), 'val');
    };

    return [
        'kecamatan' => $fetchDistinct('nama_kecamatan'),
        'jabatan' => array_values(array_unique(array_merge(
            ['Kepala', 'Guru'],
            $fetchDistinct('jabatan')
        ))),
        'transportasi' => array_values(array_unique(array_merge(
            ['Sepeda Motor', 'Mobil'],
            $fetchDistinct('alat_transportasi')
        ))),
        'jenis_lembaga' => $fetchDistinct('jenis_lembaga'),
    ];
}

function getDashboardStats(array $peserta): array
{
    $kecamatan = [];
    $jabatan = [];
    $transportasi = [];
    $umur = [];
    $lembaga = [];

    foreach ($peserta as $row) {
        $kec = trim($row['nama_kecamatan'] ?? '');
        if ($kec !== '') {
            $kecamatan[$kec] = ($kecamatan[$kec] ?? 0) + 1;
        }

        $jab = trim($row['jabatan'] ?? '');
        if ($jab !== '') {
            $jabatan[$jab] = ($jabatan[$jab] ?? 0) + 1;
        }

        $trans = trim($row['alat_transportasi'] ?? '');
        if ($trans !== '') {
            $transportasi[$trans] = ($transportasi[$trans] ?? 0) + 1;
        }

        $group = kelompokUmur(hitungUmur($row['tanggal_lahir'] ?? ''));
        $umur[$group] = ($umur[$group] ?? 0) + 1;

        $jenis = trim($row['jenis_lembaga'] ?? '');
        if ($jenis === '') {
            $jenis = parseJenisLembaga($row['asal_lembaga'] ?? '');
        }
        $lembaga[$jenis] = ($lembaga[$jenis] ?? 0) + 1;
    }

    arsort($kecamatan);
    arsort($jabatan);
    arsort($transportasi);
    arsort($lembaga);

    $umurOrder = ['< 30 tahun', '30–40 tahun', '41–50 tahun', '51–60 tahun', '> 60 tahun', 'Tidak diketahui'];
    $umurSorted = [];
    foreach ($umurOrder as $key) {
        if (isset($umur[$key])) {
            $umurSorted[$key] = $umur[$key];
        }
    }

    return [
        'total' => count($peserta),
        'kecamatan' => $kecamatan,
        'jabatan' => $jabatan,
        'transportasi' => $transportasi,
        'umur' => $umurSorted,
        'lembaga' => $lembaga,
    ];
}

function pesertaFormDefaults(?array $row = null): array
{
    $jabatanState = choiceFieldFormState($row['jabatan'] ?? '', jabatanOptions());
    $transportState = choiceFieldFormState($row['alat_transportasi'] ?? '', transportasiOptions());

    return [
        'nama' => $row['nama'] ?? '',
        'nip' => $row['nip'] ?? '',
        'nomor_wa' => $row['nomor_wa'] ?? '',
        'tempat_lahir' => $row['tempat_lahir'] ?? '',
        'tanggal_lahir' => $row['tanggal_lahir'] ?? '',
        'jabatan' => $row['jabatan'] ?? '',
        'jabatan_pilihan' => $jabatanState['pilihan'],
        'jabatan_lainnya' => $jabatanState['lainnya'],
        'jenis_lembaga' => $row['jenis_lembaga'] ?? '',
        'asal_lembaga' => $row['asal_lembaga'] ?? '',
        'kode_provinsi' => $row['kode_provinsi'] ?? '',
        'nama_provinsi' => $row['nama_provinsi'] ?? '',
        'kode_kabupaten' => $row['kode_kabupaten'] ?? '',
        'nama_kabupaten' => $row['nama_kabupaten'] ?? '',
        'kode_kecamatan' => $row['kode_kecamatan'] ?? '',
        'nama_kecamatan' => $row['nama_kecamatan'] ?? '',
        'kode_kelurahan' => $row['kode_kelurahan'] ?? '',
        'nama_kelurahan' => $row['nama_kelurahan'] ?? '',
        'alamat_detail' => $row['alamat_detail'] ?? '',
        'alat_transportasi' => $row['alat_transportasi'] ?? '',
        'transportasi_pilihan' => $transportState['pilihan'],
        'transportasi_lainnya' => $transportState['lainnya'],
    ];
}

function addPeserta(array $data): bool
{
    if (nomorWaSudahTerdaftar($data['nomor_wa'])) {
        return false;
    }

    $pdo = getDb();
    $norm = normalizeNomorWa($data['nomor_wa']);

    $stmt = $pdo->prepare(
        'INSERT INTO peserta_rakerdinma
            (nama, nip, nomor_wa, nomor_wa_norm, tempat_lahir, tanggal_lahir, jabatan, jenis_lembaga, asal_lembaga,
             alamat_lembaga, kode_provinsi, nama_provinsi, kode_kabupaten, nama_kabupaten,
             kode_kecamatan, nama_kecamatan, kode_kelurahan, nama_kelurahan, alamat_detail,
             alat_transportasi)
         VALUES
            (:nama, :nip, :nomor_wa, :nomor_wa_norm, :tempat_lahir, :tanggal_lahir, :jabatan, :jenis_lembaga, :asal_lembaga,
             :alamat_lembaga, :kode_provinsi, :nama_provinsi, :kode_kabupaten, :nama_kabupaten,
             :kode_kecamatan, :nama_kecamatan, :kode_kelurahan, :nama_kelurahan, :alamat_detail,
             :alat_transportasi)'
    );

    try {
        return $stmt->execute([
            ':nama' => $data['nama'],
            ':nip' => $data['nip'] !== '' ? $data['nip'] : null,
            ':nomor_wa' => $data['nomor_wa'],
            ':nomor_wa_norm' => $norm !== '' ? $norm : null,
            ':tempat_lahir' => $data['tempat_lahir'],
            ':tanggal_lahir' => $data['tanggal_lahir'],
            ':jabatan' => $data['jabatan'],
            ':jenis_lembaga' => $data['jenis_lembaga'],
            ':asal_lembaga' => $data['asal_lembaga'],
            ':alamat_lembaga' => $data['alamat_lembaga'],
            ':kode_provinsi' => $data['kode_provinsi'],
            ':nama_provinsi' => $data['nama_provinsi'],
            ':kode_kabupaten' => $data['kode_kabupaten'],
            ':nama_kabupaten' => $data['nama_kabupaten'],
            ':kode_kecamatan' => $data['kode_kecamatan'],
            ':nama_kecamatan' => $data['nama_kecamatan'],
            ':kode_kelurahan' => $data['kode_kelurahan'],
            ':nama_kelurahan' => $data['nama_kelurahan'],
            ':alamat_detail' => $data['alamat_detail'],
            ':alat_transportasi' => $data['alat_transportasi'],
        ]);
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return false;
        }

        throw $e;
    }
}

function deletePeserta(string $id): bool
{
    if ($id === '' || !ctype_digit($id)) {
        return false;
    }

    $pdo = getDb();
    $stmt = $pdo->prepare('DELETE FROM peserta_rakerdinma WHERE id = :id');
    $stmt->execute([':id' => (int) $id]);

    return $stmt->rowCount() > 0;
}

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['rakerdinma_admin']);
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: ?login=1');
        exit;
    }
}

function exportXls(array $peserta): void
{
    $filename = 'peserta_rakerdinma_' . date('Y-m-d_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<tr>';
    foreach ([
        'No',
        'Tanggal Daftar',
        'Nama',
        'Nomor WA',
        'NIP',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Jenis Lembaga',
        'Jabatan',
        'Lembaga',
        'Alamat',
        'Kecamatan',
        'Kabupaten',
        'Transportasi',
    ] as $header) {
        echo '<th>' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr>';

    foreach ($peserta as $index => $row) {
        $jenis = $row['jenis_lembaga'] ?? parseJenisLembaga($row['asal_lembaga'] ?? '');
        $cells = [
            (string) ($index + 1),
            $row['created_at'] ?? '',
            $row['nama'] ?? '',
            $row['nomor_wa'] ?? '',
            $row['nip'] ?? '',
            $row['tempat_lahir'] ?? '',
            $row['tanggal_lahir'] ?? '',
            $jenis,
            $row['jabatan'] ?? '',
            $row['asal_lembaga'] ?? '',
            $row['alamat_detail'] ?? $row['alamat_lembaga'] ?? '',
            $row['nama_kecamatan'] ?? '',
            $row['nama_kabupaten'] ?? '',
            $row['alat_transportasi'] ?? '',
        ];

        echo '<tr>';
        foreach ($cells as $i => $cell) {
            $escaped = htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8');
            if (in_array($i, [3, 4], true)) {
                echo '<td style="mso-number-format:\'\\@\';">' . $escaped . '</td>';
            } else {
                echo '<td>' . $escaped . '</td>';
            }
        }
        echo '</tr>';
    }

    echo '</table></body></html>';
    exit;
}
