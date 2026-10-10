<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/jalan_sehat_functions.php';

$loginError = '';
$flashMessage = '';
$flashError = '';
$content = '';
$pageTitle = 'Admin Jalan Sehat HSN 2026';
$currentPage = trim($_GET['page'] ?? 'dashboard');
$extraHead = '';
$extraScripts = '';

if (isset($_GET['logout'])) {
    logoutMaarifAdmin();
    header('Location: ' . url('admin/'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password']) && !isset($_POST['action'])) {
    if (password_verify($_POST['admin_password'], ADMIN_PASSWORD_HASH)) {
        loginMaarifAdmin();
        header('Location: ' . url('adminjalansehat/?page=dashboard'));
        exit;
    }
    $loginError = 'Password salah.';
}

syncMaarifAdminSession();

if (!in_array($currentPage, ['dashboard', 'list', 'detail', 'edit', 'pengaturan'], true)) {
    $currentPage = 'dashboard';
}

$paketFormErrors = [];
$paketFormData = null;
$pendaftaranFormErrors = [];
$pendaftaranFormData = null;

if (isJalanSehatAdminLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = (string) $_POST['action'];
    try {
        if ($action === 'delete_pendaftaran') {
            if (deleteJalanSehatPendaftaran((int) ($_POST['id'] ?? 0))) {
                header('Location: ' . url('adminjalansehat/?page=list&msg=deleted'));
                exit;
            }
            $flashError = 'Data tidak ditemukan.';
        } elseif ($action === 'save_pengaturan') {
            saveJalanSehatPengaturan($_POST);
            header('Location: ' . url('adminjalansehat/?page=pengaturan&msg=settings'));
            exit;
        } elseif ($action === 'save_paket') {
            $paketId = (int) ($_POST['paket_id'] ?? 0);
            $result = validateJalanSehatPaket($_POST);
            if (empty($result['errors'])) {
                saveJalanSehatPaket($result['data'], $paketId > 0 ? $paketId : null);
                header('Location: ' . url('adminjalansehat/?page=pengaturan&msg=paket'));
                exit;
            }
            $paketFormErrors = $result['errors'];
            $paketFormData = $result['data'] + ['id' => $paketId];
            $currentPage = 'pengaturan';
        } elseif ($action === 'delete_paket') {
            deleteJalanSehatPaket((int) ($_POST['paket_id'] ?? 0));
            header('Location: ' . url('adminjalansehat/?page=pengaturan&msg=paket_deleted'));
            exit;
        } elseif ($action === 'toggle_paket') {
            toggleJalanSehatPaket((int) ($_POST['paket_id'] ?? 0));
            header('Location: ' . url('adminjalansehat/?page=pengaturan&msg=paket'));
            exit;
        } elseif ($action === 'save_pendaftaran') {
            $pendaftaranId = (int) ($_POST['id'] ?? 0);
            $settingsPost = getJalanSehatPengaturan();
            $paketAktifPost = loadJalanSehatPaket(true);
            $result = validateJalanSehat($_POST, $settingsPost, $paketAktifPost, $pendaftaranId);
            if (empty($result['errors'])) {
                updateJalanSehatPendaftaran($pendaftaranId, $result['data']);
                header('Location: ' . url('adminjalansehat/?page=detail&id=' . $pendaftaranId . '&msg=updated'));
                exit;
            }
            $pendaftaranFormErrors = $result['errors'];
            $pendaftaranFormData = $result['data'];
            $currentPage = 'edit';
        }
    } catch (PDOException $e) {
        $flashError = 'Gagal memproses data. Periksa koneksi database.';
    }
}

$flashMessages = [
    'deleted' => 'Data pendaftaran berhasil dihapus.',
    'settings' => 'Pengaturan berhasil disimpan.',
    'paket' => 'Paket berhasil disimpan.',
    'paket_deleted' => 'Paket berhasil dihapus.',
    'updated' => 'Data pendaftaran berhasil diperbarui.',
];
if (isset($_GET['msg'], $flashMessages[$_GET['msg']])) {
    $flashMessage = $flashMessages[$_GET['msg']];
}

if (!isJalanSehatAdminLoggedIn()) {
    ob_start();
    ?>
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg border border-green-100 p-8">
      <h2 class="text-xl font-bold text-green-800 mb-2">Login Admin Jalan Sehat</h2>
      <p class="text-gray-600 text-sm mb-6">Masuk untuk mengelola pendaftaran Jalan Sehat HSN 2026.</p>
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
    $settings = getJalanSehatPengaturan();

    $search = trim($_GET['q'] ?? '');
    $filters = [
        'paket' => trim($_GET['paket'] ?? ''),
        'kecamatan' => trim($_GET['kecamatan'] ?? ''),
        'kelurahan' => trim($_GET['kelurahan'] ?? ''),
        'urut' => array_key_exists($_GET['urut'] ?? '', jalanSehatUrutanOptions()) ? (string) $_GET['urut'] : 'terbaru',
    ];

    if (isset($_GET['export']) && $_GET['export'] === 'xls') {
        exportJalanSehatXls(loadJalanSehatPendaftaran($search, $filters), $settings);
    }

    if ($currentPage === 'dashboard') {
        $rows = loadJalanSehatPendaftaran();
        $ringkasan = getJalanSehatRingkasan($rows, $settings);
        $paketAktifCount = count(loadJalanSehatPaket(true));
        $pageTitle = 'Dashboard Jalan Sehat HSN 2026';
        ob_start();
        require __DIR__ . '/views/dashboard.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'list') {
        $rows = loadJalanSehatPendaftaran($search, $filters);
        $ringkasan = getJalanSehatRingkasan($rows, $settings);
        $paketList = loadJalanSehatPaket();
        $wilayahOptions = getJalanSehatWilayahOptions();
        $exportQuery = http_build_query(array_filter(
            ['export' => 'xls', 'q' => $search] + $filters,
            static fn ($v) => $v !== ''
        ));
        $pageTitle = 'Data Pendaftar Jalan Sehat';
        ob_start();
        require __DIR__ . '/views/list.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'detail') {
        $row = getJalanSehatPendaftaranById((int) ($_GET['id'] ?? 0));
        if ($row === null) {
            header('Location: ' . url('adminjalansehat/?page=list'));
            exit;
        }
        $pageTitle = 'Detail Pendaftar Jalan Sehat';
        ob_start();
        require __DIR__ . '/views/detail.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'edit') {
        $editId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $row = getJalanSehatPendaftaranById($editId);
        if ($row === null) {
            header('Location: ' . url('adminjalansehat/?page=list'));
            exit;
        }
        $formData = $pendaftaranFormData !== null
            ? array_merge(jalanSehatFormDataFromRow($row), $pendaftaranFormData)
            : jalanSehatFormDataFromRow($row);
        $formErrors = $pendaftaranFormErrors;
        $paketAktif = loadJalanSehatPaket(true);
        $pageTitle = 'Edit Pendaftaran Jalan Sehat';
        ob_start();
        require __DIR__ . '/views/edit.php';
        $content = ob_get_clean();
        ob_start();
        require dirname(__DIR__) . '/pesertakerdinma/_wilayah_registrasi_script.php';
        ?>
<script>
(function () {
  const inputs = document.querySelectorAll('.js-kaos-input');
  const totalEl = document.getElementById('total-kaos');
  function update() {
    let t = 0;
    inputs.forEach(function (el) {
      const n = parseInt(el.value, 10);
      if (Number.isFinite(n) && n > 0) t += n;
    });
    if (totalEl) totalEl.textContent = String(t);
  }
  inputs.forEach(function (el) { el.addEventListener('input', update); });
  update();
})();
</script>
        <?php
        $extraScripts = ob_get_clean();
    } elseif ($currentPage === 'pengaturan') {
        $paketList = loadJalanSehatPaket();
        if ($paketFormData === null && isset($_GET['edit_paket'])) {
            $paketFormData = getJalanSehatPaketById((int) $_GET['edit_paket']);
        }
        $pageTitle = 'Pengaturan Paket & Surat Edaran';
        ob_start();
        require __DIR__ . '/views/pengaturan.php';
        $content = ob_get_clean();
    }
} catch (PDOException $e) {
    $content = '<div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800">Koneksi database gagal.</div>';
}

require __DIR__ . '/_layout.php';
