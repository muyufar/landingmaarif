<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/functions.php';

$loginError = '';
$deleteMessage = '';
$deleteError = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['rakerdinma_admin']);
    header('Location: /pesertakerdinma/');
    exit;
}

if (isAdminLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = trim($_POST['delete_id']);
    $searchAfterDelete = trim($_POST['q'] ?? $_GET['q'] ?? '');
    $redirectUrl = '/pesertakerdinma/';

    if ($searchAfterDelete !== '') {
        $redirectUrl .= '?q=' . urlencode($searchAfterDelete) . '&deleted=1';
    } else {
        $redirectUrl .= '?deleted=1';
    }

    try {
        if (deletePeserta($deleteId)) {
            header('Location: ' . $redirectUrl);
            exit;
        }
        $deleteError = 'Data peserta tidak ditemukan.';
    } catch (PDOException $e) {
        $deleteError = 'Gagal menghapus data. Periksa koneksi database.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password']) && !isset($_POST['delete_id'])) {
    if (password_verify($_POST['admin_password'], ADMIN_PASSWORD_HASH)) {
        $_SESSION['rakerdinma_admin'] = true;
        header('Location: /pesertakerdinma/');
        exit;
    }
    $loginError = 'Password salah.';
}

if (isAdminLoggedIn() && isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportCsv(loadPeserta());
}

if (isset($_GET['deleted'])) {
    $deleteMessage = 'Data peserta berhasil dihapus.';
}

$search = trim($_GET['q'] ?? '');
$dbError = '';
$peserta = [];

if (isAdminLoggedIn()) {
    try {
        $peserta = loadPeserta($search);
    } catch (PDOException $e) {
        $dbError = 'Koneksi database gagal. Pastikan file database/u700125577_maarifnu.sql sudah diimport.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Data Peserta RAKERDINMA | LP Ma'arif NU Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <img src="/image/logo.png" alt="Logo LP Ma'arif NU" class="w-12 h-12 rounded-full bg-white p-1">
        <div>
          <h1 class="text-lg md:text-xl font-bold">Data Peserta RAKERDINMA 2026</h1>
          <p class="text-sm text-green-100">LP Ma'arif NU Kabupaten Magelang</p>
        </div>
      </div>
      <?php if (isAdminLoggedIn()): ?>
        <a href="?logout=1" class="text-sm bg-green-900 hover:bg-green-950 px-4 py-2 rounded-lg transition">Logout</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-6 py-10">
    <?php if (!isAdminLoggedIn()): ?>
      <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg border border-green-100 p-8">
        <h2 class="text-xl font-bold text-green-800 mb-2">Login Admin</h2>
        <p class="text-gray-600 text-sm mb-6">Masuk untuk melihat data peserta pendaftaran.</p>

        <?php if ($loginError !== ''): ?>
          <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-700 text-sm">
            <?= sanitize($loginError) ?>
          </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
          <div>
            <label for="admin_password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
            <input type="password" id="admin_password" name="admin_password" required
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600">
          </div>
          <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg transition">
            Masuk
          </button>
        </form>
      </div>
    <?php else: ?>
      <div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h2 class="text-xl font-bold text-green-800">Daftar Peserta Terdaftar</h2>
            <p class="text-sm text-gray-500 mt-1">Total: <strong><?= count($peserta) ?></strong> peserta</p>
          </div>
          <div class="flex flex-col sm:flex-row gap-3">
            <form method="get" class="flex gap-2">
              <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Cari nama, NIP, lembaga..."
                     class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
              <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium">Cari</button>
            </form>
            <a href="?export=csv" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium text-center">
              Export CSV
            </a>
          </div>
        </div>

        <?php if ($dbError !== ''): ?>
          <div class="mx-6 mt-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-700 text-sm">
            <?= sanitize($dbError) ?>
          </div>
        <?php endif; ?>

        <?php if ($deleteMessage !== ''): ?>
          <div class="mx-6 mt-5 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-700 text-sm">
            <?= sanitize($deleteMessage) ?>
          </div>
        <?php endif; ?>

        <?php if ($deleteError !== ''): ?>
          <div class="mx-6 mt-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-700 text-sm">
            <?= sanitize($deleteError) ?>
          </div>
        <?php endif; ?>

        <?php if (empty($peserta)): ?>
          <div class="px-6 py-16 text-center text-gray-500">
            Belum ada peserta yang mendaftar.
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-green-50 text-green-900">
                <tr>
                  <th class="px-4 py-3 text-left font-semibold">No</th>
                  <th class="px-4 py-3 text-left font-semibold">Tanggal Daftar</th>
                  <th class="px-4 py-3 text-left font-semibold">Nama</th>
                  <th class="px-4 py-3 text-left font-semibold">NIP</th>
                  <th class="px-4 py-3 text-left font-semibold">Nomor WA</th>
                  <th class="px-4 py-3 text-left font-semibold">TTL</th>
                  <th class="px-4 py-3 text-left font-semibold">Jabatan</th>
                  <th class="px-4 py-3 text-left font-semibold">Asal Lembaga</th>
                  <th class="px-4 py-3 text-left font-semibold">Alamat Lembaga</th>
                  <th class="px-4 py-3 text-left font-semibold">Transportasi</th>
                  <th class="px-4 py-3 text-left font-semibold">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php foreach ($peserta as $index => $row): ?>
                  <tr class="hover:bg-gray-50 align-top">
                    <td class="px-4 py-3"><?= $index + 1 ?></td>
                    <td class="px-4 py-3 whitespace-nowrap"><?= sanitize($row['created_at'] ?? '-') ?></td>
                    <td class="px-4 py-3 font-medium"><?= sanitize($row['nama'] ?? '') ?></td>
                    <td class="px-4 py-3"><?= sanitize($row['nip'] ?? '-') ?></td>
                    <td class="px-4 py-3 whitespace-nowrap"><?= sanitize($row['nomor_wa'] ?? '') ?></td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <?= sanitize($row['tempat_lahir'] ?? '') ?>,
                      <?= sanitize($row['tanggal_lahir'] ?? '') ?>
                    </td>
                    <td class="px-4 py-3"><?= sanitize($row['jabatan'] ?? '') ?></td>
                    <td class="px-4 py-3"><?= sanitize($row['asal_lembaga'] ?? '') ?></td>
                    <td class="px-4 py-3 max-w-xs"><?= sanitize($row['alamat_lembaga'] ?? '') ?></td>
                    <td class="px-4 py-3"><?= sanitize($row['alat_transportasi'] ?? '') ?></td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <form method="post" class="inline" onsubmit="return confirm('Hapus data peserta ini?');">
                        <?php if ($search !== ''): ?>
                          <input type="hidden" name="q" value="<?= sanitize($search) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="delete_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-xs font-medium transition">
                          Hapus
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>

</body>
</html>
