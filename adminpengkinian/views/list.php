<?php

declare(strict_types=1);

/** @var array $rows @var array $filters @var string $search @var array $kecamatanOptions */
?>
<div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
  <div class="px-6 py-5 border-b border-gray-100">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
      <div>
        <h2 class="text-xl font-bold text-green-800">List Data Satuan Pendidikan</h2>
        <p class="text-sm text-gray-500 mt-1">Total: <strong><?= count($rows) ?></strong> data</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="<?= url('adminpengkinian/?export=csv') ?>"
           class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Export CSV</a>
      </div>
    </div>

    <form method="get" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <input type="hidden" name="page" value="list">
      <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Cari NPSN, satuan, kepsek, operator, HP..."
             class="rounded-lg border border-gray-300 px-3 py-2 text-sm lg:col-span-2 focus:ring-2 focus:ring-green-600">
      <select name="kecamatan" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">Semua Kecamatan</option>
        <?php foreach ($kecamatanOptions as $opt): ?>
          <option value="<?= sanitize($opt) ?>" <?= ($filters['kecamatan'] ?? '') === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
        <a href="<?= url('adminpengkinian/?page=list') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium">Reset</a>
      </div>
    </form>
  </div>

  <?php if (empty($rows)): ?>
    <div class="px-6 py-16 text-center text-gray-500">Belum ada data satuan pendidikan.</div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-green-50 text-green-900 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">No</th>
            <th class="px-4 py-3 text-left">NPSN</th>
            <th class="px-4 py-3 text-left">Satuan Pendidikan</th>
            <th class="px-4 py-3 text-left">Kepala Sekolah</th>
            <th class="px-4 py-3 text-left">Operator</th>
            <th class="px-4 py-3 text-left">HP Kepsek</th>
            <th class="px-4 py-3 text-left">HP Operator</th>
            <th class="px-4 py-3 text-left">Jenjang</th>
            <th class="px-4 py-3 text-left">Status SK</th>
            <th class="px-4 py-3 text-left">Diperbarui</th>
            <th class="px-4 py-3 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($rows as $index => $row): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-500"><?= $index + 1 ?></td>
              <td class="px-4 py-3 font-mono text-xs whitespace-nowrap"><?= sanitize($row['npsn'] ?? '') ?></td>
              <td class="px-4 py-3 font-semibold max-w-[12rem]"><?= sanitize($row['nama_satuan_pendidikan'] ?? '') ?></td>
              <td class="px-4 py-3"><?= sanitize($row['nama_kepala_sekolah'] ?? '') ?></td>
              <td class="px-4 py-3"><?= sanitize($row['nama_operator'] ?? '') ?></td>
              <td class="px-4 py-3 text-green-700 whitespace-nowrap"><?= sanitize($row['nomor_hp_kepsek'] ?? '') ?></td>
              <td class="px-4 py-3 text-green-700 whitespace-nowrap"><?= sanitize($row['nomor_hp_operator'] ?? '') ?></td>
              <td class="px-4 py-3"><?= sanitize($row['jenjang'] ?? '') ?></td>
              <td class="px-4 py-3">
                <?php $status = strtoupper((string) ($row['status_sk_kepala'] ?? '')); ?>
                <span class="text-xs font-bold <?= $status === 'AKTIF' ? 'text-green-700' : 'text-red-700' ?>">
                  <?= sanitize($status !== '' ? $status : '-') ?>
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-500"><?= sanitize($row['updated_at'] ?? '') ?></td>
              <td class="px-4 py-3 text-center">
                <a href="<?= url('adminpengkinian/?page=detail&id=' . (int) $row['id']) ?>"
                   class="text-green-700 hover:underline font-medium">Detail</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
