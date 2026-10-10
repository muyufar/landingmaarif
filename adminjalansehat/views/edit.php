<?php

declare(strict_types=1);

/** @var array $row @var array $settings @var array $formData @var array $formErrors @var array $paketAktif */
$inputClass = 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-600 bg-white';
$id = (int) $row['id'];

function jsAdminField(string $key, array $formData): string
{
    return sanitize((string) ($formData[$key] ?? ''));
}
?>
<div class="max-w-3xl">
  <a href="<?= url('adminjalansehat/?page=detail&id=' . $id) ?>" class="text-sm text-green-700 hover:underline">← Kembali ke detail</a>

  <div class="mt-4 bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="text-xl font-bold text-green-800">Edit Pendaftaran</h2>
      <p class="text-sm text-gray-500 mt-1"><?= sanitize((string) $row['nama_madrasah']) ?> · ID #<?= $id ?></p>
    </div>

    <div class="px-6 py-6">
      <?php if (!empty($formErrors)): ?>
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
          <ul class="list-disc list-inside space-y-1">
            <?php foreach ($formErrors as $error): ?>
              <li><?= sanitize($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-6" id="admin-js-form">
        <input type="hidden" name="action" value="save_pendaftaran">
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php
          $pilihanPaket = trim((string) ($formData['pilihan_paket'] ?? ''));
          if ($pilihanPaket === '') {
              $pilihanPaket = !empty($row['paket_id']) ? (string) $row['paket_id'] : 'custom';
          }
        ?>
        <input type="hidden" name="pilihan_paket" value="<?= sanitize($pilihanPaket) ?>">

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Madrasah</label>
            <input type="text" name="nama_madrasah" required maxlength="200" value="<?= jsAdminField('nama_madrasah', $formData) ?>" class="<?= $inputClass ?>">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Kepala</label>
            <input type="text" name="nama_kepala" required maxlength="150" value="<?= jsAdminField('nama_kepala', $formData) ?>" class="<?= $inputClass ?>">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor HP Kepala</label>
            <input type="tel" name="nomor_hp_kepala" maxlength="20" value="<?= jsAdminField('nomor_hp_kepala', $formData) ?>" class="<?= $inputClass ?>">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor WA</label>
            <input type="tel" name="nomor_wa" maxlength="20" value="<?= jsAdminField('nomor_wa', $formData) ?>" class="<?= $inputClass ?>">
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Kupon door prize</label>
            <input type="number" name="jumlah_kupon" min="0" id="jumlah_kupon" value="<?= (int) ($formData['jumlah_kupon'] ?? 0) ?>" class="<?= $inputClass ?>">
          </div>
        </div>

        <?php
          $wilayahSectionTitle = 'Alamat Madrasah';
          require dirname(__DIR__, 2) . '/pesertakerdinma/_wilayah_registrasi_fields.php';
        ?>

        <div>
          <p class="text-sm font-bold text-green-800 mb-2">Rincian kaos per ukuran</p>
          <?php foreach (jalanSehatUkuranKaosByJenis() as $jenisInfo): ?>
            <div class="mb-4 rounded-lg border border-gray-200 p-3">
              <p class="text-xs font-semibold text-gray-600 mb-2"><?= sanitize($jenisInfo['label']) ?></p>
              <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                <?php foreach ($jenisInfo['columns'] as $label => $column): ?>
                  <label class="text-center text-sm">
                    <span class="block font-bold text-green-800"><?= sanitize($label) ?></span>
                    <input type="number" name="<?= sanitize($column) ?>" min="0"
                           value="<?= (int) ($formData[$column] ?? 0) ?>"
                           class="js-kaos-input mt-1 w-full rounded border-gray-300 text-center">
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
          <p class="text-sm text-gray-600">Total kaos: <strong id="total-kaos">0</strong> pcs</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan</label>
          <textarea name="catatan" rows="3" class="<?= $inputClass ?>"><?= jsAdminField('catatan', $formData) ?></textarea>
        </div>

        <div class="flex flex-wrap gap-3 pt-2">
          <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-2.5 rounded-lg">Simpan</button>
          <a href="<?= url('adminjalansehat/?page=detail&id=' . $id) ?>" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm">Batal</a>
        </div>
      </form>
    </div>
  </div>
</div>
