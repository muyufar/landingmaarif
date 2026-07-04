<?php

declare(strict_types=1);

/** @var array $rows @var array $filters @var string $search */
$catalog = pemesananLayananCatalog();

$exportQuery = http_build_query(array_filter([
    'export' => 'xls',
    'q' => $search,
    'jenis_layanan' => $filters['jenis_layanan'] ?? '',
    'jenjang' => $filters['jenjang'] ?? '',
], static fn (string $value): bool => $value !== ''));
?>
<div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
  <div class="px-6 py-5 border-b border-gray-100">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
      <div>
        <h2 class="text-xl font-bold text-green-800">List Pemesanan</h2>
        <p class="text-sm text-gray-500 mt-1">Total: <strong><?= count($rows) ?></strong> pemesanan</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="<?= url('adminpemesananbuku/?' . $exportQuery) ?>"
           class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Export XLS</a>
      </div>
    </div>

    <form method="get" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <input type="hidden" name="page" value="list">
      <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Cari madrasah, kepala, WA..."
             class="rounded-lg border border-gray-300 px-3 py-2 text-sm lg:col-span-2 focus:ring-2 focus:ring-green-600">
      <select name="jenis_layanan" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">Semua Jenis</option>
        <?php foreach ($catalog as $key => $item): ?>
          <option value="<?= sanitize($key) ?>" <?= ($filters['jenis_layanan'] ?? '') === $key ? 'selected' : '' ?>><?= sanitize($item['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="jenjang" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">Semua Jenjang</option>
        <?php
        $allJenjang = [];
        foreach (jenjangPemesananOptions() as $j) {
            $allJenjang[$j] = true;
        }
        foreach ($catalog as $item) {
            foreach ($item['jenjang'] ?? [] as $j) {
                $allJenjang[$j] = true;
            }
        }
        foreach (array_keys($allJenjang) as $opt):
        ?>
          <option value="<?= sanitize($opt) ?>" <?= ($filters['jenjang'] ?? '') === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="flex gap-2 sm:col-span-2 lg:col-span-4">
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
        <a href="<?= url('adminpemesananbuku/?page=list') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium">Reset</a>
      </div>
    </form>
  </div>

  <?php if (empty($rows)): ?>
    <div class="px-6 py-16 text-center text-gray-500">Belum ada data pemesanan.</div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-green-50 text-green-900 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">No</th>
            <th class="px-4 py-3 text-left">Tanggal</th>
            <th class="px-4 py-3 text-left">Jenis</th>
            <th class="px-4 py-3 text-left">Madrasah / Kepala</th>
            <th class="px-4 py-3 text-left">WA</th>
            <th class="px-4 py-3 text-left">Jenjang</th>
            <th class="px-4 py-3 text-left">Ringkasan</th>
            <th class="px-4 py-3 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($rows as $index => $row): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-500"><?= $index + 1 ?></td>
              <td class="px-4 py-3 whitespace-nowrap"><?= sanitize($row['created_at'] ?? '-') ?></td>
              <td class="px-4 py-3 text-xs font-medium text-green-800 max-w-[8rem]"><?= sanitize(labelJenisLayanan($row['jenis_layanan'] ?? 'mopdik')) ?></td>
              <td class="px-4 py-3">
                <p class="font-semibold"><?= sanitize($row['nama_madrasah'] ?? '') ?></p>
                <p class="text-xs text-gray-500"><?= sanitize($row['nama_kepala'] ?? '') ?></p>
              </td>
              <td class="px-4 py-3 text-green-700"><?= sanitize($row['nomor_wa'] ?? '') ?></td>
              <td class="px-4 py-3"><?= sanitize(normalizeJenjangPemesanan($row['jenjang'] ?? '')) ?></td>
              <td class="px-4 py-3 text-xs"><?= sanitize(formatRingkasanPemesanan($row)) ?></td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-2">
                  <a href="<?= url('adminpemesananbuku/?page=detail&id=' . (int) ($row['id'] ?? 0)) ?>"
                     class="text-green-700 hover:underline text-xs font-medium">Detail</a>
                  <form method="post" class="inline" onsubmit="return confirm('Hapus pemesanan ini?');">
                    <input type="hidden" name="delete_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                    <button type="submit" class="text-red-600 hover:underline text-xs font-medium">Hapus</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
