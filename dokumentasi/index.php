<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$folderId = DOKUMENTASI_DRIVE_FOLDER_ID;
$folderUrl = DOKUMENTASI_DRIVE_FOLDER_URL;
$embedUrl = 'https://drive.google.com/embeddedfolderview?id=' . rawurlencode($folderId) . '#grid';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= sanitize(DOKUMENTASI_JUDUL) ?> | LP Ma'arif NU Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-4 min-w-0">
        <img src="<?= url('image/logo.png') ?>" alt="Logo LP Ma'arif NU" class="w-12 h-12 rounded-full bg-white p-1 shrink-0">
        <div class="min-w-0">
          <h1 class="text-lg md:text-xl font-bold truncate">LP Ma'arif NU Kabupaten Magelang</h1>
          <p class="text-sm text-green-100">Galeri Dokumentasi</p>
        </div>
      </div>
      <a href="<?= url() ?>" class="text-sm bg-green-900 hover:bg-green-950 px-3 py-2 rounded-lg transition shrink-0">
        ← Beranda
      </a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-6 py-10">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-green-100">
      <div class="bg-green-700 text-white px-8 py-6">
        <h2 class="text-xl md:text-2xl font-bold leading-snug"><?= sanitize(DOKUMENTASI_JUDUL) ?></h2>
        <p class="text-green-100 mt-2">Foto dokumentasi kegiatan RAKERDINMA LP Ma'arif NU Kabupaten Magelang.</p>
      </div>

      <div class="px-4 sm:px-8 py-8">
        <div class="rounded-xl border border-gray-200 overflow-hidden bg-gray-50">
          <iframe
            src="<?= sanitize($embedUrl) ?>"
            title="<?= sanitize(DOKUMENTASI_JUDUL) ?>"
            class="w-full border-0"
            style="height: min(70vh, 720px); min-height: 480px;"
            loading="lazy"
            allow="autoplay">
          </iframe>
        </div>

        <p class="text-sm text-gray-500 mt-4 text-center">
          Jika galeri tidak muncul, buka folder di
          <a href="<?= sanitize($folderUrl) ?>" target="_blank" rel="noopener noreferrer"
             class="text-green-700 font-medium hover:underline">Google Drive</a>.
        </p>
      </div>
    </div>
  </main>

  <footer class="bg-green-900 text-green-100 py-6 mt-10">
    <div class="max-w-6xl mx-auto px-6 text-center text-sm">
      © 2026 LP Ma'arif NU Kabupaten Magelang
    </div>
  </footer>

</body>
</html>
