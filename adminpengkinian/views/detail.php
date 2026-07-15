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
        <dt class="font-semibold text-gray-500">Tempat, Tgl Lahir</dt>
        <dd class="mt-0.5"><?= sanitize($row['tempat_lahir'] ?? '') ?>, <?= sanitize($row['tanggal_lahir'] ?? '') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">NIY/NIP</dt>
        <dd class="mt-0.5"><?= sanitize($row['niy_nip'] ?? '') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Jabatan</dt>
        <dd class="mt-0.5"><?= sanitize($row['jabatan'] ?? '') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Jenjang</dt>
        <dd class="mt-0.5"><?= sanitize($row['jenjang'] ?? '') ?></dd>
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

      <div class="sm:col-span-2 pt-2 border-t border-gray-100">
        <h3 class="font-bold text-green-800 mb-3">Data SK Kepala Terakhir</h3>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Tgl TMT SK</dt>
        <dd class="mt-0.5"><?= sanitize($row['tgl_tmt_sk'] ?? '-') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Tgl Akhir TMT SK</dt>
        <dd class="mt-0.5"><?= sanitize($row['tgl_akhir_tmt_sk'] ?? '-') ?></dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Status SK Kepala</dt>
        <dd class="mt-0.5">
          <?php $status = strtoupper((string) ($row['status_sk_kepala'] ?? '')); ?>
          <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold <?= $status === 'AKTIF' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
            <?= sanitize($status !== '' ? $status : '-') ?>
          </span>
        </dd>
      </div>
      <div>
        <dt class="font-semibold text-gray-500">Scan SK Terakhir</dt>
        <dd class="mt-0.5">
          <?php if (!empty($row['file_sk_kepala'])): ?>
            <a href="<?= url('adminpengkinian/?download_sk=' . (int) ($row['id'] ?? 0)) ?>"
               class="text-green-700 hover:underline font-medium" target="_blank" rel="noopener">Lihat / Unduh File SK</a>
          <?php else: ?>
            -
          <?php endif; ?>
        </dd>
      </div>
    </dl>
  </div>
</div>
