<?php

declare(strict_types=1);

/** @var array $settings @var array $paketList @var array|null $paketFormData @var array $paketFormErrors */
$editing = $paketFormData !== null && (int) ($paketFormData['id'] ?? 0) > 0;
$pf = $paketFormData ?? ['id' => 0, 'nama' => '', 'jumlah_kupon' => 0, 'jumlah_kaos' => 0, 'keterangan' => '', 'urutan' => count($paketList) + 1, 'aktif' => 1];
$inputClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-green-600';
?>
<div class="grid lg:grid-cols-5 gap-6">
  <div class="lg:col-span-2 space-y-6">
    <div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-100">
        <h2 class="text-lg font-bold text-green-800">Pengaturan Form</h2>
        <p class="text-xs text-gray-500 mt-1">Sesuaikan dengan isi surat edaran. Perubahan langsung tampil di form publik.</p>
      </div>
      <form method="post" class="px-6 py-5 space-y-4">
        <label class="flex items-start gap-3">
          <input type="checkbox" name="pendaftaran_dibuka" value="1" class="mt-1 h-4 w-4 text-green-700"
                 <?= $settings['pendaftaran_dibuka'] === '1' ? 'checked' : '' ?>>
          <span>
            <span class="block text-sm font-semibold text-gray-700">Pendaftaran dibuka</span>
            <span class="block text-xs text-gray-500">Hilangkan centang untuk menutup form publik.</span>
          </span>
        </label>

        <label class="flex items-start gap-3">
          <input type="checkbox" name="izinkan_jumlah_bebas" value="1" class="mt-1 h-4 w-4 text-green-700"
                 <?= $settings['izinkan_jumlah_bebas'] === '1' ? 'checked' : '' ?>>
          <span>
            <span class="block text-sm font-semibold text-gray-700">Tampilkan opsi "Isi jumlah sendiri"</span>
            <span class="block text-xs text-gray-500">Jika dimatikan, madrasah wajib memilih salah satu paket. Bila belum ada paket aktif, isian jumlah tetap ditampilkan.</span>
          </span>
        </label>

        <div class="rounded-lg border border-green-200 bg-green-50/50 px-4 py-3 text-xs text-gray-700 mb-2">
          Tarif resmi <strong>SE Revisi 0629/LPM/X/2026</strong>: kupon/pendaftaran Rp 5.000;
          kaos pendek Rp 80.000; kaos panjang Rp 90.000 (sudah termasuk pendaftaran + 2 kupon/kaos).
          <a href="<?= sanitize(url('data/jalan_sehat/SE_Revisi_Jalan_Santai_HSN_2026.pdf')) ?>" target="_blank" rel="noopener" class="text-green-800 font-semibold underline">Buka PDF</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="harga_kupon" class="block text-sm font-semibold text-gray-700 mb-1">Kupon / pendaftaran saja (Rp)</label>
            <input type="number" min="0" step="1" id="harga_kupon" name="harga_kupon" value="<?= (int) $settings['harga_kupon'] ?>" class="<?= $inputClass ?>">
          </div>
          <div>
            <label for="harga_kaos_pendek" class="block text-sm font-semibold text-gray-700 mb-1">Kaos lengan pendek / pcs (Rp)</label>
            <input type="number" min="0" step="1" id="harga_kaos_pendek" name="harga_kaos_pendek"
                   value="<?= (int) ($settings['harga_kaos_pendek'] ?? jalanSehatHargaKaosPendek($settings)) ?>" class="<?= $inputClass ?>">
          </div>
          <div>
            <label for="harga_kaos_panjang" class="block text-sm font-semibold text-gray-700 mb-1">Kaos lengan panjang / pcs (Rp)</label>
            <input type="number" min="0" step="1" id="harga_kaos_panjang" name="harga_kaos_panjang"
                   value="<?= (int) ($settings['harga_kaos_panjang'] ?? jalanSehatHargaKaosPanjang($settings)) ?>" class="<?= $inputClass ?>">
          </div>
          <div>
            <label for="harga_kaos" class="block text-sm font-semibold text-gray-700 mb-1">Harga kaos lama (opsional)</label>
            <input type="number" min="0" step="1" id="harga_kaos" name="harga_kaos" value="<?= (int) $settings['harga_kaos'] ?>" class="<?= $inputClass ?>">
            <p class="text-xs text-gray-500 mt-1">Cadangan jika pendek/panjang kosong.</p>
          </div>
        </div>
        <p class="text-sm">
          <button type="submit" name="action" value="sync_tarif_se" formnovalidate
                  class="font-semibold text-green-800 underline hover:text-green-900">
            Terapkan otomatis tarif dari surat edaran (5.000 / 80.000 / 90.000)
          </button>
        </p>

        <label class="flex items-start gap-3 rounded-lg border border-green-100 bg-green-50/60 px-4 py-3">
          <input type="checkbox" name="kupon_mengikuti_kaos" value="1" class="mt-1 h-4 w-4 text-green-700"
                 <?= ($settings['kupon_mengikuti_kaos'] ?? '1') === '1' ? 'checked' : '' ?>>
          <span>
            <span class="block text-sm font-semibold text-gray-800">Kupon mengikuti kaos (jumlah sendiri)</span>
            <span class="block text-xs text-gray-600 mt-0.5">Sesuai surat edaran: bonus kupon per kaos. Form tidak mengunci field kupon (siswa bisa pesan kupon saja). Jika kaos diisi dan kupon masih 0, sistem mengisi bonus saat simpan. Paket tetap memakai jumlah kupon/kaos masing-masing paket.</span>
          </span>
        </label>

        <div>
          <label for="kupon_per_kaos" class="block text-sm font-semibold text-gray-700 mb-1">Kupon door prize per 1 kaos</label>
          <input type="number" min="1" max="99" step="1" id="kupon_per_kaos" name="kupon_per_kaos"
                 value="<?= jalanSehatKuponPerKaos($settings) ?>"
                 class="<?= $inputClass ?> max-w-[8rem]">
          <p class="text-xs text-gray-500 mt-1">Contoh surat edaran: <strong>1 kaos = 2 kupon</strong>. Hanya dipakai untuk opsi &quot;jumlah sendiri&quot; bila centang di atas aktif.</p>
        </div>

        <div>
          <label for="info_surat_edaran" class="block text-sm font-semibold text-gray-700 mb-1">Ketentuan / kutipan surat edaran</label>
          <textarea id="info_surat_edaran" name="info_surat_edaran" rows="5" class="<?= $inputClass ?>"
                    placeholder="Contoh: Sesuai SE No. .../PCNU/X/2026, setiap MI wajib memesan minimal ... kupon door prize. Pembayaran paling lambat tanggal ..."><?= sanitize($settings['info_surat_edaran']) ?></textarea>
          <p class="text-xs text-gray-500 mt-1">Tampil di atas isian Jumlah Pemesanan pada form publik.</p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-4 space-y-2">
          <label class="flex items-start gap-3">
            <input type="checkbox" name="monitor_kaos_aktif" value="1" class="mt-1 h-4 w-4 text-green-700"
                   <?= ($settings['monitor_kaos_aktif'] ?? '0') === '1' ? 'checked' : '' ?>>
            <span>
              <span class="block text-sm font-semibold text-gray-800">Halaman monitor kaos untuk konveksi</span>
              <span class="block text-xs text-gray-600 mt-0.5">Rekap ukuran lengan pendek &amp; panjang (tanpa nomor HP/WA). Siapa pun dengan link bisa membuka — bagikan URL ke pencetak kaos.</span>
            </span>
          </label>
          <?php $monitorUrl = absoluteUrl('jalansehat/monitor'); ?>
          <p class="text-xs text-gray-600 break-all pl-7">
            URL: <a href="<?= sanitize($monitorUrl) ?>" target="_blank" rel="noopener" class="text-green-800 underline font-medium"><?= sanitize($monitorUrl) ?></a>
          </p>
        </div>

        <button type="submit" name="action" value="save_pengaturan" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold px-4 py-2.5 rounded-lg text-sm">Simpan Pengaturan</button>
      </form>
    </div>
  </div>

  <div class="lg:col-span-3 space-y-6">
    <div class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-100">
        <h2 class="text-lg font-bold text-green-800">Paket Pemesanan (Alternatif Surat Edaran)</h2>
        <p class="text-xs text-gray-500 mt-1">Paket aktif tampil sebagai pilihan di form publik. Jumlah kupon & kaos otomatis terisi saat madrasah memilih paket.</p>
      </div>

      <?php if (empty($paketList)): ?>
        <div class="px-6 py-10 text-center text-gray-500 text-sm">Belum ada paket. Tambahkan paket di bawah sesuai surat edaran.</div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-green-50 text-green-900 text-xs uppercase">
              <tr>
                <th class="px-4 py-3 text-left">Urutan</th>
                <th class="px-4 py-3 text-left">Paket</th>
                <th class="px-4 py-3 text-right">Kupon</th>
                <th class="px-4 py-3 text-right">Kaos</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($paketList as $paket): ?>
                <tr class="hover:bg-gray-50 align-top">
                  <td class="px-4 py-3 text-gray-500"><?= (int) $paket['urutan'] ?></td>
                  <td class="px-4 py-3">
                    <p class="font-semibold"><?= sanitize((string) $paket['nama']) ?></p>
                    <?php if (!empty($paket['keterangan'])): ?>
                      <p class="text-xs text-gray-500"><?= sanitize((string) $paket['keterangan']) ?></p>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-right font-semibold"><?= (int) $paket['jumlah_kupon'] ?></td>
                  <td class="px-4 py-3 text-right font-semibold"><?= (int) $paket['jumlah_kaos'] ?></td>
                  <td class="px-4 py-3 text-center">
                    <form method="post" class="inline">
                      <input type="hidden" name="action" value="toggle_paket">
                      <input type="hidden" name="paket_id" value="<?= (int) $paket['id'] ?>">
                      <button type="submit" title="Klik untuk mengubah status"
                              class="text-xs font-bold px-2 py-1 rounded <?= (int) $paket['aktif'] === 1 ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' ?>">
                        <?= (int) $paket['aktif'] === 1 ? 'AKTIF' : 'NONAKTIF' ?>
                      </button>
                    </form>
                  </td>
                  <td class="px-4 py-3 text-center whitespace-nowrap">
                    <a href="<?= url('adminjalansehat/?page=pengaturan&edit_paket=' . (int) $paket['id']) ?>#form-paket"
                       class="text-green-700 hover:underline font-medium">Edit</a>
                    <form method="post" class="inline" onsubmit="return confirm('Hapus paket ini? Data pendaftar yang sudah memilih paket ini tetap tersimpan.');">
                      <input type="hidden" name="action" value="delete_paket">
                      <input type="hidden" name="paket_id" value="<?= (int) $paket['id'] ?>">
                      <button type="submit" class="ml-2 text-red-600 hover:underline font-medium">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div id="form-paket" class="bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center gap-4">
        <h2 class="text-lg font-bold text-green-800"><?= $editing ? 'Edit Paket' : 'Tambah Paket' ?></h2>
        <?php if ($editing): ?>
          <a href="<?= url('adminjalansehat/?page=pengaturan') ?>" class="text-sm text-gray-600 hover:underline">Batal edit</a>
        <?php endif; ?>
      </div>

      <?php if (!empty($paketFormErrors)): ?>
        <div class="mx-6 mt-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
          <?php foreach ($paketFormErrors as $err): ?><p><?= sanitize($err) ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" class="px-6 py-5 grid sm:grid-cols-2 gap-4">
        <input type="hidden" name="action" value="save_paket">
        <input type="hidden" name="paket_id" value="<?= (int) ($pf['id'] ?? 0) ?>">

        <div class="sm:col-span-2">
          <label for="paket_nama" class="block text-sm font-semibold text-gray-700 mb-1">Nama paket <span class="text-red-500">*</span></label>
          <input type="text" id="paket_nama" name="nama" required maxlength="150" value="<?= sanitize((string) $pf['nama']) ?>"
                 placeholder="Contoh: Paket A (MI)" class="<?= $inputClass ?>">
        </div>
        <div>
          <label for="paket_kupon" class="block text-sm font-semibold text-gray-700 mb-1">Jumlah kupon door prize</label>
          <input type="number" id="paket_kupon" name="jumlah_kupon" min="0" step="1" value="<?= (int) $pf['jumlah_kupon'] ?>" class="<?= $inputClass ?>">
        </div>
        <div>
          <label for="paket_kaos" class="block text-sm font-semibold text-gray-700 mb-1">Jumlah kaos (pcs)</label>
          <input type="number" id="paket_kaos" name="jumlah_kaos" min="0" step="1" value="<?= (int) $pf['jumlah_kaos'] ?>" class="<?= $inputClass ?>">
        </div>
        <div class="sm:col-span-2">
          <label for="paket_keterangan" class="block text-sm font-semibold text-gray-700 mb-1">Keterangan singkat</label>
          <input type="text" id="paket_keterangan" name="keterangan" maxlength="255" value="<?= sanitize((string) ($pf['keterangan'] ?? '')) ?>"
                 placeholder="Contoh: Untuk MI dengan jumlah siswa di atas 200" class="<?= $inputClass ?>">
        </div>
        <div>
          <label for="paket_urutan" class="block text-sm font-semibold text-gray-700 mb-1">Urutan tampil</label>
          <input type="number" id="paket_urutan" name="urutan" min="0" step="1" value="<?= (int) $pf['urutan'] ?>" class="<?= $inputClass ?>">
        </div>
        <label class="flex items-center gap-2 self-end pb-2">
          <input type="checkbox" name="aktif" value="1" class="h-4 w-4 text-green-700" <?= (int) $pf['aktif'] === 1 ? 'checked' : '' ?>>
          <span class="text-sm font-semibold text-gray-700">Aktif (tampil di form)</span>
        </label>
        <div class="sm:col-span-2">
          <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-5 py-2.5 rounded-lg text-sm">
            <?= $editing ? 'Simpan Perubahan' : 'Tambah Paket' ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
