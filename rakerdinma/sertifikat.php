<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/certificate.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const SERTIFIKAT_SESSION_KEY = 'rakerdinma_sertifikat_peserta_id';

$errors = [];
$peserta = null;
$nomorWa = '';

if (isset($_GET['logout'])) {
    unset($_SESSION[SERTIFIKAT_SESSION_KEY]);
    header('Location: ' . url('rakerdinma/sertifikat'));
    exit;
}

if (isset($_GET['download']) || isset($_GET['preview'])) {
    $pesertaId = (int) ($_SESSION[SERTIFIKAT_SESSION_KEY] ?? 0);
    if ($pesertaId < 1) {
        header('Location: ' . url('rakerdinma/sertifikat'));
        exit;
    }

    $peserta = getPesertaById($pesertaId);
    if ($peserta === null) {
        unset($_SESSION[SERTIFIKAT_SESSION_KEY]);
        header('Location: ' . url('rakerdinma/sertifikat'));
        exit;
    }

    if (!sertifikatCanGenerate()) {
        http_response_code(503);
        echo 'Layanan sertifikat belum tersedia. Hubungi panitia.';
        exit;
    }

    outputSertifikatPng($peserta, isset($_GET['download']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomorWa = trim($_POST['nomor_wa'] ?? '');
    if ($nomorWa === '') {
        $errors[] = 'Nomor WhatsApp wajib diisi.';
    } else {
        $peserta = getPesertaByNomorWa($nomorWa);
        if ($peserta === null) {
            $errors[] = 'Nomor WhatsApp tidak ditemukan. Pastikan Anda sudah terdaftar di RAKERDINMA 2026.';
        } else {
            $_SESSION[SERTIFIKAT_SESSION_KEY] = (int) $peserta['id'];
        }
    }
} elseif (!empty($_SESSION[SERTIFIKAT_SESSION_KEY])) {
    $peserta = getPesertaById((int) $_SESSION[SERTIFIKAT_SESSION_KEY]);
    if ($peserta === null) {
        unset($_SESSION[SERTIFIKAT_SESSION_KEY]);
    }
}

function fieldValue(string $value): string
{
    return sanitize($value);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Download Sertifikat RAKERDINMA 2026 | LP Ma'arif NU Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-4 min-w-0">
        <img src="<?= url('image/logo.png') ?>" alt="Logo LP Ma'arif NU" class="w-12 h-12 rounded-full bg-white p-1 shrink-0">
        <div class="min-w-0">
          <h1 class="text-lg md:text-xl font-bold truncate">LP Ma'arif NU Kabupaten Magelang</h1>
          <p class="text-sm text-green-100">Sertifikat RAKERDINMA 2026</p>
        </div>
      </div>
      <a href="<?= url('rakerdinma') ?>" class="text-sm bg-green-900 hover:bg-green-950 px-3 py-2 rounded-lg transition shrink-0">
        ← Pendaftaran
      </a>
    </div>
  </header>

  <main class="max-w-5xl mx-auto px-6 py-10">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-green-100">
      <div class="bg-green-700 text-white px-8 py-6">
        <h2 class="text-xl md:text-2xl font-bold leading-snug">Download Sertifikat RAKERDINMA 2026</h2>
        <p class="text-green-100 mt-2">Masukkan nomor WhatsApp yang Anda daftarkan untuk mengunduh sertifikat.</p>
      </div>

      <div class="px-8 py-8">
        <?php if (!sertifikatCanGenerate()): ?>
          <div class="rounded-xl bg-amber-50 border border-amber-200 px-6 py-5 text-amber-900">
            <p class="font-semibold mb-1">Layanan sertifikat belum siap</p>
            <p class="text-sm">Pastikan ekstensi PHP GD aktif dan file template/font tersedia di server.</p>
          </div>
        <?php elseif ($peserta !== null): ?>
          <div class="space-y-6">
            <div class="rounded-xl bg-green-50 border border-green-200 px-6 py-5 text-green-900">
              <p class="text-sm text-green-700 mb-1">Data peserta ditemukan:</p>
              <p class="text-xl font-bold"><?= fieldValue($peserta['nama']) ?></p>
              <p class="text-sm mt-1"><?= fieldValue($peserta['asal_lembaga']) ?></p>
              <p class="text-xs text-green-700 mt-2">
                Nomor Sertifikat: <span class="font-semibold"><?= fieldValue(formatNomorSertifikat(getNomorSertifikatPeserta((int) $peserta['id']))) ?></span>
              </p>
              <p class="text-xs text-green-700">WA: <?= fieldValue($peserta['nomor_wa']) ?></p>
            </div>

            <div>
              <p class="text-sm font-semibold text-gray-700 mb-3">Pratinjau Sertifikat</p>
              <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 sm:p-4 overflow-hidden">
                <img src="<?= url('rakerdinma/sertifikat?preview=1') ?>"
                     alt="Pratinjau sertifikat <?= fieldValue($peserta['nama']) ?>"
                     class="w-full h-auto rounded-lg shadow-md border border-gray-200 bg-white">
              </div>
              <p class="text-xs text-gray-500 mt-2">Periksa nomor sertifikat, nama, dan asal lembaga. Jika sudah benar, unduh sertifikat di bawah.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
              <a href="<?= url('rakerdinma/sertifikat?download=1') ?>"
                 class="inline-flex items-center justify-center bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-xl shadow transition">
                Download Sertifikat (PNG)
              </a>
              <a href="<?= url('rakerdinma/sertifikat?logout=1') ?>"
                 class="inline-flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-6 py-3 rounded-xl transition">
                Ganti Nomor WA
              </a>
            </div>
          </div>
        <?php else: ?>
          <?php if (!empty($errors)): ?>
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 px-6 py-5 text-red-800">
              <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $error): ?>
                  <li><?= fieldValue($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" action="" class="space-y-6">
            <div>
              <label for="nomor_wa" class="block text-sm font-semibold text-gray-700 mb-2">
                NOMOR WHATSAPP <span class="text-red-500">*</span>
              </label>
              <input type="tel" id="nomor_wa" name="nomor_wa" required placeholder="08xxxxxxxxxx"
                     value="<?= fieldValue($nomorWa) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600">
              <p class="text-xs text-gray-500 mt-2">Gunakan nomor yang sama saat pendaftaran RAKERDINMA.</p>
            </div>
            <button type="submit"
                    class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-4 rounded-xl shadow transition">
              Cari Sertifikat
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </main>
</body>
</html>
