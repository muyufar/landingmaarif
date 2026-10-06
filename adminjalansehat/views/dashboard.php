<?php

declare(strict_types=1);

/** @var array $rows @var array $ringkasan @var array $settings @var int $paketAktifCount */
$adaHarga = jalanSehatAdaHarga($settings);
?>
<?php if ($paketAktifCount === 0): ?>
  <div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-amber-900 text-sm">
    Belum ada paket aktif. Form publik saat ini hanya menampilkan isian jumlah bebas.
    <a href="<?= url('adminjalansehat/?page=pengaturan') ?>" class="font-semibold underline">Atur paket sesuai surat edaran →</a>
  </div>
<?php endif; ?>
<?php if ($settings['pendaftaran_dibuka'] !== '1'): ?>
  <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
    Pendaftaran sedang <strong>DITUTUP</strong>. Form publik tidak menerima pendaftaran baru.
  </div>
<?php endif; ?>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  <div class="bg-white rounded-2xl shadow-lg border border-green-100 p-6">
    <p class="text-sm text-gray-500">Madrasah Terdaftar</p>
    <p class="text-3xl font-bold text-green-800 mt-2"><?= (int) $ringkasan['total_madrasah'] ?></p>
  </div>
  <div class="bg-white rounded-2xl shadow-lg border border-green-100 p-6">
    <p class="text-sm text-gray-500">Total Kupon Door Prize</p>
    <p class="text-3xl font-bold text-green-800 mt-2"><?= number_format((int) $ringkasan['total_kupon'], 0, ',', '.') ?></p>
  </div>
  <div class="bg-white rounded-2xl shadow-lg border border-green-100 p-6">
    <p class="text-sm text-gray-500">Total Kaos</p>
    <p class="text-3xl font-bold text-green-800 mt-2"><?= number_format((int) $ringkasan['total_kaos'], 0, ',', '.') ?> <span class="text-base font-medium text-gray-500">pcs</span></p>
  </div>
  <div class="bg-white rounded-2xl shadow-lg border border-green-100 p-6">
    <?php if ($adaHarga): ?>
      <p class="text-sm text-gray-500">Total Estimasi Biaya</p>
      <p class="text-2xl font-bold text-green-800 mt-2"><?= sanitize(formatRupiahJalanSehat((int) $ringkasan['total_biaya'])) ?></p>
    <?php else: ?>
      <p class="text-sm text-gray-500 mb-3">Aksi Cepat</p>
      <div class="flex flex-wrap gap-2">
        <a href="<?= url('adminjalansehat/?page=list') ?>"
           class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Lihat Data</a>
        <a href="<?= url('adminjalansehat/?export=xls') ?>"
           class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Export XLS</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden mb-8">
  <div class="px-6 py-5 border-b border-gray-100">
    <h2 class="text-lg font-bold text-green-800">Rekap Ukuran Kaos</h2>
    <p class="text-xs text-gray-500 mt-1">Total kebutuhan per ukuran untuk diteruskan ke konveksi.</p>
  </div>
  <div class="px-6 py-4 grid grid-cols-3 sm:grid-cols-6 gap-3">
    <?php foreach ($ringkasan['per_ukuran'] as $label => $qty): ?>
      <div class="bg-green-50 rounded-lg px-4 py-3 text-center">
        <p class="text-sm font-bold text-green-900"><?= sanitize((string) $label) ?></p>
        <p class="text-2xl font-bold text-green-800"><?= (int) $qty ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if (!empty($ringkasan['per_kecamatan'])): ?>
<div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden mb-8">
  <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center gap-4">
    <div>
      <h2 class="text-lg font-bold text-green-800">Rekap per Kecamatan</h2>
      <p class="text-xs text-gray-500 mt-1">Klik nama kecamatan untuk melihat daftar madrasahnya.</p>
    </div>
    <a href="<?= url('adminjalansehat/?page=list&urut=wilayah') ?>" class="text-sm text-green-700 hover:underline whitespace-nowrap">Urutkan per wilayah →</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-green-50 text-green-900 text-xs uppercase">
        <tr>
          <th class="px-4 py-3 text-left">Kecamatan</th>
          <th class="px-4 py-3 text-right">Madrasah</th>
          <th class="px-4 py-3 text-right">Kupon</th>
          <th class="px-4 py-3 text-right">Kaos</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <?php foreach ($ringkasan['per_kecamatan'] as $kec => $item): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-2.5">
              <?php if ($kec !== 'Belum ada kecamatan'): ?>
                <a href="<?= url('adminjalansehat/?' . http_build_query(['page' => 'list', 'kecamatan' => $kec, 'urut' => 'wilayah'])) ?>"
                   class="font-medium text-green-800 hover:underline"><?= sanitize((string) $kec) ?></a>
              <?php else: ?>
                <span class="text-gray-400 italic"><?= sanitize((string) $kec) ?></span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-2.5 text-right font-semibold"><?= (int) $item['madrasah'] ?></td>
            <td class="px-4 py-2.5 text-right"><?= number_format((int) $item['kupon'], 0, ',', '.') ?></td>
            <td class="px-4 py-2.5 text-right"><?= number_format((int) $item['kaos'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($ringkasan['per_paket'])): ?>
<div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden mb-8">
  <div class="px-6 py-5 border-b border-gray-100">
    <h2 class="text-lg font-bold text-green-800">Pendaftar per Pilihan Paket</h2>
  </div>
  <div class="px-6 py-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
    <?php foreach ($ringkasan['per_paket'] as $label => $count): ?>
      <div class="bg-green-50 rounded-lg px-4 py-3 flex justify-between items-center">
        <span class="text-sm text-gray-700"><?= sanitize((string) $label) ?></span>
        <span class="font-bold text-green-800"><?= (int) $count ?> madrasah</span>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
  <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center gap-4">
    <h2 class="text-lg font-bold text-green-800">Pendaftar Terbaru</h2>
    <a href="<?= url('adminjalansehat/?page=list') ?>" class="text-sm text-green-700 hover:underline">Lihat semua →</a>
  </div>
  <?php if (empty($rows)): ?>
    <div class="px-6 py-12 text-center text-gray-500">Belum ada pendaftar.</div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-green-50 text-green-900 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">Madrasah</th>
            <th class="px-4 py-3 text-left">Kepala</th>
            <th class="px-4 py-3 text-left">Paket</th>
            <th class="px-4 py-3 text-right">Kupon</th>
            <th class="px-4 py-3 text-right">Kaos</th>
            <th class="px-4 py-3 text-left">Tanggal</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach (array_slice($rows, 0, 10) as $row): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <a href="<?= url('adminjalansehat/?page=detail&id=' . (int) $row['id']) ?>" class="font-semibold text-green-800 hover:underline">
                  <?= sanitize((string) $row['nama_madrasah']) ?>
                </a>
              </td>
              <td class="px-4 py-3"><?= sanitize((string) $row['nama_kepala']) ?></td>
              <td class="px-4 py-3"><?= sanitize(!empty($row['paket_nama']) ? (string) $row['paket_nama'] : 'Jumlah sendiri') ?></td>
              <td class="px-4 py-3 text-right font-semibold"><?= (int) $row['jumlah_kupon'] ?></td>
              <td class="px-4 py-3 text-right font-semibold"><?= (int) $row['jumlah_kaos'] ?></td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-500"><?= sanitize((string) $row['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
