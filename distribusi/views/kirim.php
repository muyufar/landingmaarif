<?php declare(strict_types=1); /** @var array $waLinks */ ?>
<div class="bg-white rounded-2xl border border-green-100 shadow-lg p-6 max-w-2xl">
  <h2 class="text-xl font-bold text-green-800 mb-2">Kirim Buku — Packing → Delivery</h2>
  <p class="text-sm text-gray-600 mb-6">Scan atau ketik NPSN satuan pendidikan. Status berubah ke <strong>Delivery</strong> dan notifikasi WA dikirim ke operator/kepsek (data pengkinian).</p>

  <?php if (isset($_GET['success'])): ?>
    <div class="mb-6 rounded-xl bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
      <p class="font-semibold">Pengiriman berhasil dicatat!</p>
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
        <p class="mt-2 text-amber-700">Data HP operator/kepsek belum ada di pengkinian data. Lengkapi di form pengkinian.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <form method="post" class="space-y-4">
    <div>
      <label for="dispatch_npsn" class="block text-sm font-semibold mb-2">Nomor NPSN <span class="text-red-500">*</span></label>
      <input type="text" id="dispatch_npsn" name="dispatch_npsn" required inputmode="numeric"
             placeholder="Masukkan NPSN" autofocus
             class="w-full rounded-lg border px-4 py-3 text-lg font-mono focus:ring-2 focus:ring-green-600">
    </div>
    <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-bold py-3 rounded-xl">
      Proses Pengiriman
    </button>
  </form>
</div>

<script>
(function () {
  const input = document.getElementById('dispatch_npsn');
  if (input && !input.value) input.focus();
})();
</script>
