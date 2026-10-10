<?php

declare(strict_types=1);

/** @var array $rows @var array $ringkasan @var array $settings @var array $paketList @var string $search @var array $filters @var array $wilayahOptions @var string $exportQuery */
$adaHarga = jalanSehatAdaHarga($settings);
$paketFilter = $filters['paket'];
$desaOptions = $filters['kecamatan'] !== '' ? ($wilayahOptions[$filters['kecamatan']] ?? []) : [];
$selectClass = 'rounded-lg border border-gray-300 px-3 py-2 text-sm';
?>
<div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
  <div class="px-6 py-5 border-b border-gray-100">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
      <div>
        <h2 class="text-xl font-bold text-green-800">Data Pendaftar Jalan Sehat</h2>
        <p class="text-sm text-gray-500 mt-1">Total: <strong><?= count($rows) ?></strong> madrasah</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="<?= url('adminjalansehat/?' . $exportQuery) ?>"
           class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Export XLS</a>
      </div>
    </div>

    <form method="get" id="filter-form" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
      <input type="hidden" name="page" value="list">
      <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Cari madrasah, kepala, alamat, WA..."
             class="rounded-lg border border-gray-300 px-3 py-2 text-sm sm:col-span-2 focus:ring-2 focus:ring-green-600">
      <select name="kecamatan" class="<?= $selectClass ?>"
              onchange="this.form.elements.kelurahan.value=''; this.form.submit();">
        <option value="">Semua Kecamatan</option>
        <?php foreach (array_keys($wilayahOptions) as $kec): ?>
          <option value="<?= sanitize((string) $kec) ?>" <?= $filters['kecamatan'] === (string) $kec ? 'selected' : '' ?>><?= sanitize((string) $kec) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="kelurahan" class="<?= $selectClass ?> disabled:bg-gray-100" <?= $desaOptions === [] ? 'disabled' : '' ?>>
        <option value=""><?= $filters['kecamatan'] === '' ? 'Pilih kecamatan dulu' : 'Semua Desa/Kelurahan' ?></option>
        <?php foreach ($desaOptions as $desa): ?>
          <option value="<?= sanitize($desa) ?>" <?= $filters['kelurahan'] === $desa ? 'selected' : '' ?>><?= sanitize($desa) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="urut" class="<?= $selectClass ?>">
        <?php foreach (jalanSehatUrutanOptions() as $key => $label): ?>
          <option value="<?= sanitize($key) ?>" <?= $filters['urut'] === $key ? 'selected' : '' ?>>Urut: <?= sanitize($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="paket" class="<?= $selectClass ?>">
        <option value="">Semua Paket</option>
        <?php foreach ($paketList as $paket): ?>
          <option value="<?= (int) $paket['id'] ?>" <?= $paketFilter === (string) $paket['id'] ? 'selected' : '' ?>>
            <?= sanitize((string) $paket['nama']) ?><?= (int) $paket['aktif'] === 1 ? '' : ' (nonaktif)' ?>
          </option>
        <?php endforeach; ?>
        <option value="custom" <?= $paketFilter === 'custom' ? 'selected' : '' ?>>Jumlah sendiri</option>
      </select>
      <div class="flex gap-2">
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
        <a href="<?= url('adminjalansehat/?page=list') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium">Reset</a>
      </div>
    </form>
  </div>

  <div class="px-6 py-4 bg-green-50/60 border-b border-gray-100 grid grid-cols-2 <?= $adaHarga ? 'lg:grid-cols-4' : 'lg:grid-cols-3' ?> gap-3 text-sm">
    <div><span class="text-gray-500">Madrasah:</span> <strong class="text-green-800"><?= (int) $ringkasan['total_madrasah'] ?></strong></div>
    <div><span class="text-gray-500">Total kupon door prize:</span> <strong class="text-green-800"><?= number_format((int) $ringkasan['total_kupon'], 0, ',', '.') ?></strong></div>
    <div><span class="text-gray-500">Total kaos:</span> <strong class="text-green-800"><?= number_format((int) $ringkasan['total_kaos'], 0, ',', '.') ?> pcs</strong></div>
    <?php if ($adaHarga): ?>
      <div><span class="text-gray-500">Estimasi biaya:</span> <strong class="text-green-800"><?= sanitize(formatRupiahJalanSehat((int) $ringkasan['total_biaya'])) ?></strong></div>
    <?php endif; ?>
    <div class="col-span-full flex flex-wrap items-center gap-2">
      <span class="text-gray-500">Rekap ukuran kaos:</span>
      <?php foreach ($ringkasan['per_ukuran'] as $label => $qty): ?>
        <span class="rounded-md bg-white border border-green-200 px-2 py-0.5 text-xs"><strong><?= sanitize((string) $label) ?></strong>: <?= (int) $qty ?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (empty($rows)): ?>
    <div class="px-6 py-16 text-center text-gray-500">Belum ada data pendaftar.</div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-green-50 text-green-900 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">No</th>
            <th class="px-4 py-3 text-left">Madrasah</th>
            <th class="px-4 py-3 text-left">Kecamatan / Desa</th>
            <th class="px-4 py-3 text-left">Kepala</th>
            <th class="px-4 py-3 text-left">WA</th>
            <th class="px-4 py-3 text-left">Paket</th>
            <th class="px-4 py-3 text-right">Kupon</th>
            <th class="px-4 py-3 text-right">Kaos</th>
            <?php if ($adaHarga): ?><th class="px-4 py-3 text-right">Estimasi</th><?php endif; ?>
            <th class="px-4 py-3 text-left">Tanggal</th>
            <th class="px-4 py-3 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($rows as $index => $row): ?>
            <?php $kupon = (int) $row['jumlah_kupon']; $kaos = (int) $row['jumlah_kaos']; ?>
            <tr class="hover:bg-gray-50 align-top">
              <td class="px-4 py-3 text-gray-500"><?= $index + 1 ?></td>
              <td class="px-4 py-3 font-semibold max-w-[14rem]"><?= sanitize((string) $row['nama_madrasah']) ?></td>
              <td class="px-4 py-3 max-w-[16rem]">
                <?php if (!empty($row['nama_kecamatan'])): ?>
                  <p class="font-medium text-gray-800"><?= sanitize((string) $row['nama_kecamatan']) ?></p>
                  <p class="text-xs text-gray-600"><?= sanitize((string) ($row['nama_kelurahan'] ?? '')) ?></p>
                <?php endif; ?>
                <p class="text-xs text-gray-400"><?= sanitize(alamatDetailJalanSehat($row)) ?></p>
              </td>
              <td class="px-4 py-3"><?= sanitize((string) $row['nama_kepala']) ?></td>
              <td class="px-4 py-3 text-green-700 whitespace-nowrap"><?= sanitize((string) ($row['nomor_wa'] ?? '')) ?: '-' ?></td>
              <td class="px-4 py-3"><?= sanitize(!empty($row['paket_nama']) ? (string) $row['paket_nama'] : 'Jumlah sendiri') ?></td>
              <td class="px-4 py-3 text-right font-semibold"><?= $kupon ?></td>
              <td class="px-4 py-3 text-right">
                <span class="font-semibold"><?= $kaos ?></span>
                <?php $rincian = formatRincianUkuranKaos($row); ?>
                <?php if ($rincian !== ''): ?><span class="block text-xs text-gray-500 whitespace-nowrap"><?= sanitize($rincian) ?></span><?php endif; ?>
              </td>
              <?php if ($adaHarga): ?>
                <td class="px-4 py-3 text-right whitespace-nowrap"><?= sanitize(formatRupiahJalanSehat(hitungBiayaJalanSehat($kupon, $kaos, $settings))) ?></td>
              <?php endif; ?>
              <td class="px-4 py-3 whitespace-nowrap text-gray-500"><?= sanitize((string) $row['created_at']) ?></td>
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <a href="<?= url('adminjalansehat/?page=detail&id=' . (int) $row['id']) ?>"
                   class="text-green-700 hover:underline font-medium">Detail</a>
                <a href="<?= url('adminjalansehat/?page=edit&id=' . (int) $row['id']) ?>"
                   class="ml-2 text-green-700 hover:underline font-medium">Edit</a>
                <form method="post" class="inline" onsubmit="return confirm('Hapus pendaftaran <?= sanitize(addslashes((string) $row['nama_madrasah'])) ?>?');">
                  <input type="hidden" name="action" value="delete_pendaftaran">
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button type="submit" class="ml-2 text-red-600 hover:underline font-medium">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
