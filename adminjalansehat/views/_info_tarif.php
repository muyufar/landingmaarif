<?php

declare(strict_types=1);

/** @var array $settings */
$adaHarga = jalanSehatAdaHarga($settings);
$hargaKupon = (int) ($settings['harga_kupon'] ?? 0);
$hargaPendek = jalanSehatHargaKaosPendek($settings);
$hargaPanjang = jalanSehatHargaKaosPanjang($settings);
$kuponPerKaos = jalanSehatKuponPerKaos($settings);
$ikutKaos = jalanSehatKuponMengikutiKaos($settings);
$pengaturanUrl = url('adminjalansehat/?page=pengaturan');
$sePdf = url('data/jalan_sehat/SE_Revisi_Jalan_Santai_HSN_2026.pdf');
?>
<div class="mb-6 rounded-xl border border-green-200 bg-white shadow-sm overflow-hidden">
  <div class="px-5 py-3 bg-green-800 text-white flex flex-wrap items-center justify-between gap-2">
    <h2 class="text-sm font-bold tracking-wide uppercase">Tarif &amp; ketentuan pemesanan (SE 0629/LPM/X/2026)</h2>
    <div class="flex flex-wrap gap-2">
      <a href="<?= sanitize($sePdf) ?>" target="_blank" rel="noopener" class="text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg font-medium">PDF Surat Edaran</a>
      <a href="<?= sanitize($pengaturanUrl) ?>" class="text-xs bg-green-900/80 hover:bg-green-950 px-3 py-1.5 rounded-lg font-medium">Ubah di Pengaturan →</a>
    </div>
  </div>
  <div class="px-5 py-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
    <div>
      <p class="text-gray-500 text-xs uppercase font-semibold">Pendaftaran / kupon saja</p>
      <p class="text-lg font-bold text-green-800 mt-0.5">
        <?= $hargaKupon > 0 ? sanitize(formatRupiahJalanSehat($hargaKupon)) : '— belum diatur —' ?>
      </p>
      <p class="text-xs text-gray-500">per kupon door prize</p>
    </div>
    <div>
      <p class="text-gray-500 text-xs uppercase font-semibold">Kaos lengan pendek</p>
      <p class="text-lg font-bold text-green-800 mt-0.5">
        <?= $hargaPendek > 0 ? sanitize(formatRupiahJalanSehat($hargaPendek)) : '—' ?>
      </p>
      <p class="text-xs text-gray-500">termasuk daftar + <?= $kuponPerKaos ?> kupon</p>
    </div>
    <div>
      <p class="text-gray-500 text-xs uppercase font-semibold">Kaos lengan panjang</p>
      <p class="text-lg font-bold text-green-800 mt-0.5">
        <?= $hargaPanjang > 0 ? sanitize(formatRupiahJalanSehat($hargaPanjang)) : '—' ?>
      </p>
      <p class="text-xs text-gray-500">termasuk daftar + <?= $kuponPerKaos ?> kupon</p>
    </div>
    <div>
      <p class="text-gray-500 text-xs uppercase font-semibold">Perhitungan tagihan</p>
      <p class="text-xs text-gray-700 mt-1 leading-relaxed">
        Kaos ditagih per model. Kupon bonus dari kaos tidak ditagih lagi.
        <?php if ($ikutKaos): ?> Kupon di luar bonus = harga kupon × jumlah.<?php endif; ?>
      </p>
    </div>
  </div>
  <?php if (!$adaHarga): ?>
    <div class="px-5 py-3 bg-amber-50 border-t border-amber-200 text-amber-900 text-sm">
      Tarif belum lengkap di database.
      <a href="<?= sanitize($pengaturanUrl) ?>" class="font-semibold underline ml-1">Buka Pengaturan → Terapkan tarif SE</a>.
    </div>
  <?php endif; ?>
</div>
