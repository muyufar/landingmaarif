<?php

declare(strict_types=1);

/** @var array $formData @var string $inputClass @var bool $showPaketPicker @var array $paketAktif */
if (!function_exists('jsAdminField')) {
    function jsAdminField(string $key, array $formData): string
    {
        return sanitize((string) ($formData[$key] ?? ''));
    }
}
$pilihanPaket = trim((string) ($formData['pilihan_paket'] ?? ''));
if ($pilihanPaket === '') {
    $pilihanPaket = 'custom';
}
?>
<?php if (!empty($showPaketPicker)): ?>
  <div>
    <label class="block text-sm font-semibold text-gray-700 mb-1">Paket pemesanan</label>
    <select name="pilihan_paket" class="<?= $inputClass ?>">
      <option value="custom" <?= $pilihanPaket === 'custom' ? 'selected' : '' ?>>Jumlah sendiri (kupon &amp; kaos bebas)</option>
      <?php foreach ($paketAktif as $paket): ?>
        <option value="<?= (int) $paket['id'] ?>" <?= $pilihanPaket === (string) $paket['id'] ? 'selected' : '' ?>>
          <?= sanitize((string) $paket['nama']) ?> — <?= sanitize(formatPaketJalanSehat((int) $paket['jumlah_kupon'], (int) $paket['jumlah_kaos'])) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <p class="text-xs text-gray-500 mt-1">Jika memilih paket, isi rincian ukuran kaos sesuai jumlah kaos paket.</p>
  </div>
<?php else: ?>
  <input type="hidden" name="pilihan_paket" value="<?= sanitize($pilihanPaket) ?>">
<?php endif; ?>

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
    <p class="text-xs text-gray-500 mt-1">Admin boleh mengubah manual. Form publik otomatis; di sini tidak ditimpa saat simpan.</p>
    <button type="button" id="admin-sync-kupon-kaos" class="text-xs font-semibold text-green-800 underline hover:text-green-900 mt-1">
      Hitung kupon bonus dari total kaos (×<?= jalanSehatKuponPerKaos($settings ?? getJalanSehatPengaturan()) ?>)
    </button>
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
      <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-7 gap-2">
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
