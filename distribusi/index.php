<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/distribusi_lkpd_functions.php';

$loginError = '';
$flashMessage = $_GET['msg'] ?? '';
$flashError = '';
$content = '';
$pageTitle = 'Distribusi LKPD';
$currentPage = trim($_GET['page'] ?? 'dashboard');
$petugas = getDistribusiPetugasSession();
$extraScripts = '';

if (isset($_GET['logout'])) {
    logoutDistribusi();
    header('Location: ' . url('distribusi/'));
    exit;
}

if (isDistribusiPetugasLoggedIn() && isset($_GET['download_file'])) {
    $pengiriman = getPengirimanById((int) ($_GET['pengiriman_id'] ?? 0));
    $type = $_GET['download_file'] ?? '';
    if ($pengiriman !== null) {
        $path = $type === 'sekolah'
            ? ($pengiriman['file_surat_jalan_sekolah'] ?? '')
            : ($pengiriman['file_surat_jalan_distributor'] ?? '');
        if ($path !== '') {
            streamDistribusiFile($path);
        }
    }
    http_response_code(404);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_username'])) {
    if (loginDistribusiPetugas($_POST['login_username'] ?? '', $_POST['login_password'] ?? '')) {
        header('Location: ' . url('distribusi/?page=dashboard'));
        exit;
    }
    $loginError = 'Username atau password salah, atau akun nonaktif.';
}

if (!isDistribusiPetugasLoggedIn()) {
    ob_start();
    ?>
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg border border-green-100 p-8">
      <h2 class="text-xl font-bold text-green-800 mb-2">Login Petugas Distribusi</h2>
      <p class="text-sm text-gray-600 mb-6">Tracking pendistribusian buku LKPD MI Ma'arif NU Magelang</p>
      <?php if ($loginError !== ''): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-700 text-sm"><?= sanitize($loginError) ?></div>
      <?php endif; ?>
      <form method="post" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold mb-2">Username</label>
          <input type="text" name="login_username" required class="w-full rounded-lg border px-4 py-3 focus:ring-2 focus:ring-green-600">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Password</label>
          <input type="password" name="login_password" required class="w-full rounded-lg border px-4 py-3 focus:ring-2 focus:ring-green-600">
        </div>
        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg">Masuk</button>
      </form>
    </div>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/_layout.php';
    exit;
}

$petugas = getDistribusiPetugasSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispatch_npsn'])) {
    $result = dispatchDistribusiLkpd((int) $petugas['id'], $_POST['dispatch_npsn'] ?? '');
    if ($result['ok']) {
        $_SESSION['distribusi_wa_links'] = $result['wa']['results'] ?? [];
        header('Location: ' . url('distribusi/?page=kirim&success=1&pid=' . (int) $result['pengiriman_id']));
        exit;
    }
    $flashError = $result['error'] ?? 'Gagal memproses pengiriman.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_pengiriman_id'])) {
    $result = receiveDistribusiLkpd(
        (int) $petugas['id'],
        (int) $_POST['receive_pengiriman_id'],
        $_POST,
        $_FILES
    );
    if ($result['ok']) {
        $msg = $result['lengkap']
            ? 'Penerimaan lengkap. Status: Done.'
            : 'Penerimaan tercatat. Masih kurang — status Receive.';
        header('Location: ' . url('distribusi/?page=terima&msg=' . rawurlencode($msg)));
        exit;
    }
    $flashError = $result['error'] ?? 'Gagal mencatat penerimaan.';
}

if (!in_array($currentPage, ['dashboard', 'kirim', 'terima', 'list', 'detail'], true)) {
    $currentPage = 'dashboard';
}

try {
    if ($currentPage === 'dashboard') {
        $stats = getDistribusiDashboardStats();
        $deliveryRows = loadDistribusiSatuan('', DIST_STATUS_DELIVERY);
        $pageTitle = 'Dashboard Distribusi';
        ob_start();
        require __DIR__ . '/views/dashboard.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'kirim') {
        $waLinks = $_SESSION['distribusi_wa_links'] ?? [];
        if (isset($_GET['success'])) {
            unset($_SESSION['distribusi_wa_links']);
        }
        $pageTitle = 'Kirim Buku (Packing → Delivery)';
        ob_start();
        require __DIR__ . '/views/kirim.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'terima') {
        $deliveryRows = loadDistribusiSatuan('', DIST_STATUS_DELIVERY);
        $pageTitle = 'Terima Buku (Delivery → Receive/Done)';
        ob_start();
        require __DIR__ . '/views/terima.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'list') {
        $search = trim($_GET['q'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $rows = loadDistribusiSatuan($search, $statusFilter !== '' ? $statusFilter : null);
        $pageTitle = 'List Satuan Pendidikan';
        ob_start();
        require __DIR__ . '/views/list.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'detail') {
        $id = (int) ($_GET['id'] ?? 0);
        $satuan = getSatuanById($id);
        if ($satuan === null) {
            header('Location: ' . url('distribusi/?page=list'));
            exit;
        }
        $totalTerima = getTotalTerimaSatuan($id);
        $kurang = satuanKurangDetail($satuan, $totalTerima);
        $pengirimanList = loadPengirimanSatuan($id);
        $pengkinian = getPengkinianByNpsn((string) $satuan['npsn']);
        $pageTitle = 'Detail Satuan';
        ob_start();
        require __DIR__ . '/views/detail.php';
        $content = ob_get_clean();
    }
} catch (Throwable $e) {
    $content = '<div class="text-red-700">Error: ' . sanitize($e->getMessage()) . '</div>';
}

require __DIR__ . '/_layout.php';
