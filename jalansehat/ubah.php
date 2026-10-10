<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/jalan_sehat_functions.php';

$errors = [];
$lookupPhone = '';
$candidates = [];

if (isset($_GET['batal'])) {
    jalanSehatRevokeEditAccess();
    header('Location: ' . url('jalansehat/ubah'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'lookup');

    if ($action === 'lookup') {
        $lookupPhone = trim((string) ($_POST['nomor_hp'] ?? ''));
        $norm = normalizeNomorWa($lookupPhone);
        if ($norm === '' || strlen($norm) < 9) {
            $errors[] = 'Masukkan nomor HP yang valid (contoh: 081234567890).';
        } else {
            $ids = findJalanSehatPendaftaranIdsByEditPhone($lookupPhone);
            if ($ids === []) {
                $errors[] = 'Tidak ada pendaftaran Jalan Sehat untuk nomor HP ini. Gunakan nomor WA saat daftar, nomor HP kepala madrasah, atau nomor HP kepsek di pengkinian data (nama madrasah harus sama).';
            } elseif (count($ids) === 1) {
                jalanSehatGrantEditAccess($lookupPhone, $ids);
                $_SESSION['jalan_sehat_editing_id'] = $ids[0];
                header('Location: ' . url('jalansehat/?edit=1'));
                exit;
            } else {
                jalanSehatGrantEditAccess($lookupPhone, $ids);
                foreach ($ids as $id) {
                    $row = getJalanSehatPendaftaranById($id);
                    if ($row !== null) {
                        $candidates[] = $row;
                    }
                }
            }
        }
    } elseif ($action === 'pilih') {
        $id = (int) ($_POST['pendaftaran_id'] ?? 0);
        if (!jalanSehatCanEditRegistration($id)) {
            $errors[] = 'Sesi verifikasi habis atau pilihan tidak valid. Masukkan nomor HP lagi.';
        } else {
            $_SESSION['jalan_sehat_editing_id'] = $id;
            header('Location: ' . url('jalansehat/?edit=1'));
            exit;
        }
    }
} elseif (isset($_SESSION['jalan_sehat_edit_allowed_ids']) && is_array($_SESSION['jalan_sehat_edit_allowed_ids'])) {
    foreach ($_SESSION['jalan_sehat_edit_allowed_ids'] as $id) {
        $row = getJalanSehatPendaftaranById((int) $id);
        if ($row !== null) {
            $candidates[] = $row;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?= siteHeadIcons() ?>
  <title>Ubah Pendaftaran Jalan Sehat | LP Ma'arif NU Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-4">
        <img src="<?= url('image/logo.png') ?>" alt="Logo LP Ma'arif NU" class="w-12 h-12 rounded-full bg-white p-1">
        <div>
          <h1 class="text-lg md:text-xl font-bold">LP Ma'arif NU Kabupaten Magelang</h1>
          <p class="text-sm text-green-100">Ubah data Jalan Sehat</p>
        </div>
      </div>
      <a href="<?= url('jalansehat/') ?>" class="text-sm bg-green-900 hover:bg-green-950 px-4 py-2 rounded-lg transition whitespace-nowrap">← Form Daftar</a>
    </div>
  </header>

  <main class="max-w-3xl w-full mx-auto px-6 py-10 flex-1">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-green-100">
      <div class="bg-green-700 text-white px-8 py-6">
        <h2 class="text-xl md:text-2xl font-bold leading-snug">Ubah Pendaftaran Jalan Sehat</h2>
        <p class="text-green-100 mt-2">Verifikasi dengan nomor HP untuk memperbarui data (misalnya ukuran kaos lengan panjang).</p>
      </div>

      <div class="px-8 py-8 space-y-6">
        <p class="text-sm text-gray-600 leading-relaxed">
          Masukkan <strong>nomor HP kepala madrasah</strong>, <strong>nomor WhatsApp</strong> yang diisi saat pendaftaran,
          atau <strong>nomor HP kepsek</strong> yang terdaftar di pengkinian data LP Ma'arif NU (nama madrasah harus cocok).
        </p>

        <?php if (!empty($errors)): ?>
          <div class="rounded-xl bg-red-50 border border-red-200 px-6 py-5 text-red-800">
            <ul class="list-disc list-inside space-y-1 text-sm">
              <?php foreach ($errors as $error): ?>
                <li><?= sanitize($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($candidates === []): ?>
          <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="lookup">
            <div>
              <label for="nomor_hp" class="block text-sm font-semibold text-gray-700 mb-2">Nomor HP</label>
              <input type="tel" id="nomor_hp" name="nomor_hp" required placeholder="08xxxxxxxxxx" maxlength="20"
                     value="<?= sanitize($lookupPhone) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 bg-white md:max-w-md">
            </div>
            <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg">Lanjut</button>
          </form>
        <?php else: ?>
          <div class="rounded-xl border border-green-200 bg-green-50/60 px-5 py-4 text-sm text-green-900">
            Ditemukan <strong><?= count($candidates) ?></strong> pendaftaran untuk nomor HP tersebut. Pilih madrasah yang ingin diubah.
          </div>
          <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="pilih">
            <?php foreach ($candidates as $row): ?>
              <label class="flex cursor-pointer rounded-xl border-2 border-gray-200 bg-white p-4 hover:border-green-500 has-[:checked]:border-green-600 has-[:checked]:bg-green-50 transition">
                <input type="radio" name="pendaftaran_id" value="<?= (int) $row['id'] ?>" required class="mt-1 mr-3">
                <span>
                  <span class="block font-bold text-green-800"><?= sanitize((string) $row['nama_madrasah']) ?></span>
                  <span class="block text-sm text-gray-600 mt-1">Kepala: <?= sanitize((string) $row['nama_kepala']) ?></span>
                  <span class="block text-xs text-gray-500 mt-1">Daftar: <?= sanitize((string) $row['created_at']) ?> · Kaos: <?= (int) $row['jumlah_kaos'] ?> pcs</span>
                </span>
              </label>
            <?php endforeach; ?>
            <div class="flex flex-wrap gap-3 pt-2">
              <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg">Edit data ini</button>
              <a href="<?= url('jalansehat/ubah?batal=1') ?>" class="inline-flex items-center px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">Ganti nomor HP</a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <footer class="bg-green-900 text-green-100 py-6 mt-10">
    <div class="max-w-3xl mx-auto px-6 text-center text-sm">© 2026 LP Ma'arif NU Kabupaten Magelang</div>
  </footer>

</body>
</html>
