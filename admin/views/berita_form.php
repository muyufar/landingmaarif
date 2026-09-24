<?php

declare(strict_types=1);

/** @var array $formData @var array $errors @var bool $isEdit @var int $editId */
$galeri = $formData['galeri'] ?? [];
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
        <label class="block text-sm font-semibold text-gray-700 mb-2">Galeri Gambar</label>
        <?php if (!empty($galeri)): ?>
          <div class="mb-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
            <?php foreach ($galeri as $img): ?>
              <div class="relative rounded-lg border border-gray-200 overflow-hidden bg-gray-50">
                <img src="<?= url($img['path'] ?? '') ?>" alt="" class="w-full h-28 object-cover">
                <?php if ($isEdit && !empty($img['id'])): ?>
                  <label class="absolute inset-x-0 bottom-0 bg-black/55 text-white text-[11px] px-2 py-1 flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" name="hapus_gambar[]" value="<?= (int) $img['id'] ?>" class="rounded">
                    Hapus
                  </label>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <p class="text-xs text-gray-500 mb-2">Centang gambar yang ingin dihapus, lalu simpan. Gambar pertama menjadi sampul daftar berita.</p>
        <?php endif; ?>
        <input type="file" id="gambar" name="gambar[]" multiple
               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
               class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm bg-white">
        <p class="text-xs text-gray-500 mt-1">Bisa pilih lebih dari satu. JPG / PNG / WEBP, maks. 3 MB per file.</p>
      </div>

      <div>
        <label for="pdf" class="block text-sm font-semibold text-gray-700 mb-2">Dokumen PDF</label>
        <?php if (!empty($formData['pdf'])): ?>
          <div class="mb-3 rounded-lg border border-red-100 bg-red-50/40 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate"><?= sanitize(beritaPdfBasename(['pdf' => $formData['pdf']])) ?></p>
              <a href="<?= url($formData['pdf']) ?>" target="_blank" rel="noopener"
                 class="text-xs text-green-700 hover:underline">Buka / pratinjau PDF</a>
            </div>
            <?php if ($isEdit): ?>
              <label class="inline-flex items-center gap-2 text-sm text-red-700 shrink-0 cursor-pointer">
                <input type="checkbox" name="hapus_pdf" value="1" class="rounded">
                Hapus PDF
              </label>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <input type="file" id="pdf" name="pdf"
               accept=".pdf,application/pdf"
               class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm bg-white">
        <p class="text-xs text-gray-500 mt-1">Opsional. PDF maks. 15 MB — pembaca bisa membaca langsung di halaman berita.</p>
      </div>

      <div>
        <label for="youtube_url" class="block text-sm font-semibold text-gray-700 mb-2">Video YouTube</label>
        <input type="url" id="youtube_url" name="youtube_url"
               value="<?= sanitize($formData['youtube_url'] ?? '') ?>"
               placeholder="https://www.youtube.com/watch?v=XXXXXXXXXXX"
               class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-green-600">
        <p class="text-xs text-gray-500 mt-1">
          Opsional. Tempel link YouTube (watch, youtu.be, shorts, atau embed). Kosongkan jika tidak ada video.
        </p>
        <?php if (!empty($formData['youtube_url']) && beritaHasYoutube($formData)): ?>
          <div class="mt-3 rounded-xl overflow-hidden border border-gray-200 bg-black aspect-video">
            <iframe
              src="<?= sanitize(youtubeEmbedUrl($formData['youtube_url']) ?? '') ?>"
              title="Pratinjau YouTube"
              class="w-full h-full"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
              loading="lazy"></iframe>
          </div>
        <?php endif; ?>
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
