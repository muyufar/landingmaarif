<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/berita_functions.php';

$slug = trim($_GET['slug'] ?? '');
$kode = trim($_GET['kode'] ?? '');
$row = null;
$list = [];
$pageMode = 'list';

try {
    if ($kode !== '') {
        $row = getBeritaByKode($kode);
        if ($row && ($row['status'] ?? '') === 'published') {
            $pageMode = 'detail';
        } else {
            $row = null;
            $pageMode = 'notfound';
        }
    } elseif ($slug !== '') {
        $row = getBeritaBySlug($slug);
        if ($row && ($row['status'] ?? '') === 'published') {
            $pageMode = 'detail';
        } else {
            $row = null;
            $pageMode = 'notfound';
        }
    } else {
        $list = loadBeritaPublished(50);
    }
} catch (PDOException) {
    $list = [];
    $pageMode = 'error';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>
    <?php if ($pageMode === 'detail'): ?>
      <?= sanitize($row['judul'] ?? 'Berita') ?> |
    <?php endif; ?>
    Berita | LP Ma'arif NU Magelang
  </title>
  <?php if ($pageMode === 'detail' && $row): ?>
    <?php
      $share = beritaShareLinks($row);
      $coverMeta = getBeritaCoverPath($row);
    ?>
    <meta name="description" content="<?= sanitize(ringkasanBerita($row, 150)) ?>">
    <meta property="og:title" content="<?= sanitize($row['judul'] ?? '') ?>">
    <meta property="og:description" content="<?= sanitize(ringkasanBerita($row, 150)) ?>">
    <meta property="og:url" content="<?= sanitize($share['url']) ?>">
    <meta property="og:type" content="article">
    <?php if ($coverMeta !== ''): ?>
      <meta property="og:image" content="<?= sanitize(absoluteUrl($coverMeta)) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
  <?php endif; ?>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-4 min-w-0">
        <img src="<?= url('image/logo.png') ?>" alt="Logo" class="w-12 h-12 rounded-full bg-white p-1 shrink-0">
        <div class="min-w-0">
          <h1 class="text-lg md:text-xl font-bold truncate">LP Ma'arif NU Kabupaten Magelang</h1>
          <p class="text-sm text-green-100">Berita & Informasi</p>
        </div>
      </div>
      <a href="<?= url() ?>" class="text-sm bg-green-900 hover:bg-green-950 px-3 py-2 rounded-lg shrink-0">← Beranda</a>
    </div>
  </header>

  <main class="max-w-5xl mx-auto px-6 py-10">
    <?php if ($pageMode === 'detail' && $row): ?>
      <?php
        $galeri = $row['galeri'] ?? [];
        $cover = getBeritaCoverPath($row);
        $share = beritaShareLinks($row);
      ?>
      <article class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
        <?php if ($cover !== ''): ?>
          <img src="<?= url($cover) ?>" alt="<?= sanitize($row['judul'] ?? '') ?>" class="w-full max-h-[420px] object-cover">
        <?php endif; ?>
        <div class="px-6 sm:px-10 py-8">
          <p class="text-sm text-green-700 mb-2"><?= sanitize(formatTanggalBerita($row['published_at'] ?? $row['created_at'] ?? null)) ?></p>
          <h2 class="text-2xl md:text-3xl font-bold text-green-900 leading-snug mb-4"><?= sanitize($row['judul'] ?? '') ?></h2>
          <?php if (!empty($row['ringkasan'])): ?>
            <p class="text-lg text-gray-600 mb-6"><?= sanitize($row['ringkasan']) ?></p>
          <?php endif; ?>
          <div class="prose max-w-none text-gray-800 leading-relaxed whitespace-pre-wrap"><?= sanitize($row['konten'] ?? '') ?></div>

          <?php if (count($galeri) > 1): ?>
            <div class="mt-8">
              <h3 class="text-sm font-semibold text-green-800 mb-3">Galeri Foto</h3>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <?php foreach ($galeri as $img): ?>
                  <a href="<?= url($img['path'] ?? '') ?>" target="_blank" rel="noopener"
                     class="block rounded-xl overflow-hidden border border-green-100 hover:shadow-md transition">
                    <img src="<?= url($img['path'] ?? '') ?>" alt="<?= sanitize($row['judul'] ?? '') ?>" class="w-full h-36 object-cover">
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="mt-10 pt-6 border-t border-gray-100 space-y-4">
            <div>
              <p class="text-sm font-semibold text-green-800 mb-2">Bagikan Berita</p>
              <div class="flex flex-wrap gap-2">
                <a href="<?= sanitize($share['whatsapp']) ?>" target="_blank" rel="noopener"
                   class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg">WhatsApp</a>
                <a href="<?= sanitize($share['facebook']) ?>" target="_blank" rel="noopener"
                   class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Facebook</a>
                <a href="<?= sanitize($share['telegram']) ?>" target="_blank" rel="noopener"
                   class="bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium px-4 py-2 rounded-lg">Telegram</a>
                <a href="<?= sanitize($share['twitter']) ?>" target="_blank" rel="noopener"
                   class="bg-gray-800 hover:bg-black text-white text-sm font-medium px-4 py-2 rounded-lg">X / Twitter</a>
                <button type="button" id="btn-copy-link"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium px-4 py-2 rounded-lg"
                        data-url="<?= sanitize($share['url']) ?>">
                  Salin Link
                </button>
              </div>
            </div>
            <div class="rounded-xl bg-green-50 border border-green-100 px-4 py-3">
              <p class="text-xs text-gray-500 mb-1">Link singkat</p>
              <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <code id="short-link" class="text-sm text-green-900 break-all font-medium"><?= sanitize($share['url']) ?></code>
                <button type="button" id="btn-copy-short"
                        class="shrink-0 text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg"
                        data-url="<?= sanitize($share['url']) ?>">
                  Salin
                </button>
              </div>
            </div>
            <a href="<?= url('berita') ?>" class="inline-block text-green-700 hover:underline text-sm font-medium">← Semua Berita</a>
          </div>
        </div>
      </article>
      <script>
        function copyShareLink(btn) {
          const url = btn.getAttribute('data-url') || '';
          const done = () => {
            const old = btn.textContent;
            btn.textContent = 'Tersalin!';
            setTimeout(() => { btn.textContent = old; }, 1500);
          };
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done).catch(() => {
              window.prompt('Salin link ini:', url);
            });
          } else {
            window.prompt('Salin link ini:', url);
          }
        }
        document.getElementById('btn-copy-link')?.addEventListener('click', function () { copyShareLink(this); });
        document.getElementById('btn-copy-short')?.addEventListener('click', function () { copyShareLink(this); });
      </script>

    <?php elseif ($pageMode === 'notfound'): ?>
      <div class="bg-white rounded-2xl shadow border border-red-100 p-10 text-center">
        <h2 class="text-xl font-bold text-red-700 mb-2">Berita tidak ditemukan</h2>
        <a href="<?= url('berita') ?>" class="text-green-700 hover:underline text-sm">Kembali ke daftar berita</a>
      </div>

    <?php elseif ($pageMode === 'error'): ?>
      <div class="bg-white rounded-2xl shadow border border-amber-100 p-10 text-center text-amber-800">
        Belum bisa menampilkan berita. Pastikan tabel database sudah diimport.
      </div>

    <?php else: ?>
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-green-800">Berita Terbaru</h2>
        <p class="text-gray-600 mt-2">Informasi kegiatan dan pengumuman LP Ma'arif NU Kabupaten Magelang.</p>
      </div>

      <?php if (empty($list)): ?>
        <div class="bg-white rounded-2xl shadow border border-green-100 p-10 text-center text-gray-500">
          Belum ada berita yang dipublikasikan.
        </div>
      <?php else: ?>
        <div class="grid md:grid-cols-2 gap-6">
          <?php foreach ($list as $item): ?>
            <?php $cover = getBeritaCoverPath($item); ?>
            <a href="<?= url(beritaShortPath($item)) ?>"
               class="bg-white rounded-2xl shadow border border-green-100 overflow-hidden hover:shadow-lg hover:border-green-300 transition block">
              <?php if ($cover !== ''): ?>
                <img src="<?= url($cover) ?>" alt="<?= sanitize($item['judul'] ?? '') ?>" class="w-full h-48 object-cover">
              <?php else: ?>
                <div class="w-full h-48 bg-green-50 flex items-center justify-center text-4xl">📰</div>
              <?php endif; ?>
              <div class="p-5">
                <p class="text-xs text-green-700 mb-1"><?= sanitize(formatTanggalBerita($item['published_at'] ?? $item['created_at'] ?? null)) ?></p>
                <h3 class="font-bold text-green-900 text-lg leading-snug mb-2"><?= sanitize($item['judul'] ?? '') ?></h3>
                <p class="text-sm text-gray-600 line-clamp-3"><?= sanitize(ringkasanBerita($item)) ?></p>
                <?php if (count($item['galeri'] ?? []) > 1): ?>
                  <p class="text-xs text-gray-400 mt-2"><?= count($item['galeri']) ?> foto</p>
                <?php endif; ?>
                <span class="inline-block mt-4 text-sm font-semibold text-green-700">Baca selengkapnya →</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </main>

  <footer class="bg-green-900 text-green-100 py-6 mt-10">
    <div class="max-w-5xl mx-auto px-6 text-center text-sm">© 2026 LP Ma'arif NU Kabupaten Magelang</div>
  </footer>

</body>
</html>
