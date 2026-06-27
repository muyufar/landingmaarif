<?php

declare(strict_types=1);

/** @var array $formData */
$jenisOptions = jenisLembagaOptions();
$selected = $formData['jenis_lembaga'] ?? '';
?>
<div>
  <label for="jenis_lembaga" class="block text-sm font-semibold text-gray-700 mb-2">JENIS LEMBAGA <span class="text-red-500">*</span></label>
  <select id="jenis_lembaga" name="jenis_lembaga" required
          class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 bg-white text-sm">
    <option value="">-- Pilih Jenis Lembaga --</option>
    <?php foreach ($jenisOptions as $opt): ?>
      <option value="<?= sanitize($opt) ?>" <?= strcasecmp((string) $selected, $opt) === 0 ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
    <?php endforeach; ?>
  </select>
  <p class="text-xs text-gray-500 mt-1">Contoh: MI, MTS, MA, SD, SMP, SMK, SMA, SLB, Pengurus LP Maarif MWC</p>
</div>
