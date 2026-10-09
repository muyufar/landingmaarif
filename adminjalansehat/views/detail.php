<?php

declare(strict_types=1);

/** @var array $row @var array $settings */
$kupon = (int) $row['jumlah_kupon'];
$kaos = (int) $row['jumlah_kaos'];
$nomorWa = (string) ($row['nomor_wa'] ?? '');
$waLink = $nomorWa !== '' ? 'https://wa.me/62' . ltrim(normalizeNomorWa($nomorWa), '0') : '';
?>
<div class="max-w-3xl">
  <a href="<?= url('adminjalansehat/?page=list') ?>" class="text-sm text-green-700 hover:underline">← Kembali ke daftar</a>

  <div class="mt-4 bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="text-xl font-bold text-green-800"><?= sanitize((string) $row['nama_madrasah']) ?></h2>
      <p class="text-sm text-gray-500 mt-1">Didaftarkan: <?= sanitize((string) $row['created_at']) ?></p>
    </div>

    <dl class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
      <dt class="text-gray-500">Nama Madrasah</dt>
      <dd class="sm:col-span-2 font-semibold"><?= sanitize((string) $row['nama_madrasah']) ?></dd>

      <?php if (!empty($row['nama_kecamatan'])): ?>
        <dt class="text-gray-500">Kecamatan</dt>
        <dd class="sm:col-span-2"><?= sanitize((string) $row['nama_kecamatan']) ?></dd>

        <dt class="text-gray-500">Desa / Kelurahan</dt>
        <dd class="sm:col-span-2"><?= sanitize((string) ($row['nama_kelurahan'] ?? '')) ?></dd>
      <?php endif; ?>

      <dt class="text-gray-500">Alamat Lengkap</dt>
      <dd class="sm:col-span-2"><?= nl2br(sanitize((string) $row['alamat'])) ?></dd>

      <dt class="text-gray-500">Nama Kepala</dt>
      <dd class="sm:col-span-2"><?= sanitize((string) $row['nama_kepala']) ?></dd>

      <dt class="text-gray-500">Nomor WA</dt>
      <dd class="sm:col-span-2">
        <?php if ($waLink !== ''): ?>
          <a href="<?= sanitize($waLink) ?>" target="_blank" rel="noopener noreferrer" class="text-green-700 hover:underline"><?= sanitize($nomorWa) ?></a>
        <?php else: ?>
          -
        <?php endif; ?>
      </dd>

      <dt class="text-gray-500">Pilihan Paket</dt>
      <dd class="sm:col-span-2"><?= sanitize(!empty($row['paket_nama']) ? (string) $row['paket_nama'] : 'Jumlah sendiri') ?></dd>

      <dt class="text-gray-500">Kupon Door Prize</dt>
      <dd class="sm:col-span-2 font-bold text-green-800"><?= $kupon ?> kupon</dd>

      <dt class="text-gray-500">Kaos</dt>
      <dd class="sm:col-span-2">
        <p class="font-bold text-green-800"><?= $kaos ?> pcs</p>
        <?php if ($kaos > 0): ?>
          <div class="mt-2 space-y-3">
            <?php foreach (jalanSehatUkuranKaosByJenis() as $jenisInfo): ?>
              <div>
                <p class="text-xs font-semibold text-gray-600 mb-1"><?= sanitize($jenisInfo['label']) ?></p>
                <div class="flex flex-wrap gap-2">
                  <?php foreach ($jenisInfo['columns'] as $label => $column): ?>
                    <?php $qty = (int) ($row[$column] ?? 0); ?>
                    <?php if ($qty > 0): ?>
                      <span class="rounded-lg border border-green-300 bg-green-50 px-3 py-1 text-xs text-green-900 font-semibold">
                        <?= sanitize($label) ?>: <?= $qty ?>
                      </span>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </dd>

      <?php if (jalanSehatAdaHarga($settings)): ?>
        <dt class="text-gray-500">Estimasi Biaya</dt>
        <dd class="sm:col-span-2 font-bold text-green-800"><?= sanitize(formatRupiahJalanSehat(hitungBiayaJalanSehat($kupon, $kaos, $settings))) ?></dd>
      <?php endif; ?>

      <dt class="text-gray-500">Catatan</dt>
      <dd class="sm:col-span-2"><?= !empty($row['catatan']) ? nl2br(sanitize((string) $row['catatan'])) : '-' ?></dd>
    </dl>

    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
      <form method="post" onsubmit="return confirm('Hapus pendaftaran ini?');">
        <input type="hidden" name="action" value="delete_pendaftaran">
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Hapus Pendaftaran</button>
      </form>
    </div>
  </div>
</div>
