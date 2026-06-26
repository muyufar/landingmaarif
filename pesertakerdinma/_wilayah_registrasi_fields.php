<?php

declare(strict_types=1);

/** @var array $formData */
$defaults = defaultWilayahMagelang();
?>
<div class="space-y-4">
  <p class="block text-sm font-semibold text-gray-700">ALAMAT LEMBAGA <span class="text-red-500">*</span></p>
  <p class="text-xs text-gray-500 -mt-2">Provinsi Jawa Tengah, Kabupaten Magelang</p>

  <input type="hidden" name="kode_provinsi" value="<?= sanitize($defaults['kode_provinsi']) ?>">
  <input type="hidden" name="nama_provinsi" value="<?= sanitize($defaults['nama_provinsi']) ?>">
  <input type="hidden" name="kode_kabupaten" value="<?= sanitize($defaults['kode_kabupaten']) ?>">
  <input type="hidden" name="nama_kabupaten" value="<?= sanitize($defaults['nama_kabupaten']) ?>">
  <input type="hidden" name="kode_kelurahan" value="">
  <input type="hidden" name="nama_kelurahan" value="">

  <div>
    <label for="kode_kecamatan" class="block text-xs font-medium text-gray-600 mb-1">Kecamatan <span class="text-red-500">*</span></label>
    <select id="kode_kecamatan" name="kode_kecamatan" required
            class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white">
      <option value="">-- Pilih Kecamatan --</option>
    </select>
    <input type="hidden" id="nama_kecamatan" name="nama_kecamatan" value="<?= sanitize($formData['nama_kecamatan'] ?? '') ?>">
  </div>

  <div>
    <label for="alamat_detail" class="block text-xs font-medium text-gray-600 mb-1">Alamat Detail (Jalan, RT/RW, Desa/Kelurahan, dll.) <span class="text-red-500">*</span></label>
    <textarea id="alamat_detail" name="alamat_detail" required rows="2" placeholder="Contoh: Jl. Raya Magelang No. 12, RT 03/RW 05, Desa ..."
              class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent"><?= sanitize($formData['alamat_detail'] ?? '') ?></textarea>
  </div>
</div>
