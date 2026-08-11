<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/admin_functions.php';

$loginError = '';
$flashMessage = '';
$flashError = '';
$content = '';
$pageTitle = 'Portal Admin';
$currentPage = trim($_GET['page'] ?? 'dashboard');
$formErrors = [];
$formData = null;

if (isset($_GET['logout'])) {
    logoutMaarifAdmin();
    header('Location: ' . url('admin/'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password']) && !isset($_POST['save_berita']) && !isset($_POST['delete_berita_id'])) {
    if (password_verify($_POST['admin_password'], ADMIN_PASSWORD_HASH)) {
        loginMaarifAdmin();
        header('Location: ' . url('admin/?page=dashboard'));
        exit;
    }
    $loginError = 'Password salah.';
}

syncMaarifAdminSession();

if (!in_array($currentPage, ['dashboard', 'berita', 'berita-form'], true)) {
    $currentPage = 'dashboard';
}

$isLoggedIn = isMaarifAdminLoggedIn();

if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_berita_id'])) {
    try {
        if (deleteBerita((int) $_POST['delete_berita_id'])) {
            header('Location: ' . url('admin/?page=berita&deleted=1'));
            exit;
        }
        $flashError = 'Berita tidak ditemukan.';
    } catch (PDOException) {
        $flashError = 'Gagal menghapus berita.';
    }
}

if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_berita'])) {
    $editId = (int) ($_POST['id'] ?? 0);
    $result = validateBerita($_POST);

    if (!empty($result['errors'])) {
        $currentPage = 'berita-form';
        $formErrors = $result['errors'];
        $formData = array_merge(beritaFormDefaults(), $result['data']);
        if ($editId > 0) {
            $existing = getBeritaById($editId);
            $formData['gambar'] = $existing['gambar'] ?? '';
        }
    } else {
        try {
            $upload = handleBeritaGambarUpload($_FILES['gambar'] ?? [], $editId > 0 ? (getBeritaById($editId)['gambar'] ?? null) : null);
            if ($upload['error'] !== null) {
                $currentPage = 'berita-form';
                $formErrors = [$upload['error']];
                $formData = array_merge(beritaFormDefaults(), $result['data']);
                $formData['gambar'] = $editId > 0 ? (getBeritaById($editId)['gambar'] ?? '') : '';
            } else {
                $payload = $result['data'];
                $payload['gambar'] = $upload['path'] ?? '';

                $ok = $editId > 0 ? updateBerita($editId, $payload) : addBerita($payload);
                if ($ok) {
                    $flag = $editId > 0 ? 'updated' : 'created';
                    header('Location: ' . url('admin/?page=berita&' . $flag . '=1'));
                    exit;
                }
                $flashError = 'Gagal menyimpan berita.';
                $currentPage = 'berita-form';
            }
        } catch (PDOException) {
            $flashError = 'Gagal menyimpan berita. Pastikan tabel berita sudah diimport.';
            $currentPage = 'berita-form';
            $formData = array_merge(beritaFormDefaults(), $result['data']);
        }
    }
}

if (isset($_GET['deleted'])) {
    $flashMessage = 'Berita berhasil dihapus.';
}
if (isset($_GET['created'])) {
    $flashMessage = 'Berita berhasil ditambahkan.';
}
if (isset($_GET['updated'])) {
    $flashMessage = 'Berita berhasil diperbarui.';
}

if (!$isLoggedIn) {
    ob_start();
    ?>
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg border border-green-100 p-8">
      <h2 class="text-xl font-bold text-green-800 mb-2">Login Portal Admin</h2>
      <p class="text-gray-600 text-sm mb-6">Satu login untuk semua modul: Berita, RAKERDINMA, Pemesanan, Pengkinian, dan Distribusi.</p>
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
        $stats = getAdminHubStats();
        $modules = getAdminModules();
        $latestBerita = loadBerita('', '');
        $latestBerita = array_slice($latestBerita, 0, 5);
        $pageTitle = 'Dashboard Admin';
        ob_start();
        require __DIR__ . '/views/dashboard.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'berita') {
        $search = trim($_GET['q'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $rows = loadBerita($search, $statusFilter);
        $pageTitle = 'Kelola Berita';
        ob_start();
        require __DIR__ . '/views/berita_list.php';
        $content = ob_get_clean();
    } elseif ($currentPage === 'berita-form') {
        $editId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $isEdit = $editId > 0;
        $errors = $formErrors;

        if ($formData === null) {
            $existing = $isEdit ? getBeritaById($editId) : null;
            if ($isEdit && !$existing) {
                header('Location: ' . url('admin/?page=berita'));
                exit;
            }
            $formData = beritaFormDefaults($existing);
        }

        $pageTitle = $isEdit ? 'Edit Berita' : 'Tulis Berita';
        ob_start();
        require __DIR__ . '/views/berita_form.php';
        $content = ob_get_clean();
    }
} catch (PDOException) {
    $flashError = 'Koneksi database gagal. Import database/migration_berita.sql di phpMyAdmin.';
    $content = '';
}

require __DIR__ . '/_layout.php';
