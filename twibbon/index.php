<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

/** @return array<string, array{label: string, subtitle: string, file: string, size: int, hole: array, downloadName: string, hashtags: string, tips: list<string>}> */
function twibbonTemplates(): array
{
    return [
        'harlah' => [
            'label' => 'Harlah LP Ma\'arif NU Ke-97',
            'subtitle' => 'Twibbon peringatan Harlah Lembaga Pendidikan Ma\'arif NU',
            'file' => 'Twibbon_KIM_Mint_2400.png',
            'size' => 2400,
            'hole' => ['type' => 'rect', 'x' => 250, 'y' => 0, 'w' => 2150, 'h' => 1912],
            'downloadName' => 'Twibbon_Harlah97_LP_Maarif_NU.png',
            'hashtags' => '#Harlah97 #LPMaarifNU #MaarifNUMagelang',
            'tips' => [
                'Gunakan foto portrait dengan wajah terang dan latar sederhana.',
                'Pastikan wajah berada di tengah area foto sebelum unduh.',
            ],
        ],
        'harisantri' => [
            'label' => 'Hari Santri 2026',
            'subtitle' => 'Twibbon Hari Santri — Santri Menguatkan Negeri',
            'file' => 'Twibbon_Hari_Santri_2026.png',
            'size' => 1080,
            'hole' => ['type' => 'circle', 'cx' => 729, 'cy' => 521, 'r' => 211],
            'downloadName' => 'Twibbon_Hari_Santri_2026_LP_Maarif_NU.png',
            'hashtags' => '#HariSantri2026 #SantriMenguatkanNegeri #LPMaarifNU #MaarifNUMagelang',
            'tips' => [
                'Posisikan wajah di lingkaran foto kanan pada frame.',
                'Gunakan foto portrait (close-up) agar pas di area bulat.',
            ],
        ],
    ];
}

$templates = twibbonTemplates();
$selectedKey = trim((string) ($_GET['t'] ?? 'harlah'));
if (!isset($templates[$selectedKey])) {
    $selectedKey = 'harlah';
}
$selected = $templates[$selectedKey];
$templateFile = dirname(__DIR__) . '/image/' . $selected['file'];
$templateUrl = url('image/' . $selected['file']);
$templateExists = is_file($templateFile);
$pageTitle = $selected['label'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?= siteHeadIcons() ?>
  <?= siteThemeHead() ?>
  <title>Bikin Twibbon — <?= sanitize($pageTitle) ?> | LP Ma'arif NU Magelang</title>
  <?= siteTailwindScript() ?>
  <style>
    #preview-wrap { touch-action: none; user-select: none; }
    #preview-canvas { max-width: 100%; height: auto; display: block; margin: 0 auto; border-radius: 0.75rem; }
  </style>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-3 min-w-0">
        <img src="<?= url('image/logo.png') ?>" alt="Logo" class="w-11 h-11 rounded-full bg-white p-1 shrink-0">
        <div class="min-w-0">
          <h1 class="text-base sm:text-lg font-bold truncate">Twibbon LP Ma'arif NU</h1>
          <p class="text-xs sm:text-sm text-green-100 truncate">Kabupaten Magelang</p>
        </div>
      </div>
      <a href="<?= url('dashboard') ?>" class="text-sm bg-green-900 hover:bg-green-950 px-3 py-2 rounded-lg shrink-0">
        ← Dashboard
      </a>
    </div>
  </header>

  <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 flex-1 w-full">
    <div class="mb-6">
      <p class="text-sm font-semibold text-gray-700 mb-3">Pilih desain twibbon</p>
      <div class="grid sm:grid-cols-2 gap-3">
        <?php foreach ($templates as $key => $tpl): ?>
          <?php
          $thumbFile = dirname(__DIR__) . '/image/' . $tpl['file'];
          $isActive = $key === $selectedKey;
          ?>
          <a href="<?= url('twibbon/?t=' . rawurlencode($key)) ?>"
             class="block rounded-xl border-2 p-4 transition <?= $isActive ? 'border-green-600 bg-green-50 shadow-md' : 'border-gray-200 bg-white hover:border-green-400' ?>">
            <div class="flex gap-3 items-start">
              <?php if (is_file($thumbFile)): ?>
                <img src="<?= sanitize(url('image/' . $tpl['file'])) ?>" alt=""
                     class="w-16 h-16 rounded-lg object-cover border border-gray-200 shrink-0 bg-gray-100">
              <?php endif; ?>
              <div class="min-w-0">
                <p class="font-bold text-green-900 text-sm leading-snug"><?= sanitize($tpl['label']) ?></p>
                <p class="text-xs text-gray-600 mt-1"><?= sanitize($tpl['subtitle']) ?></p>
                <?php if ($isActive): ?>
                  <span class="inline-block mt-2 text-xs font-semibold text-green-800">● Dipilih</span>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (!$templateExists): ?>
      <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-6 text-sm">
        Template twibbon belum tersedia. Hubungi administrator.
      </div>
    <?php else: ?>
      <div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
        <div class="bg-gradient-to-r from-green-800 to-green-700 text-white px-6 py-5">
          <h2 class="text-xl font-bold">Bikin Twibbon — <?= sanitize($selected['label']) ?></h2>
          <p class="text-green-100 text-sm mt-1">
            Upload foto Anda, sesuaikan posisi, lalu unduh twibbon siap dibagikan di media sosial.
          </p>
        </div>

        <div class="p-4 sm:p-6 space-y-6">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">1. Pilih foto</label>
            <label class="flex flex-col items-center justify-center w-full min-h-[120px] border-2 border-dashed border-green-300 rounded-xl bg-green-50/50 hover:bg-green-50 cursor-pointer transition">
              <span class="text-3xl mb-2">📷</span>
              <span class="text-sm font-medium text-green-800">Klik untuk upload foto</span>
              <span class="text-xs text-gray-500 mt-1">JPG, PNG, atau WEBP · maks. 8 MB</span>
              <input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp" class="hidden">
            </label>
            <p id="file-name" class="text-xs text-gray-500 mt-2 hidden"></p>
          </div>

          <div id="editor-section" class="hidden">
            <label class="block text-sm font-semibold text-gray-700 mb-2">2. Sesuaikan posisi foto</label>
            <p class="text-xs text-gray-500 mb-3">Geser foto dengan drag/touch. Gunakan slider zoom agar wajah pas di area foto.</p>

            <div id="preview-wrap" class="relative bg-gray-200 rounded-xl overflow-hidden mx-auto max-w-md aspect-square">
              <canvas id="preview-canvas" width="480" height="480"></canvas>
            </div>

            <div class="mt-4 space-y-3 max-w-md mx-auto">
              <div>
                <label for="zoom-range" class="text-xs font-medium text-gray-600 flex justify-between">
                  <span>Zoom foto</span>
                  <span id="zoom-value">100%</span>
                </label>
                <input type="range" id="zoom-range" min="80" max="220" value="100" class="w-full accent-green-700 mt-1">
              </div>
              <button type="button" id="reset-btn" class="text-xs text-green-700 font-semibold hover:underline">
                Reset posisi & zoom
              </button>
            </div>
          </div>

          <div id="download-section" class="hidden pt-2 border-t">
            <p class="text-sm font-semibold text-gray-700 mb-3">3. Unduh twibbon</p>
            <div class="flex flex-col sm:flex-row gap-3">
              <button type="button" id="download-btn"
                      class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold py-3 px-6 rounded-xl shadow transition">
                Unduh Twibbon (PNG)
              </button>
              <button type="button" id="change-photo-btn"
                      class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-3 px-6 rounded-xl transition">
                Ganti Foto
              </button>
            </div>
            <p class="text-xs text-gray-500 mt-3 text-center">
              Resolusi unduhan <?= (int) $selected['size'] ?>×<?= (int) $selected['size'] ?> px — cocok untuk Instagram, Facebook, dan WhatsApp Status.
            </p>
          </div>
        </div>
      </div>

      <div class="mt-6 bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-900">
        <p class="font-semibold mb-1">Tips</p>
        <ul class="list-disc list-inside space-y-1 text-green-800 text-xs sm:text-sm">
          <?php foreach ($selected['tips'] as $tip): ?>
            <li><?= sanitize($tip) ?></li>
          <?php endforeach; ?>
          <li>Bagikan dengan caption: <em><?= sanitize($selected['hashtags']) ?></em></li>
        </ul>
      </div>
    <?php endif; ?>
  </main>

  <footer class="bg-green-900 text-green-100 py-5 mt-8">
    <div class="max-w-4xl mx-auto px-6 text-center text-sm">
      © 2026 LP Ma'arif NU Kabupaten Magelang
    </div>
  </footer>

  <?php if ($templateExists): ?>
  <script>
    window.TWIBBON_CONFIG = {
      templateUrl: <?= json_encode($templateUrl, JSON_UNESCAPED_SLASHES) ?>,
      size: <?= (int) $selected['size'] ?>,
      hole: <?= json_encode($selected['hole'], JSON_UNESCAPED_UNICODE) ?>,
      downloadName: <?= json_encode($selected['downloadName'], JSON_UNESCAPED_UNICODE) ?>,
    };
  </script>
  <script src="<?= url('twibbon/twibbon.js?v=4') ?>"></script>
  <?php endif; ?>

  <?= siteThemeFooterScript() ?>
</body>
</html>
