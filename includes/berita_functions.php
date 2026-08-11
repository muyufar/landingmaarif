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

    $done = true;
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

function handleBeritaGambarUpload(array $file, ?string $existingPath = null): array
{
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return ['error' => null, 'path' => $existingPath];
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['error' => 'Gagal mengunggah gambar. Silakan coba lagi.', 'path' => null];
    }

    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        return ['error' => 'Ukuran gambar maksimal 3 MB.', 'path' => null];
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

    if ($existingPath !== null && $existingPath !== '') {
        deleteBeritaGambarFile($existingPath);
    }

    return ['error' => null, 'path' => $relative];
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

    return $stmt->fetchAll();
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

    return $stmt->fetchAll();
}

function getBeritaById(int $id): ?array
{
    ensureBeritaSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM berita WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function getBeritaBySlug(string $slug): ?array
{
    ensureBeritaSchema();
    $pdo = getDb();
    $stmt = $pdo->prepare('SELECT * FROM berita WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function addBerita(array $data): bool
{
    ensureBeritaSchema();
    $pdo = getDb();
    $slug = uniqueBeritaSlug($data['judul']);
    $publishedAt = $data['status'] === 'published' ? date('Y-m-d H:i:s') : null;

    $stmt = $pdo->prepare(
        'INSERT INTO berita (judul, slug, ringkasan, konten, gambar, status, published_at)
         VALUES (:judul, :slug, :ringkasan, :konten, :gambar, :status, :published_at)'
    );

    return $stmt->execute([
        ':judul' => $data['judul'],
        ':slug' => $slug,
        ':ringkasan' => $data['ringkasan'] !== '' ? $data['ringkasan'] : null,
        ':konten' => $data['konten'],
        ':gambar' => $data['gambar'] !== '' ? $data['gambar'] : null,
        ':status' => $data['status'],
        ':published_at' => $publishedAt,
    ]);
}

function updateBerita(int $id, array $data): bool
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
            gambar = :gambar, status = :status, published_at = :published_at
         WHERE id = :id'
    );

    return $stmt->execute([
        ':id' => $id,
        ':judul' => $data['judul'],
        ':slug' => $slug,
        ':ringkasan' => $data['ringkasan'] !== '' ? $data['ringkasan'] : null,
        ':konten' => $data['konten'],
        ':gambar' => $data['gambar'] !== '' ? $data['gambar'] : null,
        ':status' => $data['status'],
        ':published_at' => $publishedAt,
    ]);
}

function deleteBerita(int $id): bool
{
    ensureBeritaSchema();
    $row = getBeritaById($id);
    if (!$row) {
        return false;
    }

    $pdo = getDb();
    $stmt = $pdo->prepare('DELETE FROM berita WHERE id = :id');
    $ok = $stmt->execute([':id' => $id]);
    if ($ok) {
        deleteBeritaGambarFile($row['gambar'] ?? null);
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
