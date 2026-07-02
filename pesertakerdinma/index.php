<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/functions.php';

$loginError = '';
$flashMessage = '';
$flashError = '';
$dbError = '';
$formErrors = [];
$formData = null;

if (isset($_GET['logout'])) {
    unset($_SESSION['rakerdinma_admin']);
    header('Location: ' . url('pesertakerdinma/'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password']) && !isset($_POST['delete_id']) && !isset($_POST['save_peserta'])) {
    if (password_verify($_POST['admin_password'], ADMIN_PASSWORD_HASH)) {
        $_SESSION['rakerdinma_admin'] = true;
        header('Location: ' . url('pesertakerdinma/?page=dashboard'));
        exit;
    }
    $loginError = 'Password salah.';
}

$page = trim($_GET['page'] ?? 'dashboard');
if (!in_array($page, ['dashboard', 'list', 'form', 'detail'], true)) {
    $page = 'dashboard';
}

if (isAdminLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = trim($_POST['delete_id']);
    $params = ['page' => 'list', 'deleted' => '1'];
    if (!empty($_POST['q'])) {
        $params['q'] = trim($_POST['q']);
    }
    foreach (['kecamatan', 'jabatan', 'transportasi', 'jenis_lembaga'] as $f) {
        if (!empty($_POST['filter_' . $f])) {
            $params[$f] = trim($_POST['filter_' . $f]);
        }
    }

    try {
        if (deletePeserta($deleteId)) {
            header('Location: ' . url('pesertakerdinma/') . '?' . http_build_query($params));
            exit;
        }
        $flashError = 'Data peserta tidak ditemukan.';
    } catch (PDOException $e) {
        $flashError = 'Gagal menghapus data.';
    }
}

if (isAdminLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_peserta'])) {
    $editId = (int) ($_POST['id'] ?? 0);
    $result = validatePendaftaran($_POST, $editId > 0 ? $editId : null);

    if (!empty($result['errors'])) {
        $page = 'form';
        $formErrors = $result['errors'];
        $formData = array_merge(pesertaFormDefaults(), $result['data']);
        $isEdit = $editId > 0;
    } else {
        try {
            $ok = $editId > 0
                ? updatePeserta($editId, $result['data'])
                : addPeserta($result['data']);

            if ($ok) {
                $msg = $editId > 0 ? 'updated' : 'created';
                header('Location: ' . url('pesertakerdinma/?page=list&' . $msg . '=1'));
                exit;
            }
            if (nomorWaSudahTerdaftar($result['data']['nomor_wa'], $editId > 0 ? $editId : null)) {
                $flashError = 'Nomor WA sudah digunakan peserta lain.';
            } else {
                $flashError = 'Gagal menyimpan data.';
            }
            $page = 'form';
        } catch (PDOException $e) {
            $flashError = 'Gagal menyimpan data. Periksa koneksi database.';
            $page = 'form';
        }
    }
}

if (isAdminLoggedIn() && isset($_GET['export']) && $_GET['export'] === 'xls') {
    $search = trim($_GET['q'] ?? '');
    $filters = [
        'kecamatan' => trim($_GET['kecamatan'] ?? ''),
        'jabatan' => trim($_GET['jabatan'] ?? ''),
        'transportasi' => trim($_GET['transportasi'] ?? ''),
        'jenis_lembaga' => trim($_GET['jenis_lembaga'] ?? ''),
    ];
    exportXls(loadPeserta($search, $filters));
}

if (isAdminLoggedIn() && isset($_GET['export']) && $_GET['export'] === 'sertifikat') {
    require_once dirname(__DIR__) . '/includes/certificate.php';
    $pesertaId = (int) ($_GET['id'] ?? 0);
    $peserta = $pesertaId > 0 ? getPesertaById($pesertaId) : null;
    if ($peserta === null || !sertifikatCanGenerate()) {
        header('Location: ' . url('pesertakerdinma/?page=list'));
        exit;
    }
    outputSertifikatPng($peserta, true);
}

if (isset($_GET['deleted'])) {
    $flashMessage = 'Data peserta berhasil dihapus.';
}
if (isset($_GET['created'])) {
    $flashMessage = 'Peserta berhasil ditambahkan.';
}
if (isset($_GET['updated'])) {
    $flashMessage = 'Data peserta berhasil diperbarui.';
}

$content = '';
$pageTitle = 'Admin RAKERDINMA';
$currentPage = $page;
$extraHead = '';
$extraScripts = '';

if (!isAdminLoggedIn()) {
    ob_start();
    ?>
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg border border-green-100 p-8">
      <h2 class="text-xl font-bold text-green-800 mb-2">Login Admin</h2>
      <p class="text-gray-600 text-sm mb-6">Masuk untuk mengelola data peserta pendaftaran.</p>
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
    if ($page === 'dashboard') {
        $stats = getDashboardStats(loadPeserta());
        $pageTitle = 'Dashboard';
        ob_start();
        require __DIR__ . '/views/dashboard.php';
        $dashOutput = ob_get_clean();
        if (preg_match('/^(.*?)(<script[\s\S]*)$/s', $dashOutput, $m)) {
            $content = $m[1];
            $extraScripts = $m[2];
        } else {
            $content = $dashOutput;
        }
    } elseif ($page === 'list') {
        $search = trim($_GET['q'] ?? '');
        $filters = [
            'kecamatan' => trim($_GET['kecamatan'] ?? ''),
            'jabatan' => trim($_GET['jabatan'] ?? ''),
            'transportasi' => trim($_GET['transportasi'] ?? ''),
            'jenis_lembaga' => trim($_GET['jenis_lembaga'] ?? ''),
        ];
        $filterOptions = getFilterOptions();
        $peserta = loadPeserta($search, $filters);
        $pageTitle = 'List Peserta';
        ob_start();
        require __DIR__ . '/views/list.php';
        $content = ob_get_clean();
    } elseif ($page === 'form') {
        $editId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $isEdit = $editId > 0;
        $errors = $formErrors;

        if ($formData === null) {
            $existing = $isEdit ? getPesertaById($editId) : null;
            if ($isEdit && !$existing) {
                header('Location: ' . url('pesertakerdinma/?page=list'));
                exit;
            }
            $formData = pesertaFormDefaults($existing);
        }

        $pageTitle = $isEdit ? 'Edit Peserta' : 'Tambah Peserta';
        ob_start();
        require __DIR__ . '/views/form.php';
        $formOutput = ob_get_clean();
        if (preg_match('/^(.*?)(<script[\s\S]*)$/s', $formOutput, $m)) {
            $content = $m[1];
            $extraScripts = $m[2];
        } else {
            $content = $formOutput;
        }
    } elseif ($page === 'detail') {
        $id = (int) ($_GET['id'] ?? 0);
        $row = getPesertaById($id);
        if (!$row) {
            header('Location: ' . url('pesertakerdinma/?page=list'));
            exit;
        }
        $pageTitle = 'Detail Peserta';
        ob_start();
        require __DIR__ . '/views/detail.php';
        $content = ob_get_clean();
    }
} catch (PDOException $e) {
    $flashError = 'Koneksi database gagal. Pastikan database sudah diimport.';
    $content = '';
}

require __DIR__ . '/_layout.php';
