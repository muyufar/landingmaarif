<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function ensureBeritaSchema(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo = getDb();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `berita` (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `judul` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `ringkasan` text DEFAULT NULL,
          `konten` mediumtext NOT NULL,
          `gambar` varchar(255) DEFAULT NULL,
          `status` enum('draft','published') NOT NULL DEFAULT 'draft',
          `published_at` datetime DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_berita_slug` (`slug`),
          KEY `idx_berita_status` (`status`),
          KEY `idx_berita_published_at` (`published_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `berita_gambar` (
              `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `berita_id` int(10) UNSIGNED NOT NULL,
              `path` varchar(255) NOT NULL,
              `urutan` int(10) UNSIGNED NOT NULL DEFAULT 0,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_berita_gambar_berita` (`berita_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (PDOException) {
        // Abaikan jika tabel sudah ada / constraint host terbatas
    }

    try {
        $columns = array_column($pdo->query('SHOW COLUMNS FROM berita')->fetchAll(), 'Field');
        if (!in_array('kode_singkat', $columns, true)) {
            $pdo->exec('ALTER TABLE berita ADD COLUMN `kode_singkat` varchar(12) DEFAULT NULL AFTER `slug`');
            $pdo->exec('ALTER TABLE berita ADD UNIQUE KEY `uq_berita_kode_singkat` (`kode_singkat`)');
        }
    } catch (PDOException) {
        // Abaikan jika kolom/index sudah ada
    }

    migrateLegacyBeritaGambar();
    ensureAllBeritaHaveShortCode();
    $done = true;
}

function migrateLegacyBeritaGambar(): void
{
    $pdo = getDb();
    try {
        $rows = $pdo->query(
            "SELECT b.id, b.gambar
             FROM berita b
             LEFT JOIN berita_gambar g ON g.berita_id = b.id
             WHERE b.gambar IS NOT NULL AND b.gambar != '' AND g.id IS NULL"
        )->fetchAll();
    } catch (PDOException) {
        return;
    }

    if ($rows === []) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO berita_gambar (berita_id, path, urutan) VALUES (:berita_id, :path, 0)'
    );
    foreach ($rows as $row) {
        $stmt->execute([
            ':berita_id' => (int) $row['id'],
            ':path' => $row['gambar'],
        ]);
    }
}

function generateBeritaShortCode(int $length = 6): string
{
    $alphabet = 'abcdefghijkmnopqrstuvwxyz23456789';
    $max = strlen($alphabet) - 1;
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, $max)];
    }

    return $code;
}

function uniqueBeritaShortCode(): string
{
    $pdo = getDb();
    do {
        $code = generateBeritaShortCode();
        $stmt = $pdo->prepare('SELECT id FROM berita WHERE kode_singkat = :kode LIMIT 1');
        $stmt->execute([':kode' => $code]);
        $exists = (bool) $stmt->fetch();
    } while ($exists);

    return $code;
}

function ensureAllBeritaHaveShortCode(): void
{
    $pdo = getDb();
    try {
        $rows = $pdo->query(
            "SELECT id FROM berita WHERE kode_singkat IS NULL OR kode_singkat = ''"
        )->fetchAll();
    } catch (PDOException) {
        return;
    }

    if ($rows === []) {
        return;
    }

    $stmt = $pdo->prepare('UPDATE berita SET kode_singkat = :kode WHERE id = :id');
    foreach ($rows as $row) {
        $stmt->execute([
            ':kode' => uniqueBeritaShortCode(),
            ':id' => (int) $row['id'],
        ]);
    }
}

function getBeritaByKode(string $kode): ?array
{
    $kode = strtolower(trim($kode));
    if ($kode === '' || !preg_match('/^[a-z0-9]{4,12}$/', $kode)) {
        return null;
    }

    ensureBeritaSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM berita WHERE kode_singkat = :kode LIMIT 1');
    $stmt->execute([':kode' => $kode]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $rows = attachBeritaGaleri([$row]);

    return $rows[0] ?? null;
}

function beritaShortPath(array $row): string
{
    $kode = trim((string) ($row['kode_singkat'] ?? ''));
    if ($kode === '') {
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0) {
            ensureBeritaSchema();
            $kode = uniqueBeritaShortCode();
            $pdo = getDb();
            $stmt = $pdo->prepare('UPDATE berita SET kode_singkat = :kode WHERE id = :id AND (kode_singkat IS NULL OR kode_singkat = "")');
            $stmt->execute([':kode' => $kode, ':id' => $id]);
            $row['kode_singkat'] = $kode;
        }
    }

    return 'b/' . $kode;
}

function beritaShortUrl(array $row): string
{
    return absoluteUrl(beritaShortPath($row));
}

function beritaPublicUrl(array $row): string
{
    return beritaShortUrl($row);
}

function beritaShareLinks(array $row): array
{
    $url = beritaShortUrl($row);
    $judul = trim((string) ($row['judul'] ?? 'Berita LP Ma\'arif NU Magelang'));
    $text = $judul . ' — ' . $url;

    return [
        'url' => $url,
        'whatsapp' => 'https://wa.me/?text=' . rawurlencode($text),
        'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url),
        'telegram' => 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode($judul),
        'twitter' => 'https://twitter.com/intent/tweet?text=' . rawurlencode($judul) . '&url=' . rawurlencode($url),
    ];
}

function beritaUploadDir(): string
{
    $dir = APP_ROOT . '/uploads/berita';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function slugifyBerita(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text) ?? '';
    $text = preg_replace('/[\s-]+/', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'berita-' . date('YmdHis');
}

function uniqueBeritaSlug(string $judul, ?int $excludeId = null): string
{
    ensureBeritaSchema();
    $base = slugifyBerita($judul);
    $slug = $base;
    $i = 2;
    $pdo = getDb();

    while (true) {
        $sql = 'SELECT id FROM berita WHERE slug = :slug';
        $params = [':slug' => $slug];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function beritaFormDefaults(?array $row = null): array
{
    return [
        'judul' => $row['judul'] ?? '',
        'ringkasan' => $row['ringkasan'] ?? '',
        'konten' => $row['konten'] ?? '',
        'status' => $row['status'] ?? 'draft',
        'gambar' => $row['gambar'] ?? '',
        'galeri' => $row['galeri'] ?? [],
    ];
}

function validateBerita(array $input): array
{
    $errors = [];
    $data = [];

    $judul = trim($input['judul'] ?? '');
    if ($judul === '') {
        $errors[] = 'Judul berita wajib diisi.';
    } else {
        $data['judul'] = $judul;
    }

    $ringkasan = trim($input['ringkasan'] ?? '');
    $data['ringkasan'] = $ringkasan;

    $konten = trim($input['konten'] ?? '');
    if ($konten === '') {
        $errors[] = 'Isi berita wajib diisi.';
    } else {
        $data['konten'] = $konten;
    }

    $status = trim($input['status'] ?? 'draft');
    $data['status'] = in_array($status, ['draft', 'published'], true) ? $status : 'draft';

    return ['errors' => $errors, 'data' => $data];
}

function normalizeBeritaFilesInput(array $files): array
{
    if (!isset($files['name'])) {
        return [];
    }

    // Single file shape
    if (!is_array($files['name'])) {
        return [$files];
    }

    $normalized = [];
    foreach ($files['name'] as $i => $name) {
        $normalized[] = [
            'name' => $name,
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }

    return $normalized;
}

function storeBeritaGambarFile(array $file): array
{
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return ['error' => null, 'path' => null];
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['error' => 'Gagal mengunggah salah satu gambar. Silakan coba lagi.', 'path' => null];
    }

    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        return ['error' => 'Ukuran tiap gambar maksimal 3 MB.', 'path' => null];
    }

    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['error' => 'Format gambar harus JPG, PNG, atau WEBP.', 'path' => null];
    }

    $dir = beritaUploadDir();
    $filename = 'berita_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $filename;
    $relative = 'uploads/berita/' . $filename;

    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $dest)) {
        return ['error' => 'Gagal menyimpan gambar berita.', 'path' => null];
    }

    return ['error' => null, 'path' => $relative];
}

function handleBeritaMultiGambarUpload(array $filesInput): array
{
    $files = normalizeBeritaFilesInput($filesInput);
    $paths = [];

    foreach ($files as $file) {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $result = storeBeritaGambarFile($file);
        if ($result['error'] !== null) {
            foreach ($paths as $path) {
                deleteBeritaGambarFile($path);
            }

            return ['error' => $result['error'], 'paths' => []];
        }

        if (!empty($result['path'])) {
            $paths[] = $result['path'];
        }
    }

    return ['error' => null, 'paths' => $paths];
}

function deleteBeritaGambarFile(?string $path): void
{
    if ($path === null || $path === '') {
        return;
    }

    $full = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $path), '/');
    if (is_file($full)) {
        @unlink($full);
    }
}

function getBeritaGambarList(int $beritaId): array
{
    ensureBeritaSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare(
        'SELECT id, berita_id, path, urutan, created_at
         FROM berita_gambar
         WHERE berita_id = :berita_id
         ORDER BY urutan ASC, id ASC'
    );
    $stmt->execute([':berita_id' => $beritaId]);

    return $stmt->fetchAll();
}

function getBeritaCoverPath(array $row): string
{
    if (!empty($row['gambar'])) {
        return (string) $row['gambar'];
    }

    $galeri = $row['galeri'] ?? [];
    if ($galeri !== [] && !empty($galeri[0]['path'])) {
        return (string) $galeri[0]['path'];
    }

    return '';
}

function attachBeritaGaleri(array $rows): array
{
    if ($rows === []) {
        return [];
    }

    $ids = array_values(array_filter(array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows)));
    if ($ids === []) {
        return $rows;
    }

    ensureBeritaSchema();
    $pdo = getDb();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT id, berita_id, path, urutan, created_at
         FROM berita_gambar
         WHERE berita_id IN ({$placeholders})
         ORDER BY urutan ASC, id ASC"
    );
    $stmt->execute($ids);
    $byBerita = [];
    foreach ($stmt->fetchAll() as $img) {
        $byBerita[(int) $img['berita_id']][] = $img;
    }

    foreach ($rows as &$row) {
        $id = (int) ($row['id'] ?? 0);
        $galeri = $byBerita[$id] ?? [];
        if ($galeri === [] && !empty($row['gambar'])) {
            $galeri = [[
                'id' => 0,
                'berita_id' => $id,
                'path' => $row['gambar'],
                'urutan' => 0,
            ]];
        }
        $row['galeri'] = $galeri;
        if (empty($row['gambar']) && !empty($galeri[0]['path'])) {
            $row['gambar'] = $galeri[0]['path'];
        }
    }
    unset($row);

    return $rows;
}

function syncBeritaCover(int $beritaId): void
{
    $images = getBeritaGambarList($beritaId);
    $cover = $images[0]['path'] ?? null;
    $pdo = getDb();
    $stmt = $pdo->prepare('UPDATE berita SET gambar = :gambar WHERE id = :id');
    $stmt->execute([
        ':gambar' => $cover,
        ':id' => $beritaId,
    ]);
}

function addBeritaGambarRows(int $beritaId, array $paths): void
{
    if ($paths === []) {
        return;
    }

    ensureBeritaSchema();
    $pdo = getDb();
    $stmtMax = $pdo->prepare('SELECT COALESCE(MAX(urutan), -1) FROM berita_gambar WHERE berita_id = :id');
    $stmtMax->execute([':id' => $beritaId]);
    $urutan = (int) $stmtMax->fetchColumn() + 1;

    $stmt = $pdo->prepare(
        'INSERT INTO berita_gambar (berita_id, path, urutan) VALUES (:berita_id, :path, :urutan)'
    );
    foreach ($paths as $path) {
        $stmt->execute([
            ':berita_id' => $beritaId,
            ':path' => $path,
            ':urutan' => $urutan,
        ]);
        $urutan++;
    }

    syncBeritaCover($beritaId);
}

function deleteBeritaGambarById(int $gambarId, int $beritaId): bool
{
    ensureBeritaSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM berita_gambar WHERE id = :id AND berita_id = :berita_id LIMIT 1');
    $stmt->execute([':id' => $gambarId, ':berita_id' => $beritaId]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }

    $del = $pdo->prepare('DELETE FROM berita_gambar WHERE id = :id');
    $ok = $del->execute([':id' => $gambarId]);
    if ($ok) {
        deleteBeritaGambarFile($row['path'] ?? null);
        syncBeritaCover($beritaId);
    }

    return $ok;
}

function loadBerita(string $search = '', string $status = ''): array
{
    ensureBeritaSchema();
    $pdo = getDb();
    $sql = 'SELECT * FROM berita WHERE 1=1';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (judul LIKE :q OR ringkasan LIKE :q OR konten LIKE :q)';
        $params[':q'] = '%' . $search . '%';
    }

    if ($status !== '' && in_array($status, ['draft', 'published'], true)) {
        $sql .= ' AND status = :status';
        $params[':status'] = $status;
    }

    $sql .= ' ORDER BY COALESCE(published_at, created_at) DESC, id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return attachBeritaGaleri($stmt->fetchAll());
}

function loadBeritaPublished(int $limit = 12): array
{
    ensureBeritaSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare(
        'SELECT * FROM berita
         WHERE status = "published"
         ORDER BY COALESCE(published_at, created_at) DESC, id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return attachBeritaGaleri($stmt->fetchAll());
}

function getBeritaById(int $id): ?array
{
    ensureBeritaSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM berita WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $rows = attachBeritaGaleri([$row]);

    return $rows[0] ?? null;
}

function getBeritaBySlug(string $slug): ?array
{
    ensureBeritaSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM berita WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $rows = attachBeritaGaleri([$row]);

    return $rows[0] ?? null;
}

function addBerita(array $data, array $gambarPaths = []): int|false
{
    ensureBeritaSchema();
    $pdo = getDb();
    $slug = uniqueBeritaSlug($data['judul']);
    $publishedAt = $data['status'] === 'published' ? date('Y-m-d H:i:s') : null;
    $cover = $gambarPaths[0] ?? ($data['gambar'] ?? null);

    $stmt = $pdo->prepare(
        'INSERT INTO berita (judul, slug, kode_singkat, ringkasan, konten, gambar, status, published_at)
         VALUES (:judul, :slug, :kode_singkat, :ringkasan, :konten, :gambar, :status, :published_at)'
    );

    $ok = $stmt->execute([
        ':judul' => $data['judul'],
        ':slug' => $slug,
        ':kode_singkat' => uniqueBeritaShortCode(),
        ':ringkasan' => $data['ringkasan'] !== '' ? $data['ringkasan'] : null,
        ':konten' => $data['konten'],
        ':gambar' => $cover !== '' ? $cover : null,
        ':status' => $data['status'],
        ':published_at' => $publishedAt,
    ]);

    if (!$ok) {
        return false;
    }

    $id = (int) $pdo->lastInsertId();
    if ($gambarPaths !== []) {
        addBeritaGambarRows($id, $gambarPaths);
    }

    return $id;
}

function updateBerita(int $id, array $data, array $gambarPaths = []): bool
{
    ensureBeritaSchema();
    $existing = getBeritaById($id);
    if (!$existing) {
        return false;
    }

    $slug = uniqueBeritaSlug($data['judul'], $id);
    $publishedAt = $existing['published_at'] ?? null;
    if ($data['status'] === 'published' && empty($publishedAt)) {
        $publishedAt = date('Y-m-d H:i:s');
    }
    if ($data['status'] === 'draft') {
        $publishedAt = $existing['published_at'] ?? null;
    }

    $pdo = getDb();
    $stmt = $pdo->prepare(
        'UPDATE berita SET
            judul = :judul, slug = :slug, ringkasan = :ringkasan, konten = :konten,
            status = :status, published_at = :published_at
         WHERE id = :id'
    );

    $ok = $stmt->execute([
        ':id' => $id,
        ':judul' => $data['judul'],
        ':slug' => $slug,
        ':ringkasan' => $data['ringkasan'] !== '' ? $data['ringkasan'] : null,
        ':konten' => $data['konten'],
        ':status' => $data['status'],
        ':published_at' => $publishedAt,
    ]);

    if (!$ok) {
        return false;
    }

    if (empty($existing['kode_singkat'])) {
        $pdo->prepare('UPDATE berita SET kode_singkat = :kode WHERE id = :id')
            ->execute([':kode' => uniqueBeritaShortCode(), ':id' => $id]);
    }

    if ($gambarPaths !== []) {
        addBeritaGambarRows($id, $gambarPaths);
    } else {
        syncBeritaCover($id);
    }

    return true;
}

function deleteBerita(int $id): bool
{
    ensureBeritaSchema();
    $row = getBeritaById($id);
    if (!$row) {
        return false;
    }

    $images = getBeritaGambarList($id);
    $pdo = getDb();
    $stmt = $pdo->prepare('DELETE FROM berita WHERE id = :id');
    $ok = $stmt->execute([':id' => $id]);
    if ($ok) {
        foreach ($images as $img) {
            deleteBeritaGambarFile($img['path'] ?? null);
        }
        if (!empty($row['gambar'])) {
            deleteBeritaGambarFile($row['gambar']);
        }
    }

    return $ok && $stmt->rowCount() > 0;
}

function countBeritaByStatus(): array
{
    ensureBeritaSchema();
    $pdo = getDb();
    $stmt = $pdo->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft
         FROM berita"
    );
    $row = $stmt->fetch() ?: [];

    return [
        'total' => (int) ($row['total'] ?? 0),
        'published' => (int) ($row['published'] ?? 0),
        'draft' => (int) ($row['draft'] ?? 0),
    ];
}

function formatTanggalBerita(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '-';
    }

    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }

    return date('d/m/Y H:i', $ts);
}

function ringkasanBerita(array $row, int $max = 160): string
{
    $text = trim((string) ($row['ringkasan'] ?? ''));
    if ($text === '') {
        $text = trim(strip_tags((string) ($row['konten'] ?? '')));
    }

    if (mb_strlen($text) <= $max) {
        return $text;
    }

    return mb_substr($text, 0, $max - 1) . '…';
}
