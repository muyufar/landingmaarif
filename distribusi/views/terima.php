<?php
declare(strict_types=1);
/** @var array $deliveryRows */
$selectedNpsn = trim($_GET['npsn'] ?? '');
$selectedSatuan = $selectedNpsn !== '' ? getSatuanByNpsn($selectedNpsn) : null;
$activePengiriman = $selectedSatuan ? getActivePengiriman((int) $selectedSatuan['id']) : null;
$totalTerima = $selectedSatuan ? getTotalTerimaSatuan((int) $selectedSatuan['id']) : array_fill(1, 6, 0);
$kurang = $selectedSatuan ? satuanKurangDetail($selectedSatuan, $totalTerima) : [];
?>
<div class="space-y-6">
  <div class="bg-white rounded-2xl border border-green-100 shadow-lg p-6">
    <h2 class="text-xl font-bold text-green-800 mb-2">Terima Buku — Delivery → Receive / Done</h2>
    <p class="text-sm text-gray-600">Pilih satuan yang statusnya Delivery, upload surat jalan, dan isi jumlah buku diterima per kelas.</p>

    <form method="get" class="mt-4 flex gap-2">
      <input type="hidden" name="page" value="terima">
      <select name="npsn" class="flex-1 rounded-lg border px-3 py-2 text-sm" onchange="this.form.submit()">
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
    <h3 class="font-bold text-lg mb-1"><?= sanitize($selectedSatuan['nama_lembaga'] ?? '') ?></h3>
    <p class="text-sm text-gray-600 mb-4">NPSN: <?= sanitize($selectedSatuan['npsn'] ?? '') ?> · <?= sanitize($selectedSatuan['alamat'] ?? '') ?></p>

    <?php if ($kurang !== []): ?>
      <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-900">
        <strong>Masih kurang dari pengiriman sebelumnya:</strong>
        <?php foreach ($kurang as $k => $v): ?>Kelas <?= $k ?>: <?= $v ?> buku · <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="overflow-x-auto mb-6">
      <table class="w-full text-sm border">
        <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Kelas</th><th class="px-3 py-2">Kebutuhan</th><th class="px-3 py-2">Sudah Terima</th><th class="px-3 py-2">Kurang</th></tr></thead>
        <tbody>
          <?php for ($i = 1; $i <= 6; $i++):
            $need = (int) ($selectedSatuan['kebutuhan_kelas_' . $i] ?? 0);
            $got = $totalTerima[$i] ?? 0;
          ?>
            <tr class="border-t"><td class="px-3 py-2">Kelas <?= $i ?></td><td class="px-3 py-2 text-center"><?= $need ?></td><td class="px-3 py-2 text-center"><?= $got ?></td><td class="px-3 py-2 text-center font-semibold text-amber-700"><?= max(0, $need - $got) ?></td></tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>

    <form method="post" enctype="multipart/form-data" class="space-y-5">
      <input type="hidden" name="receive_pengiriman_id" value="<?= (int) $activePengiriman['id'] ?>">

      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <?php for ($i = 1; $i <= 6; $i++): ?>
          <div>
            <label class="block text-xs font-semibold mb-1">Terima Kelas <?= $i ?></label>
            <input type="number" name="terima_kelas_<?= $i ?>" min="0" value="0" required
                   class="w-full rounded-lg border px-3 py-2 text-sm">
          </div>
        <?php endfor; ?>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-2">Upload Surat Jalan Distributor <span class="text-red-500">*</span></label>
          <input type="file" name="file_surat_jalan_distributor" accept=".pdf,.jpg,.jpeg,.png" required
                 class="w-full rounded-lg border px-3 py-2 text-sm bg-white">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-2">Upload Surat Jalan Sekolah (Stempel) <span class="text-red-500">*</span></label>
          <input type="file" name="file_surat_jalan_sekolah" accept=".pdf,.jpg,.jpeg,.png" required
                 class="w-full rounded-lg border px-3 py-2 text-sm bg-white">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Catatan (opsional)</label>
        <textarea name="catatan" rows="2" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Contoh: Kelas 3 kurang 5 buku"></textarea>
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
