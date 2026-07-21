<?php
declare(strict_types=1);
/** @var array|null $satuan @var bool $isEdit */
$isEdit = $isEdit ?? false;
$satuan = $satuan ?? [];
$formAction = $isEdit
    ? url('admindistribusi/?page=edit&id=' . (int) ($satuan['id'] ?? 0))
    : url('admindistribusi/?page=create');
$totalPreview = $satuan !== [] ? distribusiHitungTotalBukuSatuan($satuan) : 0;
?>
<div class="mb-4">
  <a href="<?= url('admindistribusi/?page=list') ?>" class="text-green-700 text-sm hover:underline">&larr; Kembali ke Monitoring</a>
</div>

<div class="bg-white rounded-2xl border shadow-lg p-6">
  <h2 class="text-xl font-bold text-green-800 mb-1"><?= $isEdit ? 'Edit Satuan Pendidikan' : 'Tambah Satuan Pendidikan' ?></h2>
  <p class="text-sm text-gray-600 mb-6">
    <?= $isEdit ? 'Perbarui data kebutuhan buku LKPD satuan ini.' : 'Input manual satuan baru selain import Excel.' ?>
  </p>

  <form method="post" class="space-y-6">
    <input type="hidden" name="save_satuan" value="1">
    <?php if ($isEdit): ?>
      <input type="hidden" name="satuan_id" value="<?= (int) ($satuan['id'] ?? 0) ?>">
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold mb-1">NPSN *</label>
        <input type="text" name="npsn" required maxlength="20"
               value="<?= sanitize((string) ($satuan['npsn'] ?? '')) ?>"
               class="w-full rounded-lg border px-3 py-2 text-sm font-mono"
               placeholder="60711234">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Status</label>
        <select name="status" class="w-full rounded-lg border px-3 py-2 text-sm">
          <?php foreach ([DIST_STATUS_PACKING, DIST_STATUS_DELIVERY, DIST_STATUS_RECEIVE, DIST_STATUS_DONE] as $st): ?>
            <option value="<?= sanitize($st) ?>" <?= ($satuan['status'] ?? DIST_STATUS_PACKING) === $st ? 'selected' : '' ?>>
              <?= sanitize(distribusiStatusLabel($st)) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-500 mt-1">Hati-hati mengubah status manual jika ada proses pengiriman aktif.</p>
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1">Nama Lembaga *</label>
      <input type="text" name="nama_lembaga" required maxlength="200"
             value="<?= sanitize((string) ($satuan['nama_lembaga'] ?? '')) ?>"
             class="w-full rounded-lg border px-3 py-2 text-sm">
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1">Alamat</label>
      <textarea name="alamat" rows="2" class="w-full rounded-lg border px-3 py-2 text-sm"
                placeholder="Kec. ..., Kabupaten Magelang, Jawa Tengah"><?= sanitize((string) ($satuan['alamat'] ?? '')) ?></textarea>
    </div>

    <div>
      <h3 class="font-semibold text-gray-900 mb-2">Jumlah Siswa per Kelas (LKPD)</h3>
      <p class="text-xs text-gray-500 mb-3">Angka per kelas untuk kolom Jumlah di surat jalan mapel.</p>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <?php for ($i = 1; $i <= 6; $i++): ?>
          <div>
            <label class="block text-xs font-semibold mb-1">Kelas <?= $i ?></label>
            <input type="number" name="kebutuhan_kelas_<?= $i ?>" min="0" value="<?= (int) ($satuan['kebutuhan_kelas_' . $i] ?? 0) ?>"
                   class="w-full rounded-lg border px-3 py-2 text-sm">
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <div>
      <h3 class="font-semibold text-gray-900 mb-2">Buku Guru per Kelas</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <?php for ($i = 1; $i <= 6; $i++): ?>
          <div>
            <label class="block text-xs font-semibold mb-1">Guru K<?= $i ?></label>
            <input type="number" name="kebutuhan_guru_kelas_<?= $i ?>" min="0" value="<?= (int) ($satuan['kebutuhan_guru_kelas_' . $i] ?? 0) ?>"
                   class="w-full rounded-lg border px-3 py-2 text-sm">
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <?php if ($isEdit): ?>
      <div class="rounded-lg bg-green-50 border border-green-100 px-4 py-3 text-sm">
        <span class="text-gray-600">Total buku (dihitung): </span>
        <strong class="text-green-800"><?= number_format($totalPreview, 0, ',', '.') ?></strong>
        <?php if ((int) ($satuan['total_buku'] ?? 0) > 0 && (int) $satuan['total_buku'] !== $totalPreview): ?>
          <span class="text-xs text-amber-700 ml-2">(DB lama: <?= number_format((int) $satuan['total_buku'], 0, ',', '.') ?> — akan disinkronkan saat simpan)</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="flex flex-wrap gap-3 pt-2">
      <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-2.5 rounded-lg text-sm">
        <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Satuan Baru' ?>
      </button>
      <a href="<?= url('admindistribusi/?page=list') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2.5 rounded-lg text-sm">Batal</a>
      <?php if ($isEdit): ?>
        <a href="<?= url('admindistribusi/?page=detail&id=' . (int) ($satuan['id'] ?? 0)) ?>"
           class="text-green-700 hover:underline text-sm self-center ml-auto">Lihat detail & riwayat</a>
      <?php endif; ?>
    </div>
  </form>
</div>
