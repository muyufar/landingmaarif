<?php

declare(strict_types=1);

/** @var array $formData */
?>
<div class="space-y-4 border border-gray-200 rounded-xl p-4 bg-gray-50">
  <p class="text-sm font-semibold text-gray-700">Alamat Lembaga</p>
  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <label for="kode_provinsi" class="block text-xs font-medium text-gray-600 mb-1">Provinsi</label>
      <select id="kode_provinsi" name="kode_provinsi" required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-green-600">
        <option value="">-- Pilih Provinsi --</option>
      </select>
      <input type="hidden" id="nama_provinsi" name="nama_provinsi" value="<?= sanitize($formData['nama_provinsi'] ?? '') ?>">
    </div>
    <div>
      <label for="kode_kabupaten" class="block text-xs font-medium text-gray-600 mb-1">Kabupaten / Kota</label>
      <select id="kode_kabupaten" name="kode_kabupaten" required disabled
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white disabled:bg-gray-100 focus:ring-2 focus:ring-green-600">
        <option value="">-- Pilih Kabupaten/Kota --</option>
      </select>
      <input type="hidden" id="nama_kabupaten" name="nama_kabupaten" value="<?= sanitize($formData['nama_kabupaten'] ?? '') ?>">
    </div>
  </div>
  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <label for="kode_kecamatan" class="block text-xs font-medium text-gray-600 mb-1">Kecamatan</label>
      <select id="kode_kecamatan" name="kode_kecamatan" required disabled
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white disabled:bg-gray-100 focus:ring-2 focus:ring-green-600">
        <option value="">-- Pilih Kecamatan --</option>
      </select>
      <input type="hidden" id="nama_kecamatan" name="nama_kecamatan" value="<?= sanitize($formData['nama_kecamatan'] ?? '') ?>">
    </div>
    <div>
      <label for="kode_kelurahan" class="block text-xs font-medium text-gray-600 mb-1">Kelurahan / Desa</label>
      <select id="kode_kelurahan" name="kode_kelurahan" required disabled
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white disabled:bg-gray-100 focus:ring-2 focus:ring-green-600">
        <option value="">-- Pilih Kelurahan/Desa --</option>
      </select>
      <input type="hidden" id="nama_kelurahan" name="nama_kelurahan" value="<?= sanitize($formData['nama_kelurahan'] ?? '') ?>">
    </div>
  </div>
  <div>
    <label for="alamat_detail" class="block text-xs font-medium text-gray-600 mb-1">Alamat Detail</label>
    <textarea id="alamat_detail" name="alamat_detail" required rows="2"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-green-600"><?= sanitize($formData['alamat_detail'] ?? '') ?></textarea>
  </div>
</div>
