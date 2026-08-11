<?php

declare(strict_types=1);

/** @var array $formData @var array $errors @var bool $isEdit @var int $editId */
?>
<div class="max-w-3xl">
  <div class="mb-4">
    <a href="<?= url('admin/?page=berita') ?>" class="text-green-700 hover:underline text-sm">← Kembali ke daftar berita</a>
  </div>

  <div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="text-xl font-bold text-green-800"><?= $isEdit ? 'Edit Berita' : 'Tulis Berita Baru' ?></h2>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="mx-6 mt-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc list-inside space-y-1">
          <?php foreach ($errors as $error): ?>
            <li><?= sanitize($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="px-6 py-6 space-y-5">
      <input type="hidden" name="save_berita" value="1">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $editId ?>">
      <?php endif; ?>

      <div>
        <label for="judul" class="block text-sm font-semibold text-gray-700 mb-2">Judul <span class="text-red-500">*</span></label>
        <input type="text" id="judul" name="judul" required value="<?= sanitize($formData['judul'] ?? '') ?>"
               class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-green-600">
      </div>

      <div>
        <label for="ringkasan" class="block text-sm font-semibold text-gray-700 mb-2">Ringkasan</label>
        <textarea id="ringkasan" name="ringkasan" rows="2"
                  class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-green-600"
                  placeholder="Cuplikan singkat untuk daftar berita (opsional)"><?= sanitize($formData['ringkasan'] ?? '') ?></textarea>
      </div>

      <div>
        <label for="konten" class="block text-sm font-semibold text-gray-700 mb-2">Isi Berita <span class="text-red-500">*</span></label>
        <textarea id="konten" name="konten" required rows="12"
                  class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-green-600"
                  placeholder="Tulis isi berita di sini..."><?= sanitize($formData['konten'] ?? '') ?></textarea>
      </div>

      <div>
        <label for="gambar" class="block text-sm font-semibold text-gray-700 mb-2">Gambar Utama</label>
        <?php if (!empty($formData['gambar'])): ?>
          <div class="mb-3">
            <img src="<?= url($formData['gambar']) ?>" alt="Gambar berita" class="w-48 h-32 object-cover rounded-lg border border-gray-200">
            <p class="text-xs text-gray-500 mt-1">Gambar saat ini. Upload file baru untuk mengganti.</p>
          </div>
        <?php endif; ?>
        <input type="file" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
               class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm bg-white">
        <p class="text-xs text-gray-500 mt-1">JPG / PNG / WEBP, maks. 3 MB</p>
      </div>

      <div>
        <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
        <select id="status" name="status" class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-green-600">
          <option value="draft" <?= ($formData['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (belum tampil di website)</option>
          <option value="published" <?= ($formData['status'] ?? '') === 'published' ? 'selected' : '' ?>>Terbitkan</option>
        </select>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 pt-2">
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg">
          <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Berita' ?>
        </button>
        <a href="<?= url('admin/?page=berita') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-6 py-3 rounded-lg text-center">Batal</a>
      </div>
    </form>
  </div>
</div>
