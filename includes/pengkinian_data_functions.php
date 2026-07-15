<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function pengkinianDataTableName(): string
{
    ensurePengkinianDataSchema();

    return 'pengkinian_data_satuan';
}

function ensurePengkinianDataSchema(): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $pdo = getDb();
    $hasTable = (bool) $pdo->query("SHOW TABLES LIKE 'pengkinian_data_satuan'")->fetch();

    if (!$hasTable) {
        $sqlPath = dirname(__DIR__) . '/database/migration_pengkinian_data.sql';
        if (is_file($sqlPath)) {
            $pdo->exec((string) file_get_contents($sqlPath));
        } else {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS `pengkinian_data_satuan` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `npsn` varchar(20) NOT NULL,
                  `nama_satuan_pendidikan` varchar(200) NOT NULL,
                  `nama_kepala_sekolah` varchar(150) NOT NULL,
                  `nama_operator` varchar(150) NOT NULL,
                  `nomor_hp_kepsek` varchar(30) NOT NULL,
                  `nomor_hp_kepsek_norm` varchar(20) DEFAULT NULL,
                  `nomor_hp_operator` varchar(30) NOT NULL,
                  `nomor_hp_operator_norm` varchar(20) DEFAULT NULL,
                  `kode_provinsi` varchar(10) DEFAULT NULL,
                  `nama_provinsi` varchar(100) DEFAULT NULL,
                  `kode_kabupaten` varchar(10) DEFAULT NULL,
                  `nama_kabupaten` varchar(150) DEFAULT NULL,
                  `kode_kecamatan` varchar(10) DEFAULT NULL,
                  `nama_kecamatan` varchar(150) DEFAULT NULL,
                  `kode_kelurahan` varchar(20) DEFAULT NULL,
                  `nama_kelurahan` varchar(150) DEFAULT NULL,
                  `alamat_detail` text DEFAULT NULL,
                  `alamat_lengkap` text DEFAULT NULL,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uniq_npsn` (`npsn`),
                  KEY `idx_nama_satuan` (`nama_satuan_pendidikan`),
                  KEY `idx_kecamatan` (`nama_kecamatan`),
                  KEY `idx_hp_kepsek_norm` (`nomor_hp_kepsek_norm`),
                  KEY `idx_hp_operator_norm` (`nomor_hp_operator_norm`),
                  KEY `idx_updated_at` (`updated_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }
    } else {
        $columns = array_column(
            $pdo->query('SHOW COLUMNS FROM `pengkinian_data_satuan`')->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
        if (!in_array('npsn', $columns, true)) {
            $pdo->exec("ALTER TABLE `pengkinian_data_satuan` ADD COLUMN `npsn` varchar(20) NOT NULL DEFAULT '' AFTER `id`");
            $pdo->exec("UPDATE `pengkinian_data_satuan` SET `npsn` = CONCAT('TMP-', `id`) WHERE `npsn` = '' OR `npsn` IS NULL");
            $indexes = $pdo->query("SHOW INDEX FROM `pengkinian_data_satuan` WHERE Key_name = 'uniq_npsn'")->fetchAll();
            if ($indexes === []) {
                $pdo->exec('ALTER TABLE `pengkinian_data_satuan` ADD UNIQUE KEY `uniq_npsn` (`npsn`)');
            }
        }

        $additions = [
            'tempat_lahir' => '`tempat_lahir` varchar(100) DEFAULT NULL',
            'tanggal_lahir' => '`tanggal_lahir` date DEFAULT NULL',
            'niy_nip' => '`niy_nip` varchar(30) DEFAULT NULL',
            'jabatan' => '`jabatan` varchar(50) DEFAULT NULL',
            'jenjang' => '`jenjang` varchar(30) DEFAULT NULL',
            'tgl_tmt_sk' => '`tgl_tmt_sk` date DEFAULT NULL',
            'tgl_akhir_tmt_sk' => '`tgl_akhir_tmt_sk` date DEFAULT NULL',
            'file_sk_kepala' => '`file_sk_kepala` varchar(255) DEFAULT NULL',
            'status_sk_kepala' => '`status_sk_kepala` varchar(10) DEFAULT NULL',
        ];
        foreach ($additions as $col => $definition) {
            if (!in_array($col, $columns, true)) {
                $pdo->exec("ALTER TABLE `pengkinian_data_satuan` ADD COLUMN {$definition}");
                $columns[] = $col;
            }
        }
    }

    $done = true;
}

function pengkinianSkStorageDir(): string
{
    $dir = APP_ROOT . '/data/sk_kepala';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function pengkinianJabatanOptions(): array
{
    return ['Kepala Madrasah', 'Kepala Sekolah'];
}

function pengkinianJenjangOptions(): array
{
    return ['MI/SD', 'MTS/SMP', 'MA/SMA/SMK'];
}

function pengkinianNormalizeJenjang(string $jenjang): string
{
    $jenjang = trim($jenjang);
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

function pengkinianStatusSkOptions(): array
{
    return ['AKTIF', 'HABIS'];
}

function resolveStatusSkKepala(string $tglAkhir, string $selected): string
{
    if (in_array($selected, pengkinianStatusSkOptions(), true)) {
        return $selected;
    }

    if ($tglAkhir !== '' && strtotime($tglAkhir) !== false) {
        return strtotime($tglAkhir) >= strtotime('today') ? 'AKTIF' : 'HABIS';
    }

    return 'HABIS';
}

function getPengkinianSkRelativePath(int $id): ?string
{
    $row = getPengkinianDataById($id);

    return !empty($row['file_sk_kepala']) ? (string) $row['file_sk_kepala'] : null;
}

function streamPengkinianSkFile(int $id): void
{
    $relative = getPengkinianSkRelativePath($id);
    if ($relative === null) {
        http_response_code(404);
        exit('File tidak ditemukan.');
    }

    $path = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $relative), '/');
    if (!is_file($path)) {
        http_response_code(404);
        exit('File tidak ditemukan.');
    }

    $mime = mime_content_type($path) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($path) . '"');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    exit;
}

function handlePengkinianSkUpload(array $file, string $npsn, ?string $existingPath): array
{
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        if ($existingPath !== null && $existingPath !== '') {
            return ['error' => null, 'path' => $existingPath];
        }

        return ['error' => 'Upload scan SK terakhir wajib diisi.', 'path' => null];
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['error' => 'Gagal mengunggah file SK. Silakan coba lagi.', 'path' => null];
    }

    $maxBytes = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        return ['error' => 'Ukuran file SK maksimal 5 MB.', 'path' => null];
    }

    $original = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowed, true)) {
        return ['error' => 'Format file SK harus PDF, JPG, atau PNG.', 'path' => null];
    }

    $dir = pengkinianSkStorageDir();
    $filename = 'sk_' . preg_replace('/\D/', '', $npsn) . '_' . date('YmdHis') . '.' . $ext;
    $dest = $dir . '/' . $filename;
    $relative = 'data/sk_kepala/' . $filename;

    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $dest)) {
        return ['error' => 'Gagal menyimpan file SK.', 'path' => null];
    }

    if ($existingPath !== null && $existingPath !== '') {
        $oldPath = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $existingPath), '/');
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    return ['error' => null, 'path' => $relative];
}

function pengkinianDataDefaultForm(): array
{
    return array_merge([
        'npsn' => '',
        'nama_satuan_pendidikan' => '',
        'nama_kepala_sekolah' => '',
        'tempat_lahir' => '',
        'tanggal_lahir' => '',
        'niy_nip' => '',
        'jabatan' => '',
        'jenjang' => '',
        'nama_operator' => '',
        'nomor_hp_kepsek' => '',
        'nomor_hp_operator' => '',
        'kode_kecamatan' => '',
        'nama_kecamatan' => '',
        'kode_kelurahan' => '',
        'nama_kelurahan' => '',
        'alamat_detail' => '',
        'tgl_tmt_sk' => '',
        'tgl_akhir_tmt_sk' => '',
        'status_sk_kepala' => '',
    ], defaultWilayahMagelang());
}

function validateNomorHp(string $label, string $value): array
{
    $value = trim($value);
    if ($value === '') {
        return ['error' => "Field {$label} wajib diisi.", 'value' => ''];
    }

    $norm = normalizeNomorWa($value);
    if ($norm === '' || strlen($norm) < 10 || strlen($norm) > 15) {
        return ['error' => "Field {$label} tidak valid. Gunakan format nomor HP Indonesia.", 'value' => ''];
    }

    return ['error' => null, 'value' => $value, 'norm' => $norm];
}

function normalizeNpsn(string $value): string
{
    return preg_replace('/\D/', '', trim($value)) ?? '';
}

function validatePengkinianData(array $input, array $files = [], ?array $existingRow = null): array
{
    $errors = [];
    $data = pengkinianDataDefaultForm();

    $npsn = normalizeNpsn($input['npsn'] ?? '');
    if ($npsn === '') {
        $errors[] = 'Field NPSN wajib diisi.';
    } elseif (strlen($npsn) < 8 || strlen($npsn) > 10) {
        $errors[] = 'Field NPSN tidak valid. NPSN berisi 8–10 digit angka.';
    } else {
        $data['npsn'] = $npsn;
    }

    $textFields = [
        'nama_satuan_pendidikan' => 'Nama Satuan Pendidikan',
        'nama_kepala_sekolah' => 'Nama Kepala Sekolah',
        'tempat_lahir' => 'Tempat Lahir',
        'niy_nip' => 'NIY/NIP',
        'nama_operator' => 'Nama Operator',
    ];

    foreach ($textFields as $field => $label) {
        $value = trim($input[$field] ?? '');
        if ($value === '') {
            $errors[] = "Field {$label} wajib diisi.";
        } else {
            $data[$field] = $value;
        }
    }

    $tanggalLahir = trim($input['tanggal_lahir'] ?? '');
    if ($tanggalLahir === '') {
        $errors[] = 'Field Tanggal Lahir wajib diisi.';
    } elseif (strtotime($tanggalLahir) === false) {
        $errors[] = 'Field Tanggal Lahir tidak valid.';
    } else {
        $data['tanggal_lahir'] = date('Y-m-d', strtotime($tanggalLahir));
    }

    $jabatan = trim($input['jabatan'] ?? '');
    if ($jabatan === '' || !in_array($jabatan, pengkinianJabatanOptions(), true)) {
        $errors[] = 'Field Jabatan wajib dipilih.';
    } else {
        $data['jabatan'] = $jabatan;
    }

    $jenjang = pengkinianNormalizeJenjang($input['jenjang'] ?? '');
    if ($jenjang === '' || !in_array($jenjang, pengkinianJenjangOptions(), true)) {
        $errors[] = 'Field Jenjang wajib dipilih.';
    } else {
        $data['jenjang'] = $jenjang;
    }

    $hpKepsek = validateNomorHp('Nomor HP Kepala Sekolah', $input['nomor_hp_kepsek'] ?? '');
    if ($hpKepsek['error'] !== null) {
        $errors[] = $hpKepsek['error'];
    } else {
        $data['nomor_hp_kepsek'] = $hpKepsek['value'];
        $data['nomor_hp_kepsek_norm'] = $hpKepsek['norm'];
    }

    $hpOperator = validateNomorHp('Nomor HP Operator', $input['nomor_hp_operator'] ?? '');
    if ($hpOperator['error'] !== null) {
        $errors[] = $hpOperator['error'];
    } else {
        $data['nomor_hp_operator'] = $hpOperator['value'];
        $data['nomor_hp_operator_norm'] = $hpOperator['norm'];
    }

    if (($data['nomor_hp_kepsek_norm'] ?? '') !== '' && ($data['nomor_hp_operator_norm'] ?? '') !== ''
        && $data['nomor_hp_kepsek_norm'] === $data['nomor_hp_operator_norm']) {
        $errors[] = 'Nomor HP Kepala Sekolah dan Operator tidak boleh sama.';
    }

    $wilayah = defaultWilayahMagelang();
    $data['kode_provinsi'] = $wilayah['kode_provinsi'];
    $data['nama_provinsi'] = $wilayah['nama_provinsi'];
    $data['kode_kabupaten'] = $wilayah['kode_kabupaten'];
    $data['nama_kabupaten'] = $wilayah['nama_kabupaten'];

    $data['kode_kecamatan'] = trim($input['kode_kecamatan'] ?? '');
    $data['nama_kecamatan'] = trim($input['nama_kecamatan'] ?? '');
    $data['kode_kelurahan'] = trim($input['kode_kelurahan'] ?? '');
    $data['nama_kelurahan'] = trim($input['nama_kelurahan'] ?? '');
    $data['alamat_detail'] = trim($input['alamat_detail'] ?? '');

    if ($data['kode_kecamatan'] === '' || $data['nama_kecamatan'] === '') {
        $errors[] = 'Field Kecamatan wajib dipilih.';
    }
    if ($data['kode_kelurahan'] === '' || $data['nama_kelurahan'] === '') {
        $errors[] = 'Field Desa/Kelurahan wajib dipilih.';
    }
    if ($data['alamat_detail'] === '') {
        $errors[] = 'Field Alamat Detail wajib diisi.';
    }

    $data['alamat_lengkap'] = buildAlamatLembaga([
        'alamat_detail' => $data['alamat_detail'],
        'nama_kelurahan' => $data['nama_kelurahan'],
        'nama_kecamatan' => $data['nama_kecamatan'],
        'nama_kabupaten' => $data['nama_kabupaten'],
        'nama_provinsi' => $data['nama_provinsi'],
    ]);

    $tglTmt = trim($input['tgl_tmt_sk'] ?? '');
    $tglAkhir = trim($input['tgl_akhir_tmt_sk'] ?? '');
    if ($tglTmt === '') {
        $errors[] = 'Field Tgl TMT SK wajib diisi.';
    } elseif (strtotime($tglTmt) === false) {
        $errors[] = 'Field Tgl TMT SK tidak valid.';
    } else {
        $data['tgl_tmt_sk'] = date('Y-m-d', strtotime($tglTmt));
    }

    if ($tglAkhir === '') {
        $errors[] = 'Field Tgl Akhir TMT SK wajib diisi.';
    } elseif (strtotime($tglAkhir) === false) {
        $errors[] = 'Field Tgl Akhir TMT SK tidak valid.';
    } else {
        $data['tgl_akhir_tmt_sk'] = date('Y-m-d', strtotime($tglAkhir));
    }

    if (($data['tgl_tmt_sk'] ?? '') !== '' && ($data['tgl_akhir_tmt_sk'] ?? '') !== ''
        && strtotime($data['tgl_akhir_tmt_sk']) < strtotime($data['tgl_tmt_sk'])) {
        $errors[] = 'Tgl Akhir TMT SK tidak boleh lebih awal dari Tgl TMT SK.';
    }

    $statusInput = strtoupper(trim($input['status_sk_kepala'] ?? ''));
    if ($statusInput === '' || !in_array($statusInput, pengkinianStatusSkOptions(), true)) {
        $errors[] = 'Field Status SK Kepala wajib dipilih.';
    } else {
        $data['status_sk_kepala'] = $statusInput;
    }

    $existingFile = $existingRow['file_sk_kepala'] ?? null;
    $upload = handlePengkinianSkUpload($files['file_sk_kepala'] ?? [], $data['npsn'] ?? '', $existingFile);
    if ($upload['error'] !== null) {
        $errors[] = $upload['error'];
    } else {
        $data['file_sk_kepala'] = $upload['path'];
    }

    return ['errors' => $errors, 'data' => $data];
}

function pengkinianDataMatchKey(string $namaSatuan, string $kodeKelurahan): string
{
    $nama = preg_replace('/\s+/', ' ', mb_strtolower(trim($namaSatuan), 'UTF-8')) ?? '';

    return $nama . '|' . trim($kodeKelurahan);
}

function findPengkinianDataId(array $data): ?int
{
    $pdo = getDb();
    $table = pengkinianDataTableName();
    $npsn = trim($data['npsn'] ?? '');

    if ($npsn !== '') {
        $stmt = $pdo->prepare("SELECT id FROM `{$table}` WHERE npsn = :npsn LIMIT 1");
        $stmt->execute([':npsn' => $npsn]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }
    }

    $key = pengkinianDataMatchKey(
        $data['nama_satuan_pendidikan'],
        $data['kode_kelurahan']
    );

    $stmt = $pdo->query("SELECT id, nama_satuan_pendidikan, kode_kelurahan FROM `{$table}`");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rowKey = pengkinianDataMatchKey(
            $row['nama_satuan_pendidikan'] ?? '',
            $row['kode_kelurahan'] ?? ''
        );
        if ($rowKey === $key) {
            return (int) $row['id'];
        }
    }

    return null;
}

function savePengkinianData(array $data): array
{
    $pdo = getDb();
    $table = pengkinianDataTableName();
    $existingId = findPengkinianDataId($data);

    $row = [
        'npsn' => $data['npsn'],
        'nama_satuan_pendidikan' => $data['nama_satuan_pendidikan'],
        'nama_kepala_sekolah' => $data['nama_kepala_sekolah'],
        'tempat_lahir' => $data['tempat_lahir'],
        'tanggal_lahir' => $data['tanggal_lahir'],
        'niy_nip' => $data['niy_nip'],
        'jabatan' => $data['jabatan'],
        'jenjang' => $data['jenjang'],
        'nama_operator' => $data['nama_operator'],
        'nomor_hp_kepsek' => $data['nomor_hp_kepsek'],
        'nomor_hp_kepsek_norm' => $data['nomor_hp_kepsek_norm'],
        'nomor_hp_operator' => $data['nomor_hp_operator'],
        'nomor_hp_operator_norm' => $data['nomor_hp_operator_norm'],
        'kode_provinsi' => $data['kode_provinsi'],
        'nama_provinsi' => $data['nama_provinsi'],
        'kode_kabupaten' => $data['kode_kabupaten'],
        'nama_kabupaten' => $data['nama_kabupaten'],
        'kode_kecamatan' => $data['kode_kecamatan'],
        'nama_kecamatan' => $data['nama_kecamatan'],
        'kode_kelurahan' => $data['kode_kelurahan'],
        'nama_kelurahan' => $data['nama_kelurahan'],
        'alamat_detail' => $data['alamat_detail'],
        'alamat_lengkap' => $data['alamat_lengkap'],
        'tgl_tmt_sk' => $data['tgl_tmt_sk'],
        'tgl_akhir_tmt_sk' => $data['tgl_akhir_tmt_sk'],
        'file_sk_kepala' => $data['file_sk_kepala'],
        'status_sk_kepala' => $data['status_sk_kepala'],
    ];

    if ($existingId !== null) {
        $sets = [];
        $params = [':id' => $existingId];
        foreach ($row as $col => $val) {
            $sets[] = "`{$col}` = :{$col}";
            $params[":{$col}"] = $val;
        }
        $sql = "UPDATE `{$table}` SET " . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ['id' => $existingId, 'updated' => true];
    }

    $cols = array_keys($row);
    $placeholders = array_map(static fn (string $c): string => ':' . $c, $cols);
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`, `', $cols) . '`) VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = $pdo->prepare($sql);
    $params = [];
    foreach ($row as $col => $val) {
        $params[':' . $col] = $val;
    }
    $stmt->execute($params);

    return ['id' => (int) $pdo->lastInsertId(), 'updated' => false];
}

function loadPengkinianData(string $search = '', array $filters = []): array
{
    $pdo = getDb();
    $table = pengkinianDataTableName();

    $sql = "SELECT id, npsn, nama_satuan_pendidikan, nama_kepala_sekolah, tempat_lahir,
                   DATE_FORMAT(tanggal_lahir, '%Y-%m-%d') AS tanggal_lahir,
                   niy_nip, jabatan, jenjang, nama_operator,
                   nomor_hp_kepsek, nomor_hp_operator,
                   kode_kecamatan, nama_kecamatan, kode_kelurahan, nama_kelurahan,
                   alamat_detail, alamat_lengkap,
                   DATE_FORMAT(tgl_tmt_sk, '%Y-%m-%d') AS tgl_tmt_sk,
                   DATE_FORMAT(tgl_akhir_tmt_sk, '%Y-%m-%d') AS tgl_akhir_tmt_sk,
                   file_sk_kepala, status_sk_kepala,
                   DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                   DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') AS updated_at
            FROM `{$table}` WHERE 1=1";

    $params = [];

    if ($search !== '') {
        $sql .= ' AND (npsn LIKE :q OR nama_satuan_pendidikan LIKE :q OR nama_kepala_sekolah LIKE :q
                  OR niy_nip LIKE :q OR nama_operator LIKE :q OR nomor_hp_kepsek LIKE :q OR nomor_hp_operator LIKE :q
                  OR alamat_lengkap LIKE :q OR nama_kecamatan LIKE :q OR jenjang LIKE :q
                  OR status_sk_kepala LIKE :q)';
        $params[':q'] = '%' . $search . '%';
    }

    if (!empty($filters['kecamatan'])) {
        $sql .= ' AND nama_kecamatan = :kecamatan';
        $params[':kecamatan'] = $filters['kecamatan'];
    }

    $sql .= ' ORDER BY updated_at DESC, id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPengkinianDataById(int $id): ?array
{
    $pdo = getDb();
    $table = pengkinianDataTableName();

    $stmt = $pdo->prepare(
        "SELECT id, npsn, nama_satuan_pendidikan, nama_kepala_sekolah, tempat_lahir,
                DATE_FORMAT(tanggal_lahir, '%Y-%m-%d') AS tanggal_lahir,
                niy_nip, jabatan, jenjang, nama_operator,
                nomor_hp_kepsek, nomor_hp_operator,
                kode_provinsi, nama_provinsi, kode_kabupaten, nama_kabupaten,
                kode_kecamatan, nama_kecamatan, kode_kelurahan, nama_kelurahan,
                alamat_detail, alamat_lengkap,
                DATE_FORMAT(tgl_tmt_sk, '%Y-%m-%d') AS tgl_tmt_sk,
                DATE_FORMAT(tgl_akhir_tmt_sk, '%Y-%m-%d') AS tgl_akhir_tmt_sk,
                file_sk_kepala, status_sk_kepala,
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') AS updated_at
         FROM `{$table}` WHERE id = :id LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function deletePengkinianData(int $id): bool
{
    $row = getPengkinianDataById($id);
    $pdo = getDb();
    $table = pengkinianDataTableName();
    $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $deleted = $stmt->rowCount() > 0;

    if ($deleted && !empty($row['file_sk_kepala'])) {
        $path = APP_ROOT . '/' . ltrim(str_replace('\\', '/', (string) $row['file_sk_kepala']), '/');
        if (is_file($path)) {
            @unlink($path);
        }
    }

    return $deleted;
}

function getPengkinianDashboardStats(array $rows): array
{
    $kecamatan = [];

    foreach ($rows as $row) {
        $kec = trim($row['nama_kecamatan'] ?? '');
        if ($kec !== '') {
            $kecamatan[$kec] = ($kecamatan[$kec] ?? 0) + 1;
        }
    }

    arsort($kecamatan);

    return [
        'total' => count($rows),
        'kecamatan' => $kecamatan,
    ];
}

function pengkinianKecamatanOptions(array $rows): array
{
    $options = [];
    foreach ($rows as $row) {
        $kec = trim($row['nama_kecamatan'] ?? '');
        if ($kec !== '') {
            $options[$kec] = true;
        }
    }
    ksort($options);

    return array_keys($options);
}

function isPengkinianAdminLoggedIn(): bool
{
    return !empty($_SESSION['pengkinian_data_admin']);
}

function exportPengkinianCsv(array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pengkinian_data_satuan_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        return;
    }

    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, [
        'No',
        'NPSN',
        'Nama Satuan Pendidikan',
        'Nama Kepala Sekolah',
        'Tempat Lahir',
        'Tanggal Lahir',
        'NIY/NIP',
        'Jabatan',
        'Jenjang',
        'Nama Operator',
        'HP Kepsek',
        'HP Operator',
        'Kecamatan',
        'Desa/Kelurahan',
        'Alamat Detail',
        'Alamat Lengkap',
        'Tgl TMT SK',
        'Tgl Akhir TMT SK',
        'Status SK Kepala',
        'Dibuat',
        'Diperbarui',
    ]);

    foreach ($rows as $i => $row) {
        fputcsv($out, [
            $i + 1,
            $row['npsn'] ?? '',
            $row['nama_satuan_pendidikan'] ?? '',
            $row['nama_kepala_sekolah'] ?? '',
            $row['tempat_lahir'] ?? '',
            $row['tanggal_lahir'] ?? '',
            $row['niy_nip'] ?? '',
            $row['jabatan'] ?? '',
            $row['jenjang'] ?? '',
            $row['nama_operator'] ?? '',
            $row['nomor_hp_kepsek'] ?? '',
            $row['nomor_hp_operator'] ?? '',
            $row['nama_kecamatan'] ?? '',
            $row['nama_kelurahan'] ?? '',
            $row['alamat_detail'] ?? '',
            $row['alamat_lengkap'] ?? '',
            $row['tgl_tmt_sk'] ?? '',
            $row['tgl_akhir_tmt_sk'] ?? '',
            $row['status_sk_kepala'] ?? '',
            $row['created_at'] ?? '',
            $row['updated_at'] ?? '',
        ]);
    }

    fclose($out);
    exit;
}
