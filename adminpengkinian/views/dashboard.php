<?php

declare(strict_types=1);

/** @var array $stats @var array $rows */
?>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
  <div class="bg-white rounded-2xl shadow-lg border border-green-100 p-6">
    <p class="text-sm text-gray-500">Total Satuan Pendidikan</p>
    <p class="text-3xl font-bold text-green-800 mt-2"><?= (int) ($stats['total'] ?? 0) ?></p>
  </div>
  <div class="bg-white rounded-2xl shadow-lg border border-green-100 p-6">
    <p class="text-sm text-gray-500">Kecamatan Terisi</p>
    <p class="text-3xl font-bold text-green-800 mt-2"><?= count($stats['kecamatan'] ?? []) ?></p>
  </div>
  <div class="bg-white rounded-2xl shadow-lg border border-green-100 p-6 sm:col-span-2 lg:col-span-1">
    <p class="text-sm text-gray-500 mb-3">Aksi Cepat</p>
    <div class="flex flex-wrap gap-2">
      <a href="<?= url('adminpengkinian/?page=list') ?>"
         class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Lihat Semua Data</a>
      <a href="<?= url('pengkinian/') ?>"
         class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium">Form Publik</a>
    </div>
  </div>
</div>

<?php if (!empty($stats['kecamatan'])): ?>
<div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden mb-8">
  <div class="px-6 py-5 border-b border-gray-100">
    <h2 class="text-lg font-bold text-green-800">Data per Kecamatan</h2>
  </div>
  <div class="px-6 py-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
    <?php foreach ($stats['kecamatan'] as $kec => $count): ?>
      <div class="bg-green-50 rounded-lg px-4 py-3 flex justify-between items-center">
        <span class="text-sm text-gray-700"><?= sanitize($kec) ?></span>
        <span class="font-bold text-green-800"><?= (int) $count ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
  <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center gap-4">
    <h2 class="text-lg font-bold text-green-800">Data Terbaru</h2>
    <a href="<?= url('adminpengkinian/?page=list') ?>" class="text-sm text-green-700 hover:underline">Lihat semua →</a>
  </div>
  <?php if (empty($rows)): ?>
    <div class="px-6 py-12 text-center text-gray-500">Belum ada data.</div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-green-50 text-green-900 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">Satuan Pendidikan</th>
            <th class="px-4 py-3 text-left">Kepsek / Operator</th>
            <th class="px-4 py-3 text-left">HP</th>
            <th class="px-4 py-3 text-left">Diperbarui</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach (array_slice($rows, 0, 10) as $row): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <a href="<?= url('adminpengkinian/?page=detail&id=' . (int) $row['id']) ?>" class="font-semibold text-green-800 hover:underline">
                  <?= sanitize($row['nama_satuan_pendidikan'] ?? '') ?>
                </a>
                <p class="text-xs text-gray-500"><?= sanitize($row['nama_kecamatan'] ?? '') ?></p>
              </td>
              <td class="px-4 py-3">
                <p><?= sanitize($row['nama_kepala_sekolah'] ?? '') ?></p>
                <p class="text-xs text-gray-500"><?= sanitize($row['nama_operator'] ?? '') ?></p>
              </td>
              <td class="px-4 py-3 text-xs">
                <p>Kep: <?= sanitize($row['nomor_hp_kepsek'] ?? '') ?></p>
                <p>Op: <?= sanitize($row['nomor_hp_operator'] ?? '') ?></p>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-500"><?= sanitize($row['updated_at'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
