<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/pengkinian_data_functions.php';

$loginError = '';
$flashMessage = '';
$flashError = '';
$content = '';
$pageTitle = 'Admin Pembaruan Data Satuan';
$currentPage = trim($_GET['page'] ?? 'dashboard');
$extraHead = '';
$extraScripts = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['pengkinian_data_admin']);
    header('Location: ' . url('adminpengkinian/'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password']) && !isset($_POST['delete_id'])) {
    if (password_verify($_POST['admin_password'], ADMIN_PASSWORD_HASH)) {
        $_SESSION['pengkinian_data_admin'] = true;
        header('Location: ' . url('adminpengkinian/?page=dashboard'));
        exit;
    }
    $loginError = 'Password salah.';
}

if (!in_array($currentPage, ['dashboard', 'list', 'detail'], true)) {
    $currentPage = 'dashboard';
}

if (isPengkinianAdminLoggedIn() && isset($_GET['download_sk'])) {
    streamPengkinianSkFile((int) $_GET['download_sk']);
}

if (isPengkinianAdminLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    try {
        if (deletePengkinianData($deleteId)) {
            header('Location: ' . url('adminpengkinian/?page=list&deleted=1'));
            exit;
        }
        $flashError = 'Data tidak ditemukan.';
    } catch (PDOException $e) {
        $flashError = 'Gagal menghapus data.';
    }
}

if (isPengkinianAdminLoggedIn() && isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportPengkinianCsv(loadPengkinianData());
}

if (isset($_GET['deleted'])) {
    $flashMessage = 'Data berhasil dihapus.';
}

if (!isPengkinianAdminLoggedIn()) {
    ob_start();
    ?>
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg border border-green-100 p-8">
      <h2 class="text-xl font-bold text-green-800 mb-2">Login Admin Pembaruan Data</h2>
      <p class="text-gray-600 text-sm mb-6">Masuk untuk mengelola data satuan pendidikan.</p>
      <?php if ($loginError !== ''): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-700 text-sm"><?= sanitize($loginError) ?></div>
      <?php endif; ?>
      <form method="post" class="space-y-4">
        <div>
          <label for="admin_password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
          <input type="password" id="admin_password" name="admin_password" required
                 class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-green-600">
        </div>
        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg">Masuk</button>
      </form>
    </div>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/_layout.php';
    exit;
}

try {
    if ($currentPage === 'dashboard') {
        $rows = loadPengkinianData();
        $stats = getPengkinianDashboardStats($rows);
        $pageTitle = 'Dashboard Pembaruan Data';
        ob_start();
        require __DIR__ . '/views/dashboard.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'list') {
        $search = trim($_GET['q'] ?? '');
        $filters = [
            'kecamatan' => trim($_GET['kecamatan'] ?? ''),
        ];
        $rows = loadPengkinianData($search, $filters);
        $allRows = loadPengkinianData();
        $kecamatanOptions = pengkinianKecamatanOptions($allRows);
        $pageTitle = 'List Data Satuan Pendidikan';
        ob_start();
        require __DIR__ . '/views/list.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'detail') {
        $id = (int) ($_GET['id'] ?? 0);
        $row = getPengkinianDataById($id);
        if ($row === null) {
            header('Location: ' . url('adminpengkinian/?page=list'));
            exit;
        }
        $pageTitle = 'Detail Satuan Pendidikan';
        ob_start();
        require __DIR__ . '/views/detail.php';
        $content = ob_get_clean();
    }
} catch (PDOException $e) {
    $content = '<div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800">Koneksi database gagal.</div>';
}

require __DIR__ . '/_layout.php';
