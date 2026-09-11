<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

const BANNER_FILES_DIR = APP_ROOT . '/data/banner_harlah';
const BANNER_ZIP_PATH = APP_ROOT . '/docs/Paket_Banner_Logo_KIM_Medsos.zip';

$files = [
    'Petunjuk_Cetak_dan_Area_Foto.txt' => [
        'title' => 'Petunjuk_Cetak_dan_Area_Foto',
        'desc' => 'Panduan teks cetak banner dan penempatan area foto.',
        'kind' => 'text',
    ],
    'Banner_Logo_KIM_Medsos_3x1m_150dpi.jpg' => [
        'title' => 'Banner_Logo_KIM_Medsos_3x1m_150dpi',
        'desc' => 'File banner JPG resolusi 150 dpi (3×1 m) untuk medsos & digital.',
        'kind' => 'image',
    ],
    'Panduan_Area_Foto.jpg' => [
        'title' => 'Panduan_Area_Foto',
        'desc' => 'Visual panduan area foto pada banner.',
        'kind' => 'image',
    ],
    'Banner_Logo_KIM_Medsos_3x1m_Cetak.pdf' => [
        'title' => 'Banner_Logo_KIM_Medsos_3x1m_Cetak',
        'desc' => 'File PDF siap cetak banner 3×1 meter.',
        'kind' => 'pdf',
    ],
    'Pratinjau_Banner.jpg' => [
        'title' => 'Pratinjau_Banner',
        'desc' => 'Pratinjau tampilan banner Harlah Ke-97.',
        'kind' => 'image',
    ],
];

function bannerHarlahSafeName(string $name): ?string
{
    global $files;
    return isset($files[$name]) ? $name : null;
}

function bannerHarlahStreamFile(string $name, bool $inline = false): void
{
    $safe = bannerHarlahSafeName($name);
    if ($safe === null) {
        http_response_code(404);
        exit('File tidak ditemukan.');
    }

    $path = BANNER_FILES_DIR . '/' . $safe;
    if (!is_file($path)) {
        http_response_code(404);
        exit('File tidak ditemukan.');
    }

    $mime = match (pathinfo($safe, PATHINFO_EXTENSION)) {
        'jpg', 'jpeg' => 'image/jpeg',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain; charset=utf-8',
        default => 'application/octet-stream',
    };

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: public, max-age=86400');
    $disposition = $inline ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($safe) . '"');
    readfile($path);
    exit;
}

if (isset($_GET['download'])) {
    bannerHarlahStreamFile((string) $_GET['download'], false);
}

if (isset($_GET['preview'])) {
    $preview = (string) $_GET['preview'];
    if (($files[$preview]['kind'] ?? '') === 'image') {
        bannerHarlahStreamFile($preview, true);
    }
    http_response_code(404);
    exit;
}

if (isset($_GET['zip']) && $_GET['zip'] === '1') {
    if (!is_file(BANNER_ZIP_PATH)) {
        http_response_code(404);
        exit('Paket ZIP tidak tersedia.');
    }
    header('Content-Type: application/zip');
    header('Content-Length: ' . (string) filesize(BANNER_ZIP_PATH));
    header('Content-Disposition: attachment; filename="Paket_Banner_Logo_KIM_Medsos.zip"');
    readfile(BANNER_ZIP_PATH);
    exit;
}

$pageTitle = 'Download Banner Harlah 97';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= sanitize($pageTitle) ?> | LP Ma'arif NU Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-3 min-w-0">
        <img src="<?= url('image/logo.png') ?>" alt="Logo" class="w-11 h-11 rounded-full bg-white p-1 shrink-0">
        <div class="min-w-0">
          <h1 class="text-base sm:text-lg font-bold truncate">LP Ma'arif NU Kab. Magelang</h1>
          <p class="text-xs sm:text-sm text-green-100 truncate">Banner & Layout Harlah Ke-97</p>
        </div>
      </div>
      <a href="<?= url() ?>" class="text-sm bg-green-900 hover:bg-green-950 px-3 py-2 rounded-lg shrink-0">← Beranda</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex-1 w-full">
    <div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
      <div class="bg-gradient-to-r from-green-800 to-green-700 text-white px-6 py-5">
        <h2 class="text-xl md:text-2xl font-bold">Download Banner Harlah LP Ma'arif NU Ke-97</h2>
        <p class="text-green-100 text-sm mt-1">
          Paket banner & layout KIM medsos — unduh per file atau sekaligus dalam ZIP.
        </p>
      </div>

      <div class="p-4 sm:p-6">
        <div class="flex flex-wrap gap-3 mb-6">
          <a href="<?= url('banner-harlah/?zip=1') ?>"
             class="inline-flex items-center gap-2 bg-green-700 hover:bg-green-800 text-white font-semibold text-sm px-5 py-2.5 rounded-xl shadow transition">
            <span>📦</span> Unduh Semua (ZIP)
          </a>
          <a href="<?= url('twibbon') ?>"
             class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold text-sm px-5 py-2.5 rounded-xl transition">
            <span>🖼️</span> Bikin Twibbon
          </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
          <?php foreach ($files as $filename => $meta):
              $exists = is_file(BANNER_FILES_DIR . '/' . $filename);
              $downloadUrl = url('banner-harlah/?download=' . rawurlencode($filename));
          ?>
          <a href="<?= $exists ? sanitize($downloadUrl) : '#' ?>"
             class="group block rounded-xl border border-gray-200 bg-gray-50 hover:border-green-400 hover:shadow-md transition overflow-hidden <?= $exists ? '' : 'opacity-50 pointer-events-none' ?>">
            <div class="aspect-[4/3] bg-gray-200 flex items-center justify-center overflow-hidden">
              <?php if ($meta['kind'] === 'image' && $exists): ?>
                <img src="<?= url('banner-harlah/?preview=' . rawurlencode($filename)) ?>"
                     alt="<?= sanitize($meta['title']) ?>"
                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                     loading="lazy">
              <?php elseif ($meta['kind'] === 'pdf'): ?>
                <div class="flex flex-col items-center text-red-600">
                  <svg class="w-14 h-14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2l5 5h-5V4zM8 14h8v2H8v-2zm0-4h8v2H8v-2z"/>
                  </svg>
                  <span class="text-xs font-bold mt-1">PDF</span>
                </div>
              <?php else: ?>
                <div class="flex flex-col items-center text-gray-500 px-2 text-center">
                  <svg class="w-12 h-12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2l5 5h-5V4zM7 10h10v2H7v-2zm0 4h7v2H7v-2z"/>
                  </svg>
                  <span class="text-xs font-semibold mt-1">TXT</span>
                </div>
              <?php endif; ?>
            </div>
            <div class="p-3 border-t border-gray-200 bg-white">
              <p class="text-xs font-semibold text-gray-900 leading-snug line-clamp-2 group-hover:text-green-800">
                <?= sanitize($meta['title']) ?>
              </p>
              <p class="text-[10px] text-gray-500 mt-1 line-clamp-2 hidden sm:block"><?= sanitize($meta['desc']) ?></p>
              <p class="text-[10px] text-green-700 font-semibold mt-2">Unduh →</p>
            </div>
          </a>
          <?php endforeach; ?>
        </div>

        <div class="mt-8 rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-900">
          <p class="font-semibold mb-2">Petunjuk singkat</p>
          <ul class="list-disc list-inside space-y-1 text-green-800 text-xs sm:text-sm">
            <li>Gunakan file <strong>PDF</strong> atau <strong>JPG 150 dpi</strong> sesuai kebutuhan cetak atau medsos.</li>
            <li>Baca <strong>Petunjuk_Cetak_dan_Area_Foto</strong> sebelum mencetak banner ukuran 3×1 m.</li>
            <li>Lihat <strong>Panduan_Area_Foto</strong> untuk penempatan foto pada banner.</li>
          </ul>
        </div>
      </div>
    </div>
  </main>

  <footer class="bg-green-900 text-green-100 py-5 mt-8">
    <div class="max-w-6xl mx-auto px-6 text-center text-sm">
      © 2026 LP Ma'arif NU Kabupaten Magelang
    </div>
  </footer>

</body>
</html>
