<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/distribusi_lkpd_functions.php';

$loginError = '';
$flashMessage = '';
$flashError = '';
$content = '';
$pageTitle = 'Admin Distribusi LKPD';
$currentPage = trim($_GET['page'] ?? 'dashboard');

if (isset($_GET['logout'])) {
    logoutDistribusi();
    header('Location: ' . url('admindistribusi/'));
    exit;
}

if (isDistribusiSuperAdminLoggedIn() && isset($_GET['download_file'])) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password']) && !isset($_POST['create_petugas'])) {
    if (loginDistribusiSuperAdmin($_POST['admin_password'] ?? '')) {
        header('Location: ' . url('admindistribusi/?page=dashboard'));
        exit;
    }
    $loginError = 'Password super admin salah.';
}

if (!isDistribusiSuperAdminLoggedIn()) {
    ob_start();
    ?>
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg border p-8">
      <h2 class="text-xl font-bold text-green-800 mb-2">Login Super Admin</h2>
      <p class="text-sm text-gray-600 mb-6">Kelola distribusi LKPD & akun petugas distributor</p>
      <?php if ($loginError !== ''): ?><div class="mb-4 text-red-700 text-sm"><?= sanitize($loginError) ?></div><?php endif; ?>
      <form method="post" class="space-y-4">
        <input type="password" name="admin_password" required placeholder="Password admin" class="w-full rounded-lg border px-4 py-3">
        <button type="submit" class="w-full bg-green-700 text-white py-3 rounded-lg font-semibold">Masuk</button>
      </form>
    </div>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/_layout.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_petugas'])) {
    $result = createDistribusiPetugas(
        $_POST['username'] ?? '',
        $_POST['password'] ?? '',
        $_POST['nama'] ?? ''
    );
    if ($result['ok']) {
        $flashMessage = 'Akun petugas berhasil dibuat.';
    } else {
        $flashError = $result['error'] ?? 'Gagal membuat akun.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_petugas_id'])) {
    toggleDistribusiPetugas((int) $_POST['toggle_petugas_id'], ($_POST['toggle_action'] ?? '') === 'aktifkan');
    $flashMessage = 'Status petugas diperbarui.';
}

if (isDistribusiSuperAdminLoggedIn() && isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportDistribusiCsv(loadDistribusiSatuan());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $result = importDistribusiFile((string) $file['tmp_name'], (string) ($file['name'] ?? ''));
        if ($result['ok']) {
            $flashMessage = 'Import berhasil: ' . (int) $result['imported'] . ' baris.';
            if (!empty($result['errors'])) {
                $flashMessage .= ' Peringatan: ' . implode(' ', array_slice($result['errors'], 0, 3));
            }
        } else {
            $flashError = implode(' ', $result['errors'] ?? ['Import gagal.']);
        }
    } else {
        $flashError = 'Pilih file Excel/CSV untuk diimport.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_satuan'])) {
    $satuanId = (int) ($_POST['satuan_id'] ?? 0);
    if ($satuanId > 0) {
        $result = updateDistribusiSatuanAdmin($satuanId, $_POST);
        if ($result['ok']) {
            $flashMessage = 'Data satuan berhasil diperbarui.';
            header('Location: ' . url('admindistribusi/?page=detail&id=' . (int) $result['id']));
            exit;
        }
        $flashError = $result['error'] ?? 'Gagal memperbarui data.';
        $currentPage = 'edit';
        $_GET['id'] = (string) $satuanId;
    } else {
        $result = createDistribusiSatuanAdmin($_POST);
        if ($result['ok']) {
            $flashMessage = 'Satuan baru berhasil ditambahkan.';
            header('Location: ' . url('admindistribusi/?page=detail&id=' . (int) $result['id']));
            exit;
        }
        $flashError = $result['error'] ?? 'Gagal menambah satuan.';
        $currentPage = 'create';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_satuan_id'])) {
    $deleteId = (int) $_POST['delete_satuan_id'];
    $result = deleteDistribusiSatuanAdmin($deleteId);
    if ($result['ok']) {
        $flashMessage = 'Data satuan berhasil dihapus.';
        header('Location: ' . url('admindistribusi/?page=list'));
        exit;
    }
    $flashError = $result['error'] ?? 'Gagal menghapus data.';
    $currentPage = trim($_POST['_return_page'] ?? 'list');
    if ($currentPage === 'detail') {
        $_GET['id'] = (string) $deleteId;
    }
}

if (!in_array($currentPage, ['dashboard', 'import', 'list', 'detail', 'petugas', 'create', 'edit'], true)) {
    $currentPage = 'dashboard';
}

try {
    if ($currentPage === 'dashboard') {
        $stats = getDistribusiDashboardStats();
        $pageTitle = 'Dashboard Distribusi';
        ob_start();
        require __DIR__ . '/views/dashboard.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'import') {
        $pageTitle = 'Import Data Satuan';
        ob_start();
        require __DIR__ . '/views/import.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'petugas') {
        $petugasList = loadDistribusiPetugas();
        $pageTitle = 'Kelola Akun Petugas';
        ob_start();
        require __DIR__ . '/views/petugas.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'list') {
        $search = trim($_GET['q'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $kecamatanFilter = trim($_GET['kecamatan'] ?? '');
        $kecamatanOptions = loadDistribusiKecamatanOptions();
        $rows = loadDistribusiSatuan(
            $search,
            $statusFilter !== '' ? $statusFilter : null,
            $kecamatanFilter !== '' ? $kecamatanFilter : null
        );
        $pageTitle = 'Monitoring Distribusi';
        ob_start();
        require __DIR__ . '/views/list.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'detail') {
        $id = (int) ($_GET['id'] ?? 0);
        $satuan = getSatuanById($id);
        if ($satuan === null) {
            header('Location: ' . url('admindistribusi/?page=list'));
            exit;
        }
        $totalTerima = getTotalTerimaSatuan($id);
        $totalTerimaGuru = getTotalTerimaGuruSatuan($id);
        $pengirimanList = loadPengirimanSatuan($id);
        $pengkinian = getPengkinianByNpsn((string) $satuan['npsn']);
        $pageTitle = 'Detail Satuan';
        ob_start();
        require __DIR__ . '/views/detail.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'create') {
        $satuan = [];
        $isEdit = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_satuan']) && !empty($flashError)) {
            $satuan = $_POST;
        }
        $pageTitle = 'Tambah Satuan';
        ob_start();
        require __DIR__ . '/views/form.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'edit') {
        $id = (int) ($_GET['id'] ?? 0);
        $satuan = getSatuanById($id);
        if ($satuan === null) {
            header('Location: ' . url('admindistribusi/?page=list'));
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_satuan']) && !empty($flashError)) {
            $satuan = array_merge($satuan, $_POST);
            $satuan['id'] = $id;
        }
        $isEdit = true;
        $pageTitle = 'Edit Satuan';
        ob_start();
        require __DIR__ . '/views/form.php';
        $content = ob_get_clean();
    }
} catch (Throwable $e) {
    $content = '<div class="text-red-700">' . sanitize($e->getMessage()) . '</div>';
}

require __DIR__ . '/_layout.php';
