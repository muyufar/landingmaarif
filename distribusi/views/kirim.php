<?php declare(strict_types=1);
/** @var array $kirimRows @var string $search @var string $kecamatanFilter @var array $kecamatanOptions */
?>
<div class="space-y-6">
  <div class="bg-white rounded-2xl border border-green-100 shadow-lg overflow-hidden">
    <div class="p-5 border-b border-green-100">
      <h2 class="text-xl font-bold text-green-800 mb-1">Kirim Buku — Packing → Delivery</h2>
      <p class="text-sm text-gray-600">
        Pilih satuan pendidikan dari daftar. Unduh <strong>Surat Jalan Excel</strong> sebelum berangkat,
        lalu klik <strong>Kirim Buku</strong> untuk mencatat pengiriman dan melanjutkan upload surat jalan.
      </p>
    </div>

    <div class="p-5 border-b bg-gray-50">
      <form method="get" class="flex flex-wrap gap-2">
        <input type="hidden" name="page" value="kirim">
        <input type="text" name="q" value="<?= sanitize($search) ?>"
               placeholder="Cari NPSN, nama lembaga, alamat..."
               class="flex-1 min-w-[200px] rounded-lg border px-3 py-2 text-sm">
        <select name="kecamatan" class="rounded-lg border px-3 py-2 text-sm min-w-[160px]">
          <option value="">Semua Kecamatan</option>
          <?php foreach ($kecamatanOptions as $opt): ?>
            <option value="<?= sanitize($opt) ?>" <?= $kecamatanFilter === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
        <?php if ($search !== '' || $kecamatanFilter !== ''): ?>
          <a href="<?= url('distribusi/?page=kirim') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm">Reset</a>
        <?php endif; ?>
      </form>
      <p class="text-xs text-gray-500 mt-3">
        Menampilkan <strong><?= count($kirimRows) ?></strong> satuan siap dikirim (status Packing atau Receive/kurang).
      </p>
    </div>

    <?php if (empty($kirimRows)): ?>
      <div class="p-8 text-center text-gray-500 text-sm">
        <?php if ($search !== '' || $kecamatanFilter !== ''): ?>
          Tidak ada satuan yang cocok dengan filter. Coba ubah kata kunci atau reset filter.
        <?php else: ?>
          Semua satuan sedang dalam proses Delivery atau sudah selesai (Done).
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-green-50 text-xs uppercase">
            <tr>
              <th class="px-4 py-3 text-left">NPSN</th>
              <th class="px-4 py-3 text-left">Lembaga</th>
              <th class="px-4 py-3 text-left">Kecamatan</th>
              <th class="px-4 py-3 text-center">K1–K6</th>
              <th class="px-4 py-3 text-center">Total Buku</th>
              <th class="px-4 py-3 text-center">Status</th>
              <th class="px-4 py-3 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <?php foreach ($kirimRows as $row): ?>
              <?php
                $nama = (string) ($row['nama_lembaga'] ?? '');
                $kec = distribusiKecamatanFromAlamat((string) ($row['alamat'] ?? ''));
                $totalBuku = satuanTotalKebutuhanBuku($row);
                $satuanId = (int) ($row['id'] ?? 0);
              ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs whitespace-nowrap"><?= sanitize($row['npsn'] ?? '') ?></td>
                <td class="px-4 py-3">
                  <p class="font-semibold"><?= sanitize($nama) ?></p>
                  <p class="text-xs text-gray-500 truncate max-w-xs"><?= sanitize($row['alamat'] ?? '') ?></p>
                </td>
                <td class="px-4 py-3 text-xs"><?= sanitize($kec !== '' ? $kec : '-') ?></td>
                <td class="px-4 py-3 text-xs text-center whitespace-nowrap">
                  <?php for ($i = 1; $i <= 6; $i++): ?><?= (int) ($row['kebutuhan_kelas_' . $i] ?? 0) ?><?= $i < 6 ? '/' : '' ?><?php endfor; ?>
                </td>
                <td class="px-4 py-3 text-center font-semibold"><?= number_format($totalBuku, 0, ',', '.') ?></td>
                <td class="px-4 py-3 text-center">
                  <span class="text-xs font-bold px-2 py-1 rounded-full <?= distribusiStatusBadgeClass($row['status'] ?? '') ?>">
                    <?= sanitize(distribusiStatusLabel($row['status'] ?? '')) ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="flex flex-col sm:flex-row gap-1 justify-center items-center">
                    <a href="<?= url('distribusi/?download_surat_jalan=1&satuan_id=' . $satuanId) ?>"
                       class="bg-white border border-green-700 text-green-800 text-xs font-semibold px-3 py-2 rounded-lg hover:bg-green-50 whitespace-nowrap">
                      Surat Jalan
                    </a>
                    <form method="post" class="inline"
                          onsubmit="return confirm(<?= json_encode('Catat pengiriman buku ke ' . $nama . '?', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);">
                      <input type="hidden" name="dispatch_satuan_id" value="<?= $satuanId ?>">
                      <input type="hidden" name="_q" value="<?= sanitize($search) ?>">
                      <input type="hidden" name="_kecamatan" value="<?= sanitize($kecamatanFilter) ?>">
                      <button type="submit"
                              class="bg-green-700 hover:bg-green-800 text-white text-xs font-semibold px-3 py-2 rounded-lg whitespace-nowrap">
                        Kirim Buku
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
