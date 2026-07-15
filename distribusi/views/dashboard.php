<?php declare(strict_types=1); /** @var array $stats @var array $deliveryRows */ ?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
  <?php foreach (['packing' => 'Packing', 'delivery' => 'Delivery', 'receive' => 'Receive', 'done' => 'Done'] as $key => $label): ?>
    <div class="bg-white rounded-xl border border-green-100 p-5 shadow-sm">
      <p class="text-xs text-gray-500 uppercase"><?= sanitize($label) ?></p>
      <p class="text-3xl font-bold text-green-800 mt-1"><?= (int) ($stats[$key] ?? 0) ?></p>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid md:grid-cols-2 gap-4 mb-8">
  <a href="<?= url('distribusi/?page=kirim') ?>" class="bg-green-700 hover:bg-green-800 text-white rounded-xl p-6 shadow-lg">
    <h3 class="font-bold text-lg">Kirim Buku</h3>
    <p class="text-green-100 text-sm mt-1">Input NPSN → Packing ke Delivery + notifikasi WA</p>
  </a>
  <a href="<?= url('distribusi/?page=terima') ?>" class="bg-blue-700 hover:bg-blue-800 text-white rounded-xl p-6 shadow-lg">
    <h3 class="font-bold text-lg">Terima Buku</h3>
    <p class="text-blue-100 text-sm mt-1">Upload surat jalan + catat jumlah diterima</p>
  </a>
</div>

<div class="bg-white rounded-xl border border-green-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b"><h2 class="font-bold text-green-800">Sedang Delivery (<?= count($deliveryRows) ?>)</h2></div>
  <?php if (empty($deliveryRows)): ?>
    <p class="p-6 text-gray-500 text-sm">Tidak ada pengiriman aktif.</p>
  <?php else: ?>
    <div class="divide-y">
      <?php foreach ($deliveryRows as $row): ?>
        <?php $active = getActivePengiriman((int) $row['id']); ?>
        <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div>
            <p class="font-semibold"><?= sanitize($row['nama_lembaga'] ?? '') ?></p>
            <p class="text-xs text-gray-500">NPSN: <?= sanitize($row['npsn'] ?? '') ?></p>
          </div>
          <a href="<?= url('distribusi/?page=terima&npsn=' . urlencode($row['npsn'] ?? '')) ?>"
             class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg text-center">Proses Penerimaan</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
