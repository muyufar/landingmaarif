<?php
declare(strict_types=1);
/** @var array $deliveryRows @var array $waLinks @var bool $isNewDispatch @var int $selectedPid */
$selectedNpsn = trim($_GET['npsn'] ?? '');
$selectedSatuan = null;
$activePengiriman = null;

if ($selectedPid > 0) {
    $activePengiriman = getPengirimanById($selectedPid);
    if ($activePengiriman !== null) {
        $selectedSatuan = getSatuanById((int) ($activePengiriman['satuan_id'] ?? 0));
        $selectedNpsn = (string) ($selectedSatuan['npsn'] ?? '');
    }
} elseif ($selectedNpsn !== '') {
    $selectedSatuan = getSatuanByNpsn($selectedNpsn);
    if ($selectedSatuan !== null) {
        $activePengiriman = getActivePengiriman((int) $selectedSatuan['id']);
        if ($activePengiriman !== null) {
            $selectedPid = (int) ($activePengiriman['id'] ?? 0);
        }
    }
}

$totalTerima = $selectedSatuan ? getTotalTerimaSatuan((int) $selectedSatuan['id']) : array_fill(1, 6, 0);
$totalTerimaGuru = $selectedSatuan ? getTotalTerimaGuruSatuan((int) $selectedSatuan['id']) : array_fill(1, 6, 0);
$kurang = $selectedSatuan ? satuanKurangDetail($selectedSatuan, $totalTerima, $totalTerimaGuru) : ['siswa' => [], 'guru' => []];
$hasGuruNeed = false;
if ($selectedSatuan) {
    for ($i = 1; $i <= 6; $i++) {
        if ((int) ($selectedSatuan['kebutuhan_guru_kelas_' . $i] ?? 0) > 0) {
            $hasGuruNeed = true;
            break;
        }
    }
}
?>
<div class="space-y-6">
  <?php if ($isNewDispatch): ?>
    <div class="rounded-xl bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
      <p class="font-semibold">Pengiriman berhasil dicatat!</p>
      <p class="mt-1">Unduh surat jalan, bawa saat mengirim buku, lalu upload foto surat jalan yang sudah ditandatangani di bawah.</p>
      <?php if (!empty($waLinks)): ?>
        <p class="mt-2">Notifikasi WA ke:</p>
        <ul class="mt-2 space-y-1">
          <?php foreach ($waLinks as $wa): ?>
            <li>
              <a href="<?= sanitize($wa['wa_link'] ?? '#') ?>" target="_blank" rel="noopener"
                 class="text-green-700 underline font-medium"><?= sanitize($wa['nomor'] ?? '') ?> — Buka WhatsApp</a>
              <?php if (!empty($wa['sent'])): ?><span class="text-xs text-gray-500"> (terkirim otomatis)</span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="mt-2 text-amber-700">Data HP operator/kepsek belum ada di pengkinian data.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl border border-green-100 shadow-lg p-6">
    <h2 class="text-xl font-bold text-green-800 mb-2">Terima Buku — Delivery → Receive / Done</h2>
    <p class="text-sm text-gray-600">Pilih satuan yang statusnya Delivery, upload surat jalan (kamera/galeri), dan isi jumlah buku diterima per kelas (LKPD siswa + buku guru).</p>

    <form method="get" class="mt-4 flex flex-wrap gap-2">
      <input type="hidden" name="page" value="terima">
      <select name="npsn" class="flex-1 min-w-[240px] rounded-lg border px-3 py-2 text-sm" onchange="this.form.submit()">
        <option value="">-- Pilih satuan (Delivery) --</option>
        <?php foreach ($deliveryRows as $row): ?>
          <option value="<?= sanitize($row['npsn'] ?? '') ?>" <?= $selectedNpsn === ($row['npsn'] ?? '') ? 'selected' : '' ?>>
            <?= sanitize($row['npsn'] ?? '') ?> — <?= sanitize($row['nama_lembaga'] ?? '') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <?php if ($selectedSatuan && $activePengiriman): ?>
  <div class="bg-white rounded-2xl border border-blue-100 shadow-lg p-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
      <div>
        <h3 class="font-bold text-lg mb-1"><?= sanitize($selectedSatuan['nama_lembaga'] ?? '') ?></h3>
        <p class="text-sm text-gray-600">NPSN: <?= sanitize($selectedSatuan['npsn'] ?? '') ?> · <?= sanitize($selectedSatuan['alamat'] ?? '') ?></p>
      </div>
      <a href="<?= url('distribusi/?download_surat_jalan=1&pengiriman_id=' . (int) $selectedPid) ?>"
         class="inline-flex items-center justify-center bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg whitespace-nowrap">
        Unduh Surat Jalan Excel
      </a>
    </div>

    <?php if (!empty($kurang['siswa']) || !empty($kurang['guru'])): ?>
      <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-900">
        <strong>Masih kurang dari pengiriman sebelumnya:</strong>
        <?php foreach ($kurang['siswa'] as $k => $v): ?>LKPD Kelas <?= $k ?>: <?= $v ?> · <?php endforeach; ?>
        <?php foreach ($kurang['guru'] as $k => $v): ?>Buku Guru K<?= $k ?>: <?= $v ?> · <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="overflow-x-auto mb-6">
      <table class="w-full text-sm border">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left">Kelas</th>
            <th class="px-3 py-2 text-left">Jenis</th>
            <th class="px-3 py-2">Kebutuhan</th>
            <th class="px-3 py-2">Sudah Terima</th>
            <th class="px-3 py-2">Kurang</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($i = 1; $i <= 6; $i++):
            $need = (int) ($selectedSatuan['kebutuhan_kelas_' . $i] ?? 0);
            $got = $totalTerima[$i] ?? 0;
            $needGuru = (int) ($selectedSatuan['kebutuhan_guru_kelas_' . $i] ?? 0);
            $gotGuru = $totalTerimaGuru[$i] ?? 0;
          ?>
            <tr class="border-t">
              <td class="px-3 py-2" rowspan="<?= $needGuru > 0 ? 2 : 1 ?>">Kelas <?= $i ?></td>
              <td class="px-3 py-2 text-gray-600">LKPD Siswa</td>
              <td class="px-3 py-2 text-center"><?= $need ?></td>
              <td class="px-3 py-2 text-center"><?= $got ?></td>
              <td class="px-3 py-2 text-center font-semibold text-amber-700"><?= max(0, $need - $got) ?></td>
            </tr>
            <?php if ($needGuru > 0): ?>
            <tr class="border-t bg-gray-50/50">
              <td class="px-3 py-2 text-gray-600">Buku Guru</td>
              <td class="px-3 py-2 text-center"><?= $needGuru ?></td>
              <td class="px-3 py-2 text-center"><?= $gotGuru ?></td>
              <td class="px-3 py-2 text-center font-semibold text-amber-700"><?= max(0, $needGuru - $gotGuru) ?></td>
            </tr>
            <?php endif; ?>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>

    <form method="post" enctype="multipart/form-data" class="space-y-5" id="form-terima-sj">
      <input type="hidden" name="receive_pengiriman_id" value="<?= (int) $activePengiriman['id'] ?>">

      <div class="rounded-xl border border-green-200 bg-green-50 p-4">
        <h4 class="font-semibold text-green-900 mb-2">Upload Surat Jalan Distributor</h4>
        <p class="text-xs text-gray-600 mb-3">Foto surat jalan via kamera atau galeri. Sistem akan mencoba membaca jumlah buku per kelas (LKPD + buku guru) secara otomatis.</p>
        <input type="file" id="file_surat_jalan_distributor" name="file_surat_jalan_distributor"
               accept="image/*,.pdf,application/pdf" capture="environment" required
               class="w-full rounded-lg border px-3 py-2 text-sm bg-white file:mr-3 file:py-2 file:px-3 file:rounded file:border-0 file:bg-green-700 file:text-white">
        <div class="flex flex-wrap gap-2 mt-2">
          <button type="button" id="btn-baca-ulang-ocr"
                  class="text-xs bg-white border border-green-700 text-green-800 px-3 py-1.5 rounded-lg hover:bg-green-100">
            Baca ulang dari foto
          </button>
        </div>
        <p id="ocr-status" class="text-xs text-gray-500 mt-2">Upload foto surat jalan untuk isi otomatis, atau isi manual di bawah.</p>
      </div>

      <div class="rounded-xl border p-4">
        <h4 class="font-semibold text-gray-900 mb-2">Upload Surat Jalan Sekolah (TTD & Cap)</h4>
        <input type="file" name="file_surat_jalan_sekolah" accept="image/*,.pdf,application/pdf" capture="environment" required
               class="w-full rounded-lg border px-3 py-2 text-sm bg-white file:mr-3 file:py-2 file:px-3 file:rounded file:border-0 file:bg-blue-700 file:text-white">
      </div>

      <div>
        <div class="flex items-center justify-between mb-2">
          <h4 class="font-semibold text-gray-900">Jumlah LKPD Siswa Diterima per Kelas</h4>
          <span class="text-xs text-gray-500">Isi manual jika OCR tidak akurat</span>
        </div>
        <p class="text-xs text-gray-500 mb-3">Angka per kelas sesuai kolom Jumlah di surat jalan (bukan total buku).</p>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <?php for ($i = 1; $i <= 6; $i++):
            $need = (int) ($selectedSatuan['kebutuhan_kelas_' . $i] ?? 0);
          ?>
            <div>
              <label class="block text-xs font-semibold mb-1">Terima Kelas <?= $i ?> <span class="text-gray-400 font-normal">(butuh <?= $need ?>)</span></label>
              <input type="number" name="terima_kelas_<?= $i ?>" min="0" value="0" required
                     class="w-full rounded-lg border px-3 py-2 text-sm terima-kelas-input">
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <?php if ($hasGuruNeed): ?>
      <div class="rounded-xl border border-purple-100 bg-purple-50/40 p-4">
        <div class="flex items-center justify-between mb-2">
          <h4 class="font-semibold text-gray-900">Buku Guru Diterima per Kelas</h4>
          <span class="text-xs text-gray-500">Baris BUKU GURU di surat jalan</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <?php for ($i = 1; $i <= 6; $i++):
            $needGuru = (int) ($selectedSatuan['kebutuhan_guru_kelas_' . $i] ?? 0);
            if ($needGuru <= 0) {
                continue;
            }
          ?>
            <div>
              <label class="block text-xs font-semibold mb-1">Guru Kelas <?= $i ?> <span class="text-gray-400 font-normal">(butuh <?= $needGuru ?>)</span></label>
              <input type="number" name="terima_guru_kelas_<?= $i ?>" min="0" max="<?= $needGuru ?>" value="0" required
                     class="w-full rounded-lg border px-3 py-2 text-sm terima-guru-input">
            </div>
          <?php endfor; ?>
        </div>
      </div>
      <?php else: ?>
        <?php for ($i = 1; $i <= 6; $i++): ?>
          <input type="hidden" name="terima_guru_kelas_<?= $i ?>" value="0">
        <?php endfor; ?>
      <?php endif; ?>

      <div>
        <label class="block text-sm font-semibold mb-2">Catatan (opsional)</label>
        <textarea name="catatan" rows="2" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Contoh: Kelas 3 kurang 5 buku, buku guru kelas 2 belum ada"></textarea>
      </div>

      <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 rounded-xl">
        Simpan Penerimaan
      </button>
    </form>
  </div>
  <?php elseif ($selectedNpsn !== ''): ?>
    <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-amber-900 text-sm">Tidak ada pengiriman aktif (Delivery) untuk NPSN ini.</div>
  <?php endif; ?>
</div>
