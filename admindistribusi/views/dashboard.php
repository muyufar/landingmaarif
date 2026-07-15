<?php declare(strict_types=1); /** @var array $stats */ ?>
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
  <div class="bg-white rounded-xl p-5 border shadow-sm col-span-2 lg:col-span-1">
    <p class="text-xs text-gray-500">Total Satuan</p>
    <p class="text-3xl font-bold text-green-800"><?= (int) ($stats['total'] ?? 0) ?></p>
  </div>
  <?php foreach (['packing', 'delivery', 'receive', 'done'] as $st): ?>
    <div class="bg-white rounded-xl p-5 border shadow-sm">
      <p class="text-xs text-gray-500"><?= sanitize(distribusiStatusLabel($st)) ?></p>
      <p class="text-2xl font-bold"><?= (int) ($stats[$st] ?? 0) ?></p>
      <p class="text-xs text-gray-400 mt-1"><?= number_format((int) ($stats['buku'][$st] ?? 0), 0, ',', '.') ?> buku</p>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid grid-cols-2 lg:grid-cols-7 gap-4 mb-8">
  <div class="bg-white rounded-xl p-5 border shadow-sm col-span-2 lg:col-span-1 border-green-200 bg-green-50">
    <p class="text-xs text-gray-600">Total Buku LKS</p>
    <p class="text-3xl font-bold text-green-800"><?= number_format((int) ($stats['total_buku'] ?? 0), 0, ',', '.') ?></p>
    <p class="text-xs text-gray-500 mt-1">Kebutuhan seluruh satuan</p>
  </div>
  <?php for ($i = 1; $i <= 6; $i++): ?>
    <div class="bg-white rounded-xl p-5 border shadow-sm">
      <p class="text-xs text-gray-500">Kelas <?= $i ?></p>
      <p class="text-xl font-bold text-green-800"><?= number_format((int) ($stats['kelas'][$i] ?? 0), 0, ',', '.') ?></p>
    </div>
  <?php endfor; ?>
</div>

<div class="grid md:grid-cols-3 gap-4">
  <a href="<?= url('admindistribusi/?page=import') ?>" class="bg-white border rounded-xl p-5 hover:shadow-md">📥 Import Data Excel</a>
  <a href="<?= url('admindistribusi/?page=petugas') ?>" class="bg-white border rounded-xl p-5 hover:shadow-md">👤 Kelola Akun Petugas</a>
  <a href="<?= url('admindistribusi/?page=list') ?>" class="bg-white border rounded-xl p-5 hover:shadow-md">📊 Monitoring Lengkap</a>
</div>
