<?php

declare(strict_types=1);

/** @var array $settings */
$adaHarga = jalanSehatAdaHarga($settings);
$hargaKupon = (int) ($settings['harga_kupon'] ?? 0);
$hargaKaos = (int) ($settings['harga_kaos'] ?? 0);
$kuponPerKaos = jalanSehatKuponPerKaos($settings);
$ikutKaos = jalanSehatKuponMengikutiKaos($settings);
$pengaturanUrl = url('adminjalansehat/?page=pengaturan');
?>
<div class="mb-6 rounded-xl border border-green-200 bg-white shadow-sm overflow-hidden">
  <div class="px-5 py-3 bg-green-800 text-white flex flex-wrap items-center justify-between gap-2">
    <h2 class="text-sm font-bold tracking-wide uppercase">Tarif &amp; ketentuan pemesanan</h2>
    <a href="<?= sanitize($pengaturanUrl) ?>" class="text-xs bg-green-900/80 hover:bg-green-950 px-3 py-1.5 rounded-lg font-medium">Ubah di Pengaturan →</a>
  </div>
  <div class="px-5 py-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
    <div>
      <p class="text-gray-500 text-xs uppercase font-semibold">Harga kupon door prize</p>
      <p class="text-lg font-bold text-green-800 mt-0.5">
        <?= $hargaKupon > 0 ? sanitize(formatRupiahJalanSehat($hargaKupon)) : '— belum diatur —' ?>
      </p>
      <p class="text-xs text-gray-500">per kupon</p>
    </div>
    <div>
      <p class="text-gray-500 text-xs uppercase font-semibold">Harga kaos</p>
      <p class="text-lg font-bold text-green-800 mt-0.5">
        <?= $hargaKaos > 0 ? sanitize(formatRupiahJalanSehat($hargaKaos)) : '— belum diatur —' ?>
      </p>
      <p class="text-xs text-gray-500">per pcs</p>
    </div>
    <div class="sm:col-span-2 lg:col-span-2">
      <p class="text-gray-500 text-xs uppercase font-semibold">Bonus kupon (jumlah sendiri)</p>
      <?php if ($ikutKaos): ?>
        <p class="text-base font-semibold text-gray-800 mt-0.5">
          <strong>1 kaos = <?= $kuponPerKaos ?> kupon</strong> door prize (bonus; bisa dihitung dari form, kupon saja tetap diisi manual)
        </p>
        <p class="text-xs text-gray-600 mt-1">Paket tetap memakai jumlah kupon/kaos masing-masing paket. Tagihan = (jumlah kupon × harga kupon) + (jumlah kaos × harga kaos).</p>
      <?php else: ?>
        <p class="text-base text-gray-700 mt-0.5">Kupon tidak disamakan otomatis dengan kaos (mode manual).</p>
      <?php endif; ?>
    </div>
  </div>
  <?php if (!$adaHarga): ?>
    <div class="px-5 py-3 bg-amber-50 border-t border-amber-200 text-amber-900 text-sm">
      Harga belum diisi — kolom rincian tagihan &amp; total pendapatan tidak muncul.
      <a href="<?= sanitize($pengaturanUrl) ?>" class="font-semibold underline ml-1">Atur harga kupon &amp; kaos sekarang</a>.
    </div>
  <?php endif; ?>
</div>
