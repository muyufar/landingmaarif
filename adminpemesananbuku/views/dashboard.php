<?php

declare(strict_types=1);

/** @var array $stats */
$catalog = pemesananLayananCatalog();
?>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
  <div class="bg-white rounded-2xl shadow border border-green-100 p-6">
    <p class="text-sm text-gray-500">Total Pemesanan</p>
    <p class="text-4xl font-bold text-green-800 mt-2"><?= (int) ($stats['total'] ?? 0) ?></p>
  </div>
  <div class="bg-white rounded-2xl shadow border border-green-100 p-6">
    <p class="text-sm text-gray-500">Total Item Buku/Paket</p>
    <p class="text-4xl font-bold text-green-800 mt-2"><?= (int) ($stats['total_jumlah'] ?? 0) ?></p>
  </div>
  <div class="bg-white rounded-2xl shadow border border-green-100 p-6">
    <p class="text-sm text-gray-500">Waktu Laporan</p>
    <p class="text-lg font-bold text-green-800 mt-2"><?= sanitize(date('d/m/Y H:i')) ?> WIB</p>
  </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-8">
  <div class="bg-white rounded-2xl shadow border border-green-100 p-6">
    <h3 class="font-bold text-green-800 mb-4">Pemesanan per Jenis Layanan</h3>
    <div class="space-y-3">
      <?php foreach ($catalog as $key => $item): ?>
        <?php $count = (int) ($stats['jenis'][$key] ?? 0); ?>
        <div>
          <div class="flex justify-between text-sm mb-1 gap-2">
            <span class="truncate"><?= sanitize($item['label']) ?></span>
            <span class="font-semibold shrink-0"><?= $count ?></span>
          </div>
          <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-green-600 rounded-full" style="width: <?= ($stats['total'] ?? 0) > 0 ? round($count / $stats['total'] * 100) : 0 ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow border border-green-100 p-6">
    <h3 class="font-bold text-green-800 mb-4">Pemesanan per Jenjang</h3>
    <?php if (empty($stats['jenjang'])): ?>
      <p class="text-gray-500 text-sm">Belum ada data.</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($stats['jenjang'] as $label => $count): ?>
          <div class="flex justify-between text-sm">
            <span><?= sanitize($label) ?></span>
            <span class="font-semibold"><?= (int) $count ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="bg-white rounded-2xl shadow border border-green-100 p-6">
  <h3 class="font-bold text-green-800 mb-4">Aksi Cepat</h3>
  <div class="flex flex-wrap gap-3">
    <a href="<?= url('adminpemesananbuku/?page=list') ?>"
       class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Lihat Semua Pemesanan</a>
    <a href="<?= url('adminpemesananbuku/?export=xls') ?>"
       class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Export XLS</a>
    <a href="<?= url('pemesanan') ?>"
       class="bg-white border border-green-700 text-green-800 hover:bg-green-50 px-4 py-2 rounded-lg text-sm font-medium">Buka Form Publik</a>
  </div>
</div>
