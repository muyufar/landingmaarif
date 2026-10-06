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
        <input type="hidden" name="action" value="save_pengaturan">

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

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="harga_kupon" class="block text-sm font-semibold text-gray-700 mb-1">Harga / kupon (Rp)</label>
            <input type="number" min="0" step="1" id="harga_kupon" name="harga_kupon" value="<?= (int) $settings['harga_kupon'] ?>" class="<?= $inputClass ?>">
          </div>
          <div>
            <label for="harga_kaos" class="block text-sm font-semibold text-gray-700 mb-1">Harga / kaos (Rp)</label>
            <input type="number" min="0" step="1" id="harga_kaos" name="harga_kaos" value="<?= (int) $settings['harga_kaos'] ?>" class="<?= $inputClass ?>">
          </div>
          <p class="col-span-2 text-xs text-gray-500">Isi 0 jika tidak ada harga; estimasi biaya tidak akan ditampilkan.</p>
        </div>

        <div>
          <label for="info_surat_edaran" class="block text-sm font-semibold text-gray-700 mb-1">Ketentuan / kutipan surat edaran</label>
          <textarea id="info_surat_edaran" name="info_surat_edaran" rows="5" class="<?= $inputClass ?>"
                    placeholder="Contoh: Sesuai SE No. .../PCNU/X/2026, setiap MI wajib memesan minimal ... kupon door prize. Pembayaran paling lambat tanggal ..."><?= sanitize($settings['info_surat_edaran']) ?></textarea>
          <p class="text-xs text-gray-500 mt-1">Tampil di atas isian Jumlah Pemesanan pada form publik.</p>
        </div>

        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold px-4 py-2.5 rounded-lg text-sm">Simpan Pengaturan</button>
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
