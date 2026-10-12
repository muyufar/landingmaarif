<?php

declare(strict_types=1);

/** @var array $formData @var array $formErrors @var array $paketAktif @var array|null $pendaftaranDuplicateRow */
$inputClass = 'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-600 bg-white';
$showPaketPicker = true;
?>
<div class="max-w-3xl">
  <a href="<?= url('adminjalansehat/?page=list') ?>" class="text-sm text-green-700 hover:underline">← Kembali ke daftar</a>

  <div class="mt-4 bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="text-xl font-bold text-green-800">Input Pendaftaran Manual</h2>
      <p class="text-sm text-gray-500 mt-1">Tambah data madrasah yang mendaftar Jalan Sehat (via panitia / offline).</p>
    </div>

    <div class="px-6 py-6">
      <?php if (!empty($formErrors)): ?>
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
          <ul class="list-disc list-inside space-y-1">
            <?php foreach ($formErrors as $error): ?>
              <li><?= sanitize($error) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php if (!empty($pendaftaranDuplicateRow['id'])): ?>
            <a href="<?= url('adminjalansehat/?page=edit&id=' . (int) $pendaftaranDuplicateRow['id']) ?>"
               class="inline-block mt-3 bg-green-700 hover:bg-green-800 text-white font-semibold px-4 py-2 rounded-lg text-sm">
              Edit pendaftaran yang sudah ada
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-6" id="admin-js-form">
        <input type="hidden" name="action" value="create_pendaftaran">
        <?php require __DIR__ . '/_pendaftaran_form_body.php'; ?>

        <div class="flex flex-wrap gap-3 pt-2">
          <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-2.5 rounded-lg">Simpan Pendaftaran</button>
          <a href="<?= url('adminjalansehat/?page=list') ?>" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm">Batal</a>
        </div>
      </form>
    </div>
  </div>
</div>
