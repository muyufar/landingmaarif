<?php

declare(strict_types=1);

/** @var array $row */
$jenis = $row['jenis_layanan'] ?? 'mopdik';
$layanan = getPemesananLayanan($jenis);
?>
<div class="max-w-3xl">
  <div class="mb-6">
    <a href="<?= url('adminpemesananbuku/?page=list') ?>" class="text-green-700 hover:underline text-sm">← Kembali ke List</a>
  </div>

  <div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-start gap-4">
      <div>
        <h2 class="text-xl font-bold text-green-800">Detail Pemesanan #<?= (int) ($row['id'] ?? 0) ?></h2>
        <p class="text-sm text-gray-500 mt-1"><?= sanitize($row['created_at'] ?? '') ?></p>
      </div>
      <form method="post" onsubmit="return confirm('Hapus pemesanan ini?');">
        <input type="hidden" name="delete_id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">Hapus</button>
      </form>
    </div>

    <dl class="px-6 py-6 grid sm:grid-cols-2 gap-5 text-sm">
      <div class="sm:col-span-2"><dt class="font-semibold text-gray-500">Jenis Layanan</dt><dd class="mt-0.5 font-semibold text-green-800"><?= sanitize(labelJenisLayanan($jenis)) ?></dd></div>
      <div><dt class="font-semibold text-gray-500">Nama Madrasah/Sekolah</dt><dd class="mt-0.5"><?= sanitize($row['nama_madrasah'] ?? '') ?></dd></div>
      <div><dt class="font-semibold text-gray-500">Nama Kepala/Kepsek</dt><dd class="mt-0.5"><?= sanitize($row['nama_kepala'] ?? '') ?></dd></div>
      <div><dt class="font-semibold text-gray-500">Nomor WA</dt><dd class="mt-0.5 text-green-700"><?= sanitize($row['nomor_wa'] ?? '') ?></dd></div>
      <div><dt class="font-semibold text-gray-500">Jenjang</dt><dd class="mt-0.5"><?= sanitize($row['jenjang'] ?? '') ?></dd></div>

      <?php if (($layanan['tipe'] ?? '') === 'kenuan'): ?>
      <div class="sm:col-span-2">
        <dt class="font-semibold text-gray-500 mb-2">Jumlah Buku per Kelas</dt>
        <dd class="mt-0.5">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm">
            <?php foreach (bukuKenuanKelasFields() as $key => $label): ?>
              <?php $qty = (int) ($row[$key] ?? 0); ?>
              <?php if ($qty > 0): ?>
                <div class="bg-green-50 rounded px-3 py-2"><span class="text-gray-600"><?= sanitize($label) ?>:</span> <strong><?= $qty ?></strong></div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <p class="mt-2 font-semibold">Total: <?= getTotalKenuanKelas($row) ?> buku</p>
        </dd>
      </div>
      <?php elseif (($layanan['tipe'] ?? '') === 'jumlah'): ?>
      <div><dt class="font-semibold text-gray-500"><?= sanitize($layanan['jumlah_label'] ?? 'Jumlah') ?></dt><dd class="mt-0.5 font-semibold"><?= (int) getJumlahPemesanan($row) ?></dd></div>
      <?php endif; ?>

      <?php if (($layanan['tipe'] ?? '') === 'batik'): ?>
      <div><dt class="font-semibold text-gray-500">Jenis Pemesanan</dt><dd class="mt-0.5"><?php $batikTypes = parseJenisBatikSelected($row['jenis_batik'] ?? ''); echo $batikTypes !== [] ? sanitize(implode(', ', $batikTypes)) : '-'; ?></dd></div>
      <?php if (!empty($row['satuan_jenis_1'])): ?>
      <div><dt class="font-semibold text-gray-500">Satuan 1</dt><dd class="mt-0.5"><?= sanitize($row['satuan_jenis_1']) ?> × <?= (int) ($row['satuan_jumlah_1'] ?? 0) ?></dd></div>
      <?php endif; ?>
      <?php if (!empty($row['satuan_jenis_2'])): ?>
      <div><dt class="font-semibold text-gray-500">Satuan 2</dt><dd class="mt-0.5"><?= sanitize($row['satuan_jenis_2']) ?> × <?= (int) ($row['satuan_jumlah_2'] ?? 0) ?></dd></div>
      <?php endif; ?>
      <div class="sm:col-span-2"><dt class="font-semibold text-gray-500">Ukuran (S / M / L / XL / XXL)</dt>
        <dd class="mt-0.5"><?= (int) ($row['ukuran_s'] ?? 0) ?> / <?= (int) ($row['ukuran_m'] ?? 0) ?> / <?= (int) ($row['ukuran_l'] ?? 0) ?> / <?= (int) ($row['ukuran_xl'] ?? 0) ?> / <?= (int) ($row['ukuran_xxl'] ?? 0) ?></dd></div>
      <?php endif; ?>

      <?php if (!empty($row['catatan'])): ?>
      <div class="sm:col-span-2"><dt class="font-semibold text-gray-500">Catatan</dt><dd class="mt-0.5 whitespace-pre-wrap"><?= sanitize($row['catatan']) ?></dd></div>
      <?php endif; ?>
    </dl>
  </div>
</div>
