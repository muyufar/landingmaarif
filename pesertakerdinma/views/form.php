<?php

declare(strict_types=1);

/** @var array $formData @var array $errors @var bool $isEdit @var int|null $editId */
?>
<div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
  <div class="px-6 py-5 border-b border-gray-100">
    <h2 class="text-xl font-bold text-green-800"><?= $isEdit ? 'Edit Peserta' : 'Tambah Peserta' ?></h2>
    <p class="text-sm text-gray-500 mt-1"><?= $isEdit ? 'Perbarui data peserta RAKERDINMA' : 'Input manual peserta oleh admin' ?></p>
  </div>
  <div class="px-6 py-6">
    <?php if (!empty($errors)): ?>
      <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
        <ul class="list-disc list-inside space-y-1">
          <?php foreach ($errors as $error): ?>
            <li><?= sanitize($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <input type="hidden" name="save_peserta" value="1">
      <?php if ($isEdit && $editId): ?>
        <input type="hidden" name="id" value="<?= (int) $editId ?>">
      <?php endif; ?>

      <div class="grid md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-semibold mb-1">Nama *</label>
          <input type="text" name="nama" required value="<?= sanitize($formData['nama']) ?>"
                 class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-green-600">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">NIP</label>
          <input type="text" name="nip" value="<?= sanitize($formData['nip']) ?>"
                 class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-green-600">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Nomor WA *</label>
          <input type="tel" name="nomor_wa" required value="<?= sanitize($formData['nomor_wa']) ?>"
                 class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-green-600">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Tempat Lahir *</label>
          <input type="text" name="tempat_lahir" required value="<?= sanitize($formData['tempat_lahir']) ?>"
                 class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-green-600">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Tanggal Lahir *</label>
          <input type="date" name="tanggal_lahir" required value="<?= sanitize($formData['tanggal_lahir']) ?>"
                 class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-green-600">
        </div>
      </div>

      <?php require __DIR__ . '/../_jabatan_transportasi_fields.php'; ?>

      <div class="grid md:grid-cols-2 gap-4">
        <?php require __DIR__ . '/../_jenis_lembaga_field.php'; ?>
        <div>
          <label class="block text-sm font-semibold mb-1">Nama Lembaga *</label>
          <input type="text" name="asal_lembaga" required placeholder="Contoh: MI Ma'arif Giritengah"
                 value="<?= sanitize($formData['asal_lembaga']) ?>"
                 class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-green-600">
        </div>
      </div>

      <?php require __DIR__ . '/../_wilayah_fields.php'; ?>

      <div class="flex gap-3 pt-4">
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-6 py-2.5 rounded-lg text-sm font-semibold">
          <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Peserta' ?>
        </button>
        <a href="<?= url('pesertakerdinma/?page=list') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2.5 rounded-lg text-sm font-semibold">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../_jabatan_transportasi_script.php'; ?>
<?php require __DIR__ . '/../_wilayah_script.php'; ?>
