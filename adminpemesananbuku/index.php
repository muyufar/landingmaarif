<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/pemesanan_functions.php';

$loginError = '';
$flashMessage = '';
$flashError = '';
$content = '';
$pageTitle = 'Admin Pemesanan Buku';
$currentPage = trim($_GET['page'] ?? 'dashboard');
$extraHead = '';
$extraScripts = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['pemesanan_buku_admin']);
    header('Location: ' . url('adminpemesananbuku/'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password']) && !isset($_POST['delete_id'])) {
    if (password_verify($_POST['admin_password'], ADMIN_PASSWORD_HASH)) {
        $_SESSION['pemesanan_buku_admin'] = true;
        header('Location: ' . url('adminpemesananbuku/?page=dashboard'));
        exit;
    }
    $loginError = 'Password salah.';
}

if (!in_array($currentPage, ['dashboard', 'list', 'detail'], true)) {
    $currentPage = 'dashboard';
}

if (isPemesananAdminLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    try {
        if (deletePemesanan($deleteId)) {
            header('Location: ' . url('adminpemesananbuku/?page=list&deleted=1'));
            exit;
        }
        $flashError = 'Data pemesanan tidak ditemukan.';
    } catch (PDOException $e) {
        $flashError = 'Gagal menghapus data.';
    }
}

if (isPemesananAdminLoggedIn() && isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportPemesananCsv(loadPemesanan());
}

if (isset($_GET['deleted'])) {
    $flashMessage = 'Pemesanan berhasil dihapus.';
}

if (!isPemesananAdminLoggedIn()) {
    ob_start();
    ?>
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg border border-green-100 p-8">
      <h2 class="text-xl font-bold text-green-800 mb-2">Login Admin Pemesanan</h2>
      <p class="text-gray-600 text-sm mb-6">Masuk untuk mengelola data pemesanan buku.</p>
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
        $rows = loadPemesanan();
        $stats = getPemesananDashboardStats($rows);
        $pageTitle = 'Dashboard Pemesanan';
        ob_start();
        require __DIR__ . '/views/dashboard.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'list') {
        $search = trim($_GET['q'] ?? '');
        $filters = [
            'jenjang' => trim($_GET['jenjang'] ?? ''),
            'jenis_layanan' => trim($_GET['jenis_layanan'] ?? ''),
        ];
        $rows = loadPemesanan($search, $filters);
        $pageTitle = 'List Pemesanan';
        ob_start();
        require __DIR__ . '/views/list.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'detail') {
        $id = (int) ($_GET['id'] ?? 0);
        $row = getPemesananById($id);
        if (!$row) {
            header('Location: ' . url('adminpemesananbuku/?page=list'));
            exit;
        }
        $pageTitle = 'Detail Pemesanan';
        ob_start();
        require __DIR__ . '/views/detail.php';
        $content = ob_get_clean();
    }
} catch (PDOException $e) {
    $flashError = 'Koneksi database gagal. Jalankan migration_pemesanan_upgrade.sql terlebih dahulu.';
    $content = '';
}

require __DIR__ . '/_layout.php';
