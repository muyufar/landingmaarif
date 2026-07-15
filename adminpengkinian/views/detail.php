<?php

declare(strict_types=1);

/** @var array $row */
?>
<div class="max-w-3xl">
  <div class="mb-6">
    <a href="<?= url('adminpengkinian/?page=list') ?>" class="text-green-700 hover:underline text-sm">← Kembali ke List</a>
  </div>

  <div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-start gap-4">
      <div>
        <h2 class="text-xl font-bold text-green-800">Detail #<?= (int) ($row['id'] ?? 0) ?></h2>
        <p class="text-sm text-gray-500 mt-1">
          Dibuat: <?= sanitize($row['created_at'] ?? '') ?>
          · Diperbarui: <?= sanitize($row['updated_at'] ?? '') ?>
        </p>
      </div>
      <form method="post" onsubmit="return confirm('Hapus data satuan pendidikan ini?');">
        <input type="hidden" name="delete_id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">Hapus</button>
      </form>
    </div>

    <dl class="px-6 py-6 grid sm:grid-cols-2 gap-5 text-sm">
      <div class="sm:col-span-2">
        <dt class="font-semibold text-gray-500">NPSN</dt>
        <dd class="mt-0.5 font-mono text-green-800"><?= sanitize($row['npsn'] ?? '') ?></dd>
      </div>
      <div class="sm:col-span-2">
        <dt class="font-semibold text-gray-500">Nama Satuan Pendidikan</dt>
        <dd class="mt-0.5 text-lg font-semibold text-green-800"><?= sanitize($row['nama_satuan_pendidikan'] ?? '') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Nama Kepala Sekolah</dt>
        <dd class="mt-0.5"><?= sanitize($row['nama_kepala_sekolah'] ?? '') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Nama Operator</dt>
        <dd class="mt-0.5"><?= sanitize($row['nama_operator'] ?? '') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Nomor HP Kepala Sekolah</dt>
        <dd class="mt-0.5 text-green-700 font-medium"><?= sanitize($row['nomor_hp_kepsek'] ?? '') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Nomor HP Operator</dt>
        <dd class="mt-0.5 text-green-700 font-medium"><?= sanitize($row['nomor_hp_operator'] ?? '') ?></dd>
      </div>
      <div class="sm:col-span-2">
        <dt class="font-semibold text-gray-500">Alamat Lengkap</dt>
        <dd class="mt-0.5 leading-relaxed"><?= sanitize($row['alamat_lengkap'] ?? '') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Kecamatan</dt>
        <dd class="mt-0.5"><?= sanitize($row['nama_kecamatan'] ?? '') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Desa/Kelurahan</dt>
        <dd class="mt-0.5"><?= sanitize($row['nama_kelurahan'] ?? '') ?></dd>
      </div>
      <div class="sm:col-span-2">
        <dt class="font-semibold text-gray-500">Alamat Detail</dt>
        <dd class="mt-0.5 whitespace-pre-wrap"><?= sanitize($row['alamat_detail'] ?? '') ?></dd>
      </div>
    </dl>
  </div>
</div>
