<?php

declare(strict_types=1);

/** @var array $stats @var array $modules @var array $latestBerita */
?>
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
  <div class="bg-white rounded-2xl shadow border border-green-100 p-5">
    <p class="text-xs text-gray-500">Berita Terbit</p>
    <p class="text-3xl font-bold text-green-800 mt-1"><?= (int) ($stats['berita']['published'] ?? 0) ?></p>
    <p class="text-xs text-gray-400 mt-1"><?= (int) ($stats['berita']['draft'] ?? 0) ?> draft</p>
  </div>
  <div class="bg-white rounded-2xl shadow border border-green-100 p-5">
    <p class="text-xs text-gray-500">Peserta RAKERDINMA</p>
    <p class="text-3xl font-bold text-green-800 mt-1"><?= (int) ($stats['peserta'] ?? 0) ?></p>
  </div>
  <div class="bg-white rounded-2xl shadow border border-green-100 p-5">
    <p class="text-xs text-gray-500">Pemesanan</p>
    <p class="text-3xl font-bold text-green-800 mt-1"><?= (int) ($stats['pemesanan'] ?? 0) ?></p>
  </div>
  <div class="bg-white rounded-2xl shadow border border-green-100 p-5">
    <p class="text-xs text-gray-500">Pengkinian Data</p>
    <p class="text-3xl font-bold text-green-800 mt-1"><?= (int) ($stats['pengkinian'] ?? 0) ?></p>
  </div>
  <div class="bg-white rounded-2xl shadow border border-green-100 p-5">
    <p class="text-xs text-gray-500">Satuan Distribusi</p>
    <p class="text-3xl font-bold text-green-800 mt-1"><?= (int) ($stats['distribusi'] ?? 0) ?></p>
  </div>
</div>

<div class="mb-8">
  <h2 class="text-xl font-bold text-green-800 mb-4">Semua Modul Admin</h2>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($modules as $mod): ?>
      <?php if (($mod['key'] ?? '') === 'hub') continue; ?>
      <a href="<?= sanitize($mod['url']) ?>"
         class="bg-white rounded-2xl shadow border border-green-100 p-6 hover:shadow-lg hover:border-green-300 transition">
        <div class="text-3xl mb-3"><?= $mod['icon'] ?></div>
        <h3 class="font-bold text-green-800 text-lg"><?= sanitize($mod['label']) ?></h3>
        <p class="text-sm text-gray-600 mt-1"><?= sanitize($mod['desc']) ?></p>
        <span class="inline-block mt-4 text-sm font-semibold text-green-700">Buka →</span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="bg-white rounded-2xl shadow border border-green-100 overflow-hidden">
  <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
    <h2 class="text-lg font-bold text-green-800">Berita Terbaru</h2>
    <a href="<?= url('admin/?page=berita-form') ?>" class="text-sm bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg">+ Tulis Berita</a>
  </div>
  <?php if (empty($latestBerita)): ?>
    <div class="px-6 py-10 text-center text-gray-500 text-sm">Belum ada berita. Mulai dengan menulis berita baru.</div>
  <?php else: ?>
    <div class="divide-y divide-gray-100">
      <?php foreach ($latestBerita as $row): ?>
        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div class="min-w-0">
            <p class="font-semibold text-gray-800 truncate"><?= sanitize($row['judul'] ?? '') ?></p>
            <p class="text-xs text-gray-500 mt-0.5">
              <?= sanitize(formatTanggalBerita($row['published_at'] ?? $row['created_at'] ?? null)) ?>
              ·
              <span class="<?= ($row['status'] ?? '') === 'published' ? 'text-green-700' : 'text-amber-600' ?>">
                <?= ($row['status'] ?? '') === 'published' ? 'Terbit' : 'Draft' ?>
              </span>
            </p>
          </div>
          <a href="<?= url('admin/?page=berita-form&id=' . (int) ($row['id'] ?? 0)) ?>"
             class="text-sm text-green-700 hover:underline shrink-0">Edit</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
