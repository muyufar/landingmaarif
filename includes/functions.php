<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function validatePendaftaran(array $input): array
{
    $errors = [];
    $data = [];

    $required = [
        'nama' => 'Nama',
        'nomor_wa' => 'Nomor WA',
        'tempat_lahir' => 'Tempat Lahir',
        'tanggal_lahir' => 'Tanggal Lahir',
        'jabatan' => 'Jabatan',
        'asal_lembaga' => 'Asal Lembaga',
        'alamat_lembaga' => 'Alamat Lembaga',
        'alat_transportasi' => 'Alat Transportasi',
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

    if (!empty($data['nomor_wa']) && !preg_match('/^[0-9+\-\s()]{8,20}$/', $data['nomor_wa'])) {
        $errors[] = 'Nomor WA tidak valid.';
    }

    if (!empty($data['tanggal_lahir'])) {
        $date = DateTime::createFromFormat('Y-m-d', $data['tanggal_lahir']);
        if (!$date || $date->format('Y-m-d') !== $data['tanggal_lahir']) {
            $errors[] = 'Tanggal lahir tidak valid.';
        }
    }

    return ['errors' => $errors, 'data' => $data];
}

function loadPeserta(string $search = ''): array
{
    $pdo = getDb();

    $sql = 'SELECT id, nama, nip, nomor_wa, tempat_lahir,
                   DATE_FORMAT(tanggal_lahir, "%Y-%m-%d") AS tanggal_lahir,
                   jabatan, asal_lembaga, alamat_lembaga, alat_transportasi,
                   DATE_FORMAT(created_at, "%Y-%m-%d %H:%i:%s") AS created_at
            FROM peserta_rakerdinma';

    $params = [];

    if ($search !== '') {
        $sql .= ' WHERE nama LIKE :q OR nip LIKE :q OR nomor_wa LIKE :q
                  OR asal_lembaga LIKE :q OR jabatan LIKE :q';
        $params[':q'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function addPeserta(array $data): bool
{
    $pdo = getDb();

    $stmt = $pdo->prepare(
        'INSERT INTO peserta_rakerdinma
            (nama, nip, nomor_wa, tempat_lahir, tanggal_lahir, jabatan, asal_lembaga, alamat_lembaga, alat_transportasi)
         VALUES
            (:nama, :nip, :nomor_wa, :tempat_lahir, :tanggal_lahir, :jabatan, :asal_lembaga, :alamat_lembaga, :alat_transportasi)'
    );

    return $stmt->execute([
        ':nama' => $data['nama'],
        ':nip' => $data['nip'] !== '' ? $data['nip'] : null,
        ':nomor_wa' => $data['nomor_wa'],
        ':tempat_lahir' => $data['tempat_lahir'],
        ':tanggal_lahir' => $data['tanggal_lahir'],
        ':jabatan' => $data['jabatan'],
        ':asal_lembaga' => $data['asal_lembaga'],
        ':alamat_lembaga' => $data['alamat_lembaga'],
        ':alat_transportasi' => $data['alat_transportasi'],
    ]);
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

function exportCsv(array $peserta): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="peserta_rakerdinma_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, [
        'No',
        'Tanggal Daftar',
        'Nama',
        'NIP',
        'Nomor WA',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Jabatan',
        'Asal Lembaga',
        'Alamat Lembaga',
        'Alat Transportasi',
    ]);

    foreach ($peserta as $index => $row) {
        fputcsv($output, [
            $index + 1,
            $row['created_at'] ?? '',
            $row['nama'] ?? '',
            $row['nip'] ?? '',
            $row['nomor_wa'] ?? '',
            $row['tempat_lahir'] ?? '',
            $row['tanggal_lahir'] ?? '',
            $row['jabatan'] ?? '',
            $row['asal_lembaga'] ?? '',
            $row['alamat_lembaga'] ?? '',
            $row['alat_transportasi'] ?? '',
        ]);
    }

    fclose($output);
    exit;
}
