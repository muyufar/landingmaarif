<?php

declare(strict_types=1);

/** @var array $row */
$umur = hitungUmur($row['tanggal_lahir'] ?? '');
?>
<div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
  <div class="px-6 py-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h2 class="text-xl font-bold text-green-800">Detail Peserta</h2>
      <p class="text-sm text-gray-500 mt-1">ID #<?= (int) $row['id'] ?> · Daftar <?= sanitize($row['created_at'] ?? '-') ?></p>
    </div>
    <div class="flex gap-2">
      <a href="<?= url('pesertakerdinma/?export=sertifikat&id=' . (int) $row['id']) ?>"
         class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Sertifikat</a>
      <a href="<?= url('pesertakerdinma/?page=form&id=' . (int) $row['id']) ?>"
         class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Edit</a>
      <a href="<?= url('pesertakerdinma/?page=list') ?>"
         class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium">Kembali</a>
    </div>
  </div>
  <dl class="px-6 py-6 grid sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
    <div><dt class="font-semibold text-gray-500">Nomor Sertifikat</dt><dd class="mt-0.5 font-medium text-green-800"><?= sanitize(formatNomorSertifikat(getNomorSertifikatPeserta((int) $row['id']))) ?></dd></div>
    <div><dt class="font-semibold text-gray-500">Nama</dt><dd class="mt-0.5 font-medium"><?= sanitize($row['nama'] ?? '') ?></dd></div>
    <div><dt class="font-semibold text-gray-500">NIP</dt><dd class="mt-0.5"><?= sanitize($row['nip'] ?? '-') ?></dd></div>
    <div><dt class="font-semibold text-gray-500">Nomor WA</dt><dd class="mt-0.5 text-green-700 font-medium"><?= sanitize($row['nomor_wa'] ?? '') ?></dd></div>
    <div><dt class="font-semibold text-gray-500">Tempat, Tanggal Lahir</dt><dd class="mt-0.5"><?= sanitize($row['tempat_lahir'] ?? '') ?>, <?= sanitize($row['tanggal_lahir'] ?? '') ?></dd></div>
    <div><dt class="font-semibold text-gray-500">Umur</dt><dd class="mt-0.5"><?= $umur !== null ? $umur . ' tahun' : '-' ?></dd></div>
    <div><dt class="font-semibold text-gray-500">Jabatan</dt><dd class="mt-0.5"><?= sanitize($row['jabatan'] ?? '') ?></dd></div>
    <div class="sm:col-span-2"><dt class="font-semibold text-gray-500">Nama Lembaga</dt><dd class="mt-0.5"><?= sanitize($row['asal_lembaga'] ?? '') ?></dd></div>
    <div><dt class="font-semibold text-gray-500">Jenis Lembaga</dt><dd class="mt-0.5"><span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs font-semibold"><?= sanitize($row['jenis_lembaga'] ?? parseJenisLembaga($row['asal_lembaga'] ?? '')) ?></span></dd></div>
    <div><dt class="font-semibold text-gray-500">Transportasi</dt><dd class="mt-0.5"><?= sanitize($row['alat_transportasi'] ?? '') ?></dd></div>
    <div class="sm:col-span-2 border-t border-gray-100 pt-4"><dt class="font-semibold text-gray-500">Alamat Detail</dt><dd class="mt-0.5"><?= sanitize($row['alamat_detail'] ?? $row['alamat_lembaga'] ?? '') ?></dd></div>
    <div><dt class="font-semibold text-gray-500">Kelurahan/Desa</dt><dd class="mt-0.5"><?= sanitize($row['nama_kelurahan'] ?? '-') ?></dd></div>
    <div><dt class="font-semibold text-gray-500">Kecamatan</dt><dd class="mt-0.5 font-medium"><?= sanitize($row['nama_kecamatan'] ?? '-') ?></dd></div>
    <div><dt class="font-semibold text-gray-500">Kabupaten/Kota</dt><dd class="mt-0.5 font-medium"><?= sanitize($row['nama_kabupaten'] ?? '-') ?></dd></div>
    <div><dt class="font-semibold text-gray-500">Provinsi</dt><dd class="mt-0.5"><?= sanitize($row['nama_provinsi'] ?? '-') ?></dd></div>
  </dl>
</div>
