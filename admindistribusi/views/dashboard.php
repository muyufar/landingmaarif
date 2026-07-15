<?php declare(strict_types=1); /** @var array $stats */ ?>
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
  <div class="bg-white rounded-xl p-5 border shadow-sm col-span-2 lg:col-span-1">
    <p class="text-xs text-gray-500">Total Satuan</p>
    <p class="text-3xl font-bold text-green-800"><?= (int) ($stats['total'] ?? 0) ?></p>
  </div>
  <?php foreach (['packing', 'delivery', 'receive', 'done'] as $st): ?>
    <div class="bg-white rounded-xl p-5 border shadow-sm">
      <p class="text-xs text-gray-500"><?= sanitize(distribusiStatusLabel($st)) ?></p>
      <p class="text-2xl font-bold"><?= (int) ($stats[$st] ?? 0) ?></p>
    </div>
  <?php endforeach; ?>
</div>
<div class="grid md:grid-cols-3 gap-4">
  <a href="<?= url('admindistribusi/?page=import') ?>" class="bg-white border rounded-xl p-5 hover:shadow-md">📥 Import Data Excel</a>
  <a href="<?= url('admindistribusi/?page=petugas') ?>" class="bg-white border rounded-xl p-5 hover:shadow-md">👤 Kelola Akun Petugas</a>
  <a href="<?= url('admindistribusi/?page=list') ?>" class="bg-white border rounded-xl p-5 hover:shadow-md">📊 Monitoring Lengkap</a>
</div>
