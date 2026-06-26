<?php

declare(strict_types=1);

/** @var array $formData */
$jabatanPilihan = $formData['jabatan_pilihan'] ?? choiceFieldFormState($formData['jabatan'] ?? '', jabatanOptions())['pilihan'];
$jabatanLainnya = $formData['jabatan_lainnya'] ?? choiceFieldFormState($formData['jabatan'] ?? '', jabatanOptions())['lainnya'];
$transportPilihan = $formData['transportasi_pilihan'] ?? choiceFieldFormState($formData['alat_transportasi'] ?? '', transportasiOptions())['pilihan'];
$transportLainnya = $formData['transportasi_lainnya'] ?? choiceFieldFormState($formData['alat_transportasi'] ?? '', transportasiOptions())['lainnya'];
?>
<div class="grid md:grid-cols-2 gap-4">
  <div>
    <label for="jabatan_pilihan" class="block text-sm font-semibold text-gray-700 mb-2">JABATAN <span class="text-red-500">*</span></label>
    <select id="jabatan_pilihan" name="jabatan_pilihan" required data-choice-target="jabatan_lainnya_wrap"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm bg-white focus:ring-2 focus:ring-green-600">
      <option value="">-- Pilih Jabatan --</option>
      <?php foreach (jabatanOptions() as $opt): ?>
        <option value="<?= sanitize($opt) ?>" <?= $jabatanPilihan === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
      <?php endforeach; ?>
    </select>
    <div id="jabatan_lainnya_wrap" class="mt-2 <?= $jabatanPilihan === 'Lainnya' ? '' : 'hidden' ?>">
      <input type="text" id="jabatan_lainnya" name="jabatan_lainnya" placeholder="Tulis jabatan lainnya"
             value="<?= sanitize($jabatanLainnya) ?>"
             class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:ring-2 focus:ring-green-600">
    </div>
  </div>
  <div>
    <label for="transportasi_pilihan" class="block text-sm font-semibold text-gray-700 mb-2">ALAT TRANSPORTASI <span class="text-red-500">*</span></label>
    <select id="transportasi_pilihan" name="transportasi_pilihan" required data-choice-target="transportasi_lainnya_wrap"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm bg-white focus:ring-2 focus:ring-green-600">
      <option value="">-- Pilih Transportasi --</option>
      <?php foreach (transportasiOptions() as $opt): ?>
        <option value="<?= sanitize($opt) ?>" <?= $transportPilihan === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
      <?php endforeach; ?>
    </select>
    <div id="transportasi_lainnya_wrap" class="mt-2 <?= $transportPilihan === 'Lainnya' ? '' : 'hidden' ?>">
      <input type="text" id="transportasi_lainnya" name="transportasi_lainnya" placeholder="Tulis transportasi lainnya"
             value="<?= sanitize($transportLainnya) ?>"
             class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:ring-2 focus:ring-green-600">
    </div>
  </div>
</div>
