<?php

declare(strict_types=1);

/** @var array $peserta @var array $filterOptions @var array $filters @var string $search */

$exportQuery = http_build_query(array_filter([
    'export' => 'xls',
    'q' => $search,
    'kecamatan' => $filters['kecamatan'] ?? '',
    'jabatan' => $filters['jabatan'] ?? '',
    'transportasi' => $filters['transportasi'] ?? '',
    'jenis_lembaga' => $filters['jenis_lembaga'] ?? '',
], static fn (string $value): bool => $value !== ''));
?>
<style>
  @media (min-width: 1280px) {
    .peserta-row {
      display: grid;
        grid-template-columns: 2rem 8rem minmax(8rem, 1fr) 6rem 4rem 5rem minmax(6rem, 1fr) minmax(8rem, 1.2fr) 5rem 6rem 4rem 5.5rem;
      gap: 0.5rem 0.75rem;
      align-items: start;
    }
  }
</style>

<div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
  <div class="px-6 py-5 border-b border-gray-100">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
      <div>
        <h2 class="text-xl font-bold text-green-800">List Peserta</h2>
        <p class="text-sm text-gray-500 mt-1">Total: <strong><?= count($peserta) ?></strong> peserta</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="<?= url('pesertakerdinma/?page=form') ?>"
           class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium">+ Tambah Peserta</a>
        <a href="<?= url('pesertakerdinma/?' . $exportQuery) ?>"
           class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Export XLS</a>
      </div>
    </div>

    <form method="get" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
      <input type="hidden" name="page" value="list">
      <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Cari nama, NIP, lembaga..."
             class="rounded-lg border border-gray-300 px-3 py-2 text-sm xl:col-span-2 focus:ring-2 focus:ring-green-600">
      <select name="kecamatan" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">Semua Kecamatan</option>
        <?php foreach ($filterOptions['kecamatan'] as $opt): ?>
          <option value="<?= sanitize($opt) ?>" <?= ($filters['kecamatan'] ?? '') === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="jabatan" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">Semua Jabatan</option>
        <?php foreach ($filterOptions['jabatan'] as $opt): ?>
          <option value="<?= sanitize($opt) ?>" <?= ($filters['jabatan'] ?? '') === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="transportasi" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">Semua Transport</option>
        <?php foreach ($filterOptions['transportasi'] as $opt): ?>
          <option value="<?= sanitize($opt) ?>" <?= ($filters['transportasi'] ?? '') === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="jenis_lembaga" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">Semua Jenis Lembaga</option>
        <?php foreach ($filterOptions['jenis_lembaga'] as $opt): ?>
          <option value="<?= sanitize($opt) ?>" <?= ($filters['jenis_lembaga'] ?? '') === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="flex gap-2 sm:col-span-2 xl:col-span-6">
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
        <a href="<?= url('pesertakerdinma/?page=list') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium">Reset</a>
      </div>
    </form>
  </div>

  <?php if (empty($peserta)): ?>
    <div class="px-6 py-16 text-center text-gray-500">Tidak ada data peserta.</div>
  <?php else: ?>
    <div class="hidden xl:grid peserta-row px-4 py-3 bg-green-50 text-green-900 text-[11px] font-semibold uppercase tracking-wide border-b border-green-100">
      <div>No</div><div>Tgl</div><div>Nama / WA</div><div>TTL</div><div>Jenis</div><div>Jabatan</div>
      <div>Lembaga</div><div>Alamat</div><div>Kec.</div><div>Kab.</div><div>Transp.</div><div class="text-center">Aksi</div>
    </div>
    <div class="divide-y divide-gray-100">
      <?php foreach ($peserta as $index => $row): ?>
        <article class="px-4 py-4 hover:bg-gray-50">
          <div class="hidden xl:grid peserta-row text-xs text-gray-700">
            <div class="text-gray-500"><?= $index + 1 ?></div>
            <div class="text-gray-600"><?= sanitize($row['created_at'] ?? '-') ?></div>
            <div class="min-w-0">
              <p class="font-semibold break-words"><?= sanitize($row['nama'] ?? '') ?></p>
              <p class="text-[11px] text-green-700"><?= sanitize($row['nomor_wa'] ?? '') ?></p>
            </div>
                  <div><?= sanitize($row['tempat_lahir'] ?? '') ?><br><?= sanitize($row['tanggal_lahir'] ?? '') ?></div>
                  <div><span class="bg-green-100 text-green-800 px-1.5 py-0.5 rounded font-semibold"><?= sanitize($row['jenis_lembaga'] ?? parseJenisLembaga($row['asal_lembaga'] ?? '')) ?></span></div>
                  <div class="break-words"><?= sanitize($row['jabatan'] ?? '') ?></div>
            <div class="break-words"><?= sanitize($row['asal_lembaga'] ?? '') ?></div>
            <div class="break-words"><?= sanitize($row['alamat_detail'] ?? $row['alamat_lembaga'] ?? '') ?></div>
            <div class="font-medium"><?= sanitize($row['nama_kecamatan'] ?? '-') ?></div>
            <div class="font-medium"><?= sanitize($row['nama_kabupaten'] ?? '-') ?></div>
            <div><?= sanitize($row['alat_transportasi'] ?? '') ?></div>
            <div>
              <?php require __DIR__ . '/../_action_buttons.php'; ?>
            </div>
          </div>
          <div class="xl:hidden space-y-2 text-sm">
            <div class="flex justify-between gap-3">
              <div>
                <p class="font-semibold"><?= sanitize($row['nama'] ?? '') ?></p>
                <p class="text-xs text-gray-500"><?= sanitize($row['asal_lembaga'] ?? '') ?></p>
              </div>
              <div class="shrink-0">
                <?php require __DIR__ . '/../_action_buttons.php'; ?>
              </div>
            </div>
            <p class="text-xs text-gray-600"><?= sanitize($row['nama_kecamatan'] ?? '-') ?> · <?= sanitize($row['nama_kabupaten'] ?? '-') ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
