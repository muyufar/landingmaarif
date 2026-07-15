<?php

declare(strict_types=1);

require_once __DIR__ . '/pengkinian_data_functions.php';

const DIST_STATUS_PACKING = 'packing';
const DIST_STATUS_DELIVERY = 'delivery';
const DIST_STATUS_RECEIVE = 'receive';
const DIST_STATUS_DONE = 'done';

function ensureDistribusiLkpdSchema(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo = getDb();
    $has = (bool) $pdo->query("SHOW TABLES LIKE 'distribusi_lkpd_satuan'")->fetch();
    if (!$has) {
        $path = dirname(__DIR__) . '/database/migration_distribusi_lkpd.sql';
        if (is_file($path)) {
            $sql = (string) file_get_contents($path);
            $sql = preg_replace('/,\s*CONSTRAINT[^;]+/s', '', $sql) ?? $sql;
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if ($stmt !== '') {
                    $pdo->exec($stmt);
                }
            }
        }
    }

    $done = true;
}

function distribusiKelasFields(): array
{
    return [
        1 => 'kebutuhan_kelas_1',
        2 => 'kebutuhan_kelas_2',
        3 => 'kebutuhan_kelas_3',
        4 => 'kebutuhan_kelas_4',
        5 => 'kebutuhan_kelas_5',
        6 => 'kebutuhan_kelas_6',
    ];
}

function distribusiTerimaFields(): array
{
    return [
        1 => 'terima_kelas_1',
        2 => 'terima_kelas_2',
        3 => 'terima_kelas_3',
        4 => 'terima_kelas_4',
        5 => 'terima_kelas_5',
        6 => 'terima_kelas_6',
    ];
}

function distribusiStatusLabel(string $status): string
{
    return match ($status) {
        DIST_STATUS_PACKING => 'Packing',
        DIST_STATUS_DELIVERY => 'Delivery',
        DIST_STATUS_RECEIVE => 'Receive (Kurang)',
        DIST_STATUS_DONE => 'Done',
        default => $status,
    };
}

function distribusiStatusBadgeClass(string $status): string
{
    return match ($status) {
        DIST_STATUS_PACKING => 'bg-gray-100 text-gray-800',
        DIST_STATUS_DELIVERY => 'bg-blue-100 text-blue-800',
        DIST_STATUS_RECEIVE => 'bg-amber-100 text-amber-800',
        DIST_STATUS_DONE => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-800',
    };
}

function isDistribusiPetugasLoggedIn(): bool
{
    return !empty($_SESSION['distribusi_petugas_id']);
}

function isDistribusiSuperAdminLoggedIn(): bool
{
    return !empty($_SESSION['distribusi_super_admin']);
}

function getDistribusiPetugasSession(): ?array
{
    if (!isDistribusiPetugasLoggedIn()) {
        return null;
    }

    return getDistribusiPetugasById((int) $_SESSION['distribusi_petugas_id']);
}

function getDistribusiPetugasById(int $id): ?array
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT id, username, nama, role, aktif FROM distribusi_petugas WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function getDistribusiPetugasByUsername(string $username): ?array
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM distribusi_petugas WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => trim($username)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function loginDistribusiPetugas(string $username, string $password): bool
{
    $user = getDistribusiPetugasByUsername($username);
    if ($user === null || !(int) ($user['aktif'] ?? 0)) {
        return false;
    }
    if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
        return false;
    }

    $_SESSION['distribusi_petugas_id'] = (int) $user['id'];
    unset($_SESSION['distribusi_super_admin']);

    return true;
}

function loginDistribusiSuperAdmin(string $password): bool
{
    if (!password_verify($password, ADMIN_PASSWORD_HASH)) {
        return false;
    }

    $_SESSION['distribusi_super_admin'] = true;
    unset($_SESSION['distribusi_petugas_id']);

    return true;
}

function logoutDistribusi(): void
{
    unset($_SESSION['distribusi_petugas_id'], $_SESSION['distribusi_super_admin']);
}

function createDistribusiPetugas(string $username, string $password, string $nama): array
{
    ensureDistribusiLkpdSchema();
    $username = trim($username);
    $nama = trim($nama);

    if ($username === '' || $nama === '') {
        return ['ok' => false, 'error' => 'Username dan nama wajib diisi.'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Password minimal 6 karakter.'];
    }
    if (getDistribusiPetugasByUsername($username) !== null) {
        return ['ok' => false, 'error' => 'Username sudah digunakan.'];
    }

    $pdo = getDb();
    $stmt = $pdo->prepare(
        'INSERT INTO distribusi_petugas (username, password_hash, nama, role, aktif) VALUES (:u, :p, :n, :r, 1)'
    );
    $stmt->execute([
        ':u' => $username,
        ':p' => password_hash($password, PASSWORD_DEFAULT),
        ':n' => $nama,
        ':r' => 'petugas',
    ]);

    return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
}

function toggleDistribusiPetugas(int $id, bool $aktif): bool
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare('UPDATE distribusi_petugas SET aktif = :a WHERE id = :id AND role = :r');
    $stmt->execute([':a' => $aktif ? 1 : 0, ':id' => $id, ':r' => 'petugas']);

    return $stmt->rowCount() > 0;
}

function loadDistribusiPetugas(): array
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();

    return $pdo->query(
        "SELECT id, username, nama, role, aktif,
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at
         FROM distribusi_petugas ORDER BY role DESC, nama ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

function getSatuanByNpsn(string $npsn): ?array
{
    ensureDistribusiLkpdSchema();
    $npsn = normalizeNpsn($npsn);
    if ($npsn === '') {
        return null;
    }

    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM distribusi_lkpd_satuan WHERE npsn = :npsn LIMIT 1');
    $stmt->execute([':npsn' => $npsn]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function getSatuanById(int $id): ?array
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM distribusi_lkpd_satuan WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function getTotalTerimaSatuan(int $satuanId): array
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();
    $totals = array_fill(1, 6, 0);

    $stmt = $pdo->prepare(
        'SELECT terima_kelas_1, terima_kelas_2, terima_kelas_3, terima_kelas_4, terima_kelas_5, terima_kelas_6
         FROM distribusi_lkpd_pengiriman
         WHERE satuan_id = :id AND status IN (\'received_partial\', \'received_complete\')'
    );
    $stmt->execute([':id' => $satuanId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        for ($i = 1; $i <= 6; $i++) {
            $totals[$i] += (int) ($row['terima_kelas_' . $i] ?? 0);
        }
    }

    return $totals;
}

function satuanKebutuhanLengkap(array $satuan, array $totalTerima): bool
{
    for ($i = 1; $i <= 6; $i++) {
        $need = (int) ($satuan['kebutuhan_kelas_' . $i] ?? 0);
        if ($totalTerima[$i] < $need) {
            return false;
        }
    }

    return true;
}

function satuanKurangDetail(array $satuan, array $totalTerima): array
{
    $kurang = [];
    for ($i = 1; $i <= 6; $i++) {
        $need = (int) ($satuan['kebutuhan_kelas_' . $i] ?? 0);
        $got = $totalTerima[$i] ?? 0;
        if ($got < $need) {
            $kurang[$i] = $need - $got;
        }
    }

    return $kurang;
}

function getActivePengiriman(int $satuanId): ?array
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare(
        "SELECT p.*, u.nama AS nama_petugas
         FROM distribusi_lkpd_pengiriman p
         JOIN distribusi_petugas u ON u.id = p.petugas_id
         WHERE p.satuan_id = :sid AND p.status = 'delivery'
         ORDER BY p.id DESC LIMIT 1"
    );
    $stmt->execute([':sid' => $satuanId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function getPengirimanById(int $id): ?array
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare(
        "SELECT p.*, u.nama AS nama_petugas, s.npsn, s.nama_lembaga, s.alamat
         FROM distribusi_lkpd_pengiriman p
         JOIN distribusi_petugas u ON u.id = p.petugas_id
         JOIN distribusi_lkpd_satuan s ON s.id = p.satuan_id
         WHERE p.id = :id LIMIT 1"
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function loadPengirimanSatuan(int $satuanId): array
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare(
        "SELECT p.*, u.nama AS nama_petugas
         FROM distribusi_lkpd_pengiriman p
         JOIN distribusi_petugas u ON u.id = p.petugas_id
         WHERE p.satuan_id = :sid ORDER BY p.id DESC"
    );
    $stmt->execute([':sid' => $satuanId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function loadDistribusiSatuan(string $search = '', ?string $status = null): array
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();
    $sql = 'SELECT * FROM distribusi_lkpd_satuan WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (npsn LIKE :q OR nama_lembaga LIKE :q OR alamat LIKE :q)';
        $params[':q'] = '%' . $search . '%';
    }
    if ($status !== null && $status !== '') {
        $sql .= ' AND status = :st';
        $params[':st'] = $status;
    }
    $sql .= ' ORDER BY FIELD(status, \'delivery\', \'receive\', \'packing\', \'done\'), nama_lembaga ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function distribusiSuratJalanDir(): string
{
    $dir = APP_ROOT . '/data/distribusi_surat_jalan';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function uploadDistribusiSuratJalan(array $file, string $prefix): array
{
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'File wajib diunggah.', 'path' => null];
    }
    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['error' => 'Gagal mengunggah file.', 'path' => null];
    }
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
        return ['error' => 'Ukuran file maksimal 8 MB.', 'path' => null];
    }

    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
        return ['error' => 'Format file harus PDF, JPG, atau PNG.', 'path' => null];
    }

    $filename = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = distribusiSuratJalanDir() . '/' . $filename;
    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $dest)) {
        return ['error' => 'Gagal menyimpan file.', 'path' => null];
    }

    return ['error' => null, 'path' => 'data/distribusi_surat_jalan/' . $filename];
}

function streamDistribusiFile(string $relativePath): void
{
    $path = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
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

function buildDistribusiWaMessage(array $satuan, ?array $pengkinian): string
{
    $nama = $satuan['nama_lembaga'] ?? '';
    $npsn = $satuan['npsn'] ?? '';
    $alamat = $pengkinian['alamat_lengkap'] ?? ($satuan['alamat'] ?? '');

    return "Assalamu'alaikum Wr. Wb.\n\n"
        . "Informasi dari Distributor LP Ma'arif NU Kab. Magelang:\n"
        . "Buku ajar LKPD untuk *{$nama}* (NPSN: {$npsn}) sedang dalam pengiriman menuju lokasi satuan pendidikan.\n\n"
        . "Alamat tujuan:\n{$alamat}\n\n"
        . "Mohon menyiapkan penerimaan buku. Terima kasih.\n"
        . "LP Ma'arif NU Kab. Magelang";
}

function sendDistribusiWaNotifications(array $satuan): array
{
    $pengkinian = getPengkinianByNpsn((string) ($satuan['npsn'] ?? ''));
    $message = buildDistribusiWaMessage($satuan, $pengkinian);
    $numbers = [];
    if ($pengkinian !== null) {
        foreach (['nomor_hp_operator', 'nomor_hp_kepsek'] as $field) {
            $norm = normalizeNomorWa((string) ($pengkinian[$field] ?? ''));
            if ($norm !== '' && !in_array($norm, $numbers, true)) {
                $numbers[] = $norm;
            }
        }
    }

    $results = [];
    foreach ($numbers as $num) {
        $results[] = [
            'nomor' => $num,
            'sent' => sendWaApiMessage($num, $message),
            'wa_link' => waMeLink($num, $message),
        ];
    }

    return ['message' => $message, 'results' => $results, 'numbers' => $numbers];
}

function waMeLink(string $nomor, string $message): string
{
    $digits = normalizeNomorWa($nomor);
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    }

    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
}

function sendWaApiMessage(string $nomor, string $message): bool
{
    if (!defined('WA_API_ENABLED') || !WA_API_ENABLED) {
        return false;
    }
    if (!defined('WA_API_URL') || WA_API_URL === '') {
        return false;
    }

    $target = normalizeNomorWa($nomor);
    $payload = [
        'target' => $target,
        'message' => $message,
    ];
    if (defined('WA_API_TOKEN') && WA_API_TOKEN !== '') {
        $payload['token'] = WA_API_TOKEN;
    }

    $ch = curl_init(WA_API_URL);
    if ($ch === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $response !== false && $code >= 200 && $code < 300;
}

function dispatchDistribusiLkpd(int $petugasId, string $npsn): array
{
    ensureDistribusiLkpdSchema();
    $satuan = getSatuanByNpsn($npsn);
    if ($satuan === null) {
        return ['ok' => false, 'error' => 'NPSN tidak ditemukan dalam data distribusi.'];
    }

    $status = $satuan['status'] ?? DIST_STATUS_PACKING;
    if ($status === DIST_STATUS_DONE) {
        return ['ok' => false, 'error' => 'Distribusi satuan ini sudah selesai (Done).'];
    }
    if ($status === DIST_STATUS_DELIVERY) {
        return ['ok' => false, 'error' => 'Masih ada pengiriman aktif (Delivery). Selesaikan penerimaan terlebih dahulu.'];
    }
    if (!in_array($status, [DIST_STATUS_PACKING, DIST_STATUS_RECEIVE], true)) {
        return ['ok' => false, 'error' => 'Status satuan tidak valid untuk pengiriman.'];
    }

    $pdo = getDb();
    $stmt = $pdo->prepare(
        "INSERT INTO distribusi_lkpd_pengiriman (satuan_id, petugas_id, status) VALUES (:sid, :pid, 'delivery')"
    );
    $stmt->execute([':sid' => (int) $satuan['id'], ':pid' => $petugasId]);
    $pengirimanId = (int) $pdo->lastInsertId();

    $pdo->prepare("UPDATE distribusi_lkpd_satuan SET status = 'delivery' WHERE id = :id")
        ->execute([':id' => (int) $satuan['id']]);

    $wa = sendDistribusiWaNotifications($satuan);
    $sentTo = implode(', ', $wa['numbers']);
    if ($sentTo !== '') {
        $pdo->prepare('UPDATE distribusi_lkpd_pengiriman SET wa_sent_at = NOW(), wa_sent_to = :t WHERE id = :id')
            ->execute([':t' => $sentTo, ':id' => $pengirimanId]);
    }

    return [
        'ok' => true,
        'pengiriman_id' => $pengirimanId,
        'satuan' => getSatuanById((int) $satuan['id']),
        'wa' => $wa,
    ];
}

function receiveDistribusiLkpd(int $petugasId, int $pengirimanId, array $input, array $files): array
{
    ensureDistribusiLkpdSchema();
    $pengiriman = getPengirimanById($pengirimanId);
    if ($pengiriman === null) {
        return ['ok' => false, 'error' => 'Data pengiriman tidak ditemukan.'];
    }
    if (($pengiriman['status'] ?? '') !== 'delivery') {
        return ['ok' => false, 'error' => 'Pengiriman ini sudah diproses.'];
    }

    $satuan = getSatuanById((int) $pengiriman['satuan_id']);
    if ($satuan === null) {
        return ['ok' => false, 'error' => 'Data satuan tidak ditemukan.'];
    }

    $terima = [];
    for ($i = 1; $i <= 6; $i++) {
        $val = (int) ($input['terima_kelas_' . $i] ?? 0);
        if ($val < 0) {
            return ['ok' => false, 'error' => 'Jumlah terima kelas ' . $i . ' tidak valid.'];
        }
        $terima[$i] = $val;
    }

    if (array_sum($terima) === 0) {
        return ['ok' => false, 'error' => 'Isi jumlah buku yang diterima minimal pada satu kelas.'];
    }

    $upDist = uploadDistribusiSuratJalan($files['file_surat_jalan_distributor'] ?? [], 'sj_dist_' . $pengirimanId);
    if ($upDist['error'] !== null) {
        return ['ok' => false, 'error' => 'Surat jalan distributor: ' . $upDist['error']];
    }

    $upSekolah = uploadDistribusiSuratJalan($files['file_surat_jalan_sekolah'] ?? [], 'sj_sekolah_' . $pengirimanId);
    if ($upSekolah['error'] !== null) {
        return ['ok' => false, 'error' => 'Surat jalan sekolah: ' . $upSekolah['error']];
    }

    $totalBefore = getTotalTerimaSatuan((int) $satuan['id']);
    $totalAfter = $totalBefore;
    for ($i = 1; $i <= 6; $i++) {
        $totalAfter[$i] += $terima[$i];
    }

    $lengkap = satuanKebutuhanLengkap($satuan, $totalAfter);
    $pengirimanStatus = $lengkap ? 'received_complete' : 'received_partial';
    $satuanStatus = $lengkap ? DIST_STATUS_DONE : DIST_STATUS_RECEIVE;
    $catatan = trim($input['catatan'] ?? '');

    $pdo = getDb();
    $stmt = $pdo->prepare(
        'UPDATE distribusi_lkpd_pengiriman SET
            status = :st,
            terima_kelas_1 = :k1, terima_kelas_2 = :k2, terima_kelas_3 = :k3,
            terima_kelas_4 = :k4, terima_kelas_5 = :k5, terima_kelas_6 = :k6,
            file_surat_jalan_distributor = :fd,
            file_surat_jalan_sekolah = :fs,
            catatan = :cat,
            received_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        ':st' => $pengirimanStatus,
        ':k1' => $terima[1], ':k2' => $terima[2], ':k3' => $terima[3],
        ':k4' => $terima[4], ':k5' => $terima[5], ':k6' => $terima[6],
        ':fd' => $upDist['path'],
        ':fs' => $upSekolah['path'],
        ':cat' => $catatan !== '' ? $catatan : null,
        ':id' => $pengirimanId,
    ]);

    $pdo->prepare('UPDATE distribusi_lkpd_satuan SET status = :st WHERE id = :id')
        ->execute([':st' => $satuanStatus, ':id' => (int) $satuan['id']]);

    return [
        'ok' => true,
        'lengkap' => $lengkap,
        'satuan_status' => $satuanStatus,
        'kurang' => satuanKurangDetail($satuan, $totalAfter),
        'total_terima' => $totalAfter,
    ];
}

function upsertDistribusiSatuanRow(array $row): void
{
    ensureDistribusiLkpdSchema();
    $npsn = normalizeNpsn($row['npsn'] ?? '');
    if ($npsn === '') {
        return;
    }

    $pdo = getDb();
    $existing = getSatuanByNpsn($npsn);
    $data = [
        ':npsn' => $npsn,
        ':nama' => trim($row['nama_lembaga'] ?? ''),
        ':alamat' => trim($row['alamat'] ?? ''),
        ':k1' => (int) ($row['kebutuhan_kelas_1'] ?? 0),
        ':k2' => (int) ($row['kebutuhan_kelas_2'] ?? 0),
        ':k3' => (int) ($row['kebutuhan_kelas_3'] ?? 0),
        ':k4' => (int) ($row['kebutuhan_kelas_4'] ?? 0),
        ':k5' => (int) ($row['kebutuhan_kelas_5'] ?? 0),
        ':k6' => (int) ($row['kebutuhan_kelas_6'] ?? 0),
    ];

    if ($existing === null) {
        $pdo->prepare(
            'INSERT INTO distribusi_lkpd_satuan
            (npsn, nama_lembaga, alamat, kebutuhan_kelas_1, kebutuhan_kelas_2, kebutuhan_kelas_3,
             kebutuhan_kelas_4, kebutuhan_kelas_5, kebutuhan_kelas_6, status)
             VALUES (:npsn, :nama, :alamat, :k1, :k2, :k3, :k4, :k5, :k6, \'packing\')'
        )->execute($data);
    } else {
        $pdo->prepare(
            'UPDATE distribusi_lkpd_satuan SET
             nama_lembaga = :nama, alamat = :alamat,
             kebutuhan_kelas_1 = :k1, kebutuhan_kelas_2 = :k2, kebutuhan_kelas_3 = :k3,
             kebutuhan_kelas_4 = :k4, kebutuhan_kelas_5 = :k5, kebutuhan_kelas_6 = :k6
             WHERE npsn = :npsn'
        )->execute($data);
    }
}

function parseDistribusiImportRows(string $filePath): array
{
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($ext === 'csv') {
        return parseDistribusiCsv($filePath);
    }
    if (in_array($ext, ['xlsx', 'xls'], true)) {
        return parseDistribusiXlsx($filePath);
    }

    return ['rows' => [], 'errors' => ['Format file harus CSV atau XLSX.']];
}

function parseDistribusiCsv(string $filePath): array
{
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        return ['rows' => [], 'errors' => ['Gagal membaca file CSV.']];
    }

    $rows = [];
    $errors = [];
    $header = null;
    $line = 0;

    while (($data = fgetcsv($handle)) !== false) {
        $line++;
        if ($header === null) {
            $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $data);
            continue;
        }
        if (count(array_filter($data, static fn ($v) => trim((string) $v) !== '')) === 0) {
            continue;
        }
        $mapped = mapDistribusiImportRow($header, $data);
        if ($mapped === null) {
            $errors[] = "Baris {$line}: NPSN kosong, dilewati.";
            continue;
        }
        $rows[] = $mapped;
    }
    fclose($handle);

    return ['rows' => $rows, 'errors' => $errors];
}

function parseDistribusiXlsx(string $filePath): array
{
    if (!class_exists('ZipArchive')) {
        return ['rows' => [], 'errors' => ['Ekstensi ZipArchive tidak tersedia. Simpan Excel sebagai CSV.']];
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        return ['rows' => [], 'errors' => ['Gagal membuka file XLSX.']];
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sx = @simplexml_load_string($sharedXml);
        if ($sx !== false) {
            foreach ($sx->si as $si) {
                $sharedStrings[] = trim((string) ($si->t ?? $si->r->t ?? ''));
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) {
        return ['rows' => [], 'errors' => ['Sheet 1 tidak ditemukan.']];
    }

    $sheet = @simplexml_load_string($sheetXml);
    if ($sheet === false) {
        return ['rows' => [], 'errors' => ['Gagal parse sheet Excel.']];
    }

    $grid = [];
    foreach ($sheet->sheetData->row as $row) {
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
                continue;
            }
            $col = $m[1];
            $rowNum = (int) $m[2];
            $type = (string) ($cell['t'] ?? '');
            $value = (string) ($cell->v ?? '');
            if ($type === 's' && isset($sharedStrings[(int) $value])) {
                $value = $sharedStrings[(int) $value];
            }
            $grid[$rowNum][$col] = $value;
        }
    }

    if ($grid === []) {
        return ['rows' => [], 'errors' => ['Sheet kosong.']];
    }

    ksort($grid);
    $headerRow = array_shift($grid);
    if ($headerRow === null) {
        return ['rows' => [], 'errors' => ['Header tidak ditemukan.']];
    }

    $cols = array_keys($headerRow);
    sort($cols);
    $header = [];
    foreach ($cols as $col) {
        $header[] = strtolower(trim((string) ($headerRow[$col] ?? '')));
    }

    $rows = [];
    $errors = [];
    foreach ($grid as $rowNum => $rowData) {
        $line = [];
        foreach ($cols as $i => $col) {
            $line[] = (string) ($rowData[$col] ?? '');
        }
        if (count(array_filter($line, static fn ($v) => trim($v) !== '')) === 0) {
            continue;
        }
        $mapped = mapDistribusiImportRow($header, $line);
        if ($mapped === null) {
            $errors[] = "Baris {$rowNum}: NPSN kosong, dilewati.";
            continue;
        }
        $rows[] = $mapped;
    }

    return ['rows' => $rows, 'errors' => $errors];
}

function mapDistribusiImportRow(array $header, array $data): ?array
{
    $map = [];
    foreach ($header as $i => $key) {
        $map[$key] = trim((string) ($data[$i] ?? ''));
    }

    $npsn = normalizeNpsn(
        $map['npsn'] ?? $map['npsn/sekolah'] ?? $map['no npsn'] ?? ''
    );
    if ($npsn === '') {
        return null;
    }

    $nama = $map['nama lembaga'] ?? $map['nama_lembaga'] ?? $map['nama madrasah'] ?? $map['nama sekolah'] ?? $map['nama'] ?? '';
    $alamat = $map['alamat'] ?? $map['alamat lembaga'] ?? '';

    $kelas = [];
    for ($i = 1; $i <= 6; $i++) {
        $keys = [
            "kelas {$i}", "kelas_{$i}", "kls {$i}", "kls_{$i}",
            "jumlah kelas {$i}", "jumlah_kelas_{$i}", "kebutuhan kelas {$i}",
        ];
        $roman = ['', 'i', 'ii', 'iii', 'iv', 'v', 'vi'][$i] ?? '';
        if ($roman !== '') {
            $keys[] = "kelas {$roman}";
            $keys[] = "jumlah {$roman}";
        }
        $val = 0;
        foreach ($keys as $k) {
            if (isset($map[$k]) && $map[$k] !== '') {
                $val = (int) preg_replace('/\D/', '', $map[$k]);
                break;
            }
        }
        $kelas[$i] = $val;
    }

    return [
        'npsn' => $npsn,
        'nama_lembaga' => $nama,
        'alamat' => $alamat,
        'kebutuhan_kelas_1' => $kelas[1],
        'kebutuhan_kelas_2' => $kelas[2],
        'kebutuhan_kelas_3' => $kelas[3],
        'kebutuhan_kelas_4' => $kelas[4],
        'kebutuhan_kelas_5' => $kelas[5],
        'kebutuhan_kelas_6' => $kelas[6],
    ];
}

function importDistribusiFile(string $tmpPath, string $originalName): array
{
    $parsed = parseDistribusiImportRows($tmpPath);
    if (!empty($parsed['errors']) && empty($parsed['rows'])) {
        return ['ok' => false, 'imported' => 0, 'errors' => $parsed['errors']];
    }

    $imported = 0;
    foreach ($parsed['rows'] as $row) {
        upsertDistribusiSatuanRow($row);
        $imported++;
    }

    return ['ok' => true, 'imported' => $imported, 'errors' => $parsed['errors']];
}

function getDistribusiDashboardStats(): array
{
    ensureDistribusiLkpdSchema();
    $pdo = getDb();
    $stats = [
        'packing' => 0,
        'delivery' => 0,
        'receive' => 0,
        'done' => 0,
        'total' => 0,
    ];

    $rows = $pdo->query('SELECT status, COUNT(*) AS c FROM distribusi_lkpd_satuan GROUP BY status')->fetchAll();
    foreach ($rows as $row) {
        $st = $row['status'] ?? '';
        $c = (int) ($row['c'] ?? 0);
        if (isset($stats[$st])) {
            $stats[$st] = $c;
        }
        $stats['total'] += $c;
    }

    return $stats;
}

function exportDistribusiCsv(array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="distribusi_lkpd_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    if ($out === false) {
        return;
    }
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['NPSN', 'Nama Lembaga', 'Alamat', 'K1', 'K2', 'K3', 'K4', 'K5', 'K6', 'Status']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['npsn'] ?? '',
            $row['nama_lembaga'] ?? '',
            $row['alamat'] ?? '',
            $row['kebutuhan_kelas_1'] ?? 0,
            $row['kebutuhan_kelas_2'] ?? 0,
            $row['kebutuhan_kelas_3'] ?? 0,
            $row['kebutuhan_kelas_4'] ?? 0,
            $row['kebutuhan_kelas_5'] ?? 0,
            $row['kebutuhan_kelas_6'] ?? 0,
            distribusiStatusLabel($row['status'] ?? ''),
        ]);
    }
    fclose($out);
    exit;
}
