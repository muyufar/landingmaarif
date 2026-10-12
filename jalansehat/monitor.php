<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/jalan_sehat_functions.php';

$errors = [];
$settings = jalanSehatPengaturanDefaults();
$dbError = false;
$rekap = null;
$updatedAt = null;
$byJenis = jalanSehatUkuranKaosByJenis();
$sizes = array_keys($byJenis['pendek']['columns']);

try {
    $settings = getJalanSehatPengaturan();
} catch (PDOException $e) {
    $dbError = true;
}

$monitorEnabled = jalanSehatMonitorKaosEnabled($settings);

if (isset($_GET['keluar'])) {
    jalanSehatRevokeMonitorKaosAccess();
    header('Location: ' . url('jalansehat/monitor'));
    exit;
}

if ($monitorEnabled && !$dbError) {
    $kFromQuery = trim((string) ($_GET['k'] ?? ''));
    if ($kFromQuery !== '' && jalanSehatVerifyMonitorKaosKode($settings, $kFromQuery)) {
        jalanSehatGrantMonitorKaosAccess();
        header('Location: ' . url('jalansehat/monitor'));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $inputKode = trim((string) ($_POST['kode_akses'] ?? ''));
        if (jalanSehatVerifyMonitorKaosKode($settings, $inputKode)) {
            jalanSehatGrantMonitorKaosAccess();
            header('Location: ' . url('jalansehat/monitor'));
            exit;
        }
        $errors[] = 'Kode akses salah. Minta kode terbaru ke panitia LP Ma\'arif NU.';
    }
}

$hasAccess = $monitorEnabled && !$dbError && jalanSehatHasMonitorKaosAccess();

if ($hasAccess) {
    try {
        $rows = loadJalanSehatPendaftaran();
        $rekap = getJalanSehatRekapKaosPencetak($rows);
        $updatedAt = date('d/m/Y H:i');
    } catch (PDOException $e) {
        $dbError = true;
        $hasAccess = false;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?= siteHeadIcons() ?>
  <?= siteThemeHead() ?>
  <title>Monitor Pemesanan Kaos Jalan Sehat | LP Ma'arif NU Magelang</title>
  <?= siteTailwindScript() ?>
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: #fff !important; color: #000 !important; }
      .print-sheet { box-shadow: none !important; border: none !important; }
      .monitor-table { font-size: 9pt; }
      .monitor-table th, .monitor-table td { border: 1px solid #333 !important; }
      @page { size: landscape; margin: 12mm; }
    }
    .monitor-table th, .monitor-table td { padding: 0.35rem 0.5rem; }
    .cell-qty { text-align: center; min-width: 2rem; }
    .cell-qty-empty { color: #9ca3af; }
    .dark .cell-qty-empty { color: #6b7280; }
  </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 min-h-screen flex flex-col">

  <header class="bg-green-800 text-white shadow-lg no-print">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <img src="<?= url('image/logo.png') ?>" alt="Logo" class="w-11 h-11 rounded-full bg-white p-1">
        <div>
          <h1 class="text-base sm:text-lg font-bold leading-tight">Monitor Kaos — Jalan Sehat LP Ma'arif NU</h1>
          <p class="text-xs sm:text-sm text-green-100">Untuk konveksi / pencetak kaos</p>
        </div>
      </div>
      <?php if ($hasAccess): ?>
        <div class="flex flex-wrap gap-2">
          <button type="button" onclick="window.print()" class="text-sm bg-white text-green-900 hover:bg-green-50 font-semibold px-4 py-2 rounded-lg">Cetak / PDF</button>
          <a href="<?= url('jalansehat/monitor?keluar=1') ?>" class="text-sm bg-green-950 hover:bg-black px-4 py-2 rounded-lg">Keluar</a>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <main class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex-1">
    <?php if ($dbError): ?>
      <div class="rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 px-6 py-5 text-red-800 dark:text-red-200 text-sm">
        Koneksi data sementara bermasalah. Coba beberapa saat lagi atau hubungi panitia.
      </div>
    <?php elseif (!$monitorEnabled): ?>
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-green-100 dark:border-gray-700 px-8 py-10 text-center max-w-lg mx-auto">
        <p class="text-lg font-semibold text-green-800 dark:text-green-300">Monitor kaos belum dibuka</p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Panitia belum mengaktifkan halaman ini. Hubungi LP Ma'arif NU Kab. Magelang untuk akses konveksi.</p>
      </div>
    <?php elseif (!$hasAccess): ?>
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-green-100 dark:border-gray-700 overflow-hidden max-w-md mx-auto">
        <div class="bg-green-700 text-white px-6 py-5">
          <h2 class="text-xl font-bold">Masuk dengan kode akses</h2>
          <p class="text-green-100 text-sm mt-1">Kode diberikan panitia kepada konveksi. Jangan dibagikan ke umum.</p>
        </div>
        <div class="px-6 py-6">
          <?php if (!empty($errors)): ?>
            <ul class="mb-4 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
              <?php foreach ($errors as $error): ?>
                <li><?= sanitize($error) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <form method="post" class="space-y-4">
            <div>
              <label for="kode_akses" class="block text-sm font-semibold mb-2">Kode akses konveksi</label>
              <input type="password" id="kode_akses" name="kode_akses" required autocomplete="off"
                     class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-4 py-3 font-mono focus:ring-2 focus:ring-green-600">
            </div>
            <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg">Lihat rekap kaos</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <?php
      $pu = $rekap['per_ukuran']['pendek'];
      $pj = $rekap['per_ukuran']['panjang'];
      ?>
      <div class="print-sheet bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-green-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex flex-wrap justify-between gap-2 items-baseline">
          <div>
            <h2 class="text-xl font-bold text-green-800 dark:text-green-300">Rekap pemesanan kaos Jalan Sehat</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">LP Ma'arif NU Kabupaten Magelang · data pendaftaran madrasah</p>
          </div>
          <?php if ($updatedAt !== null): ?>
            <p class="text-xs text-gray-500">Diperbarui: <strong><?= sanitize($updatedAt) ?> WIB</strong></p>
          <?php endif; ?>
        </div>

        <div class="px-6 py-6 space-y-8">
          <section>
            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">Ringkasan cetak</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="rounded-xl bg-green-50 dark:bg-green-950/50 border border-green-200 dark:border-green-800 p-4 text-center">
                <p class="text-3xl font-bold text-green-800 dark:text-green-300"><?= number_format($rekap['total_pcs'], 0, ',', '.') ?></p>
                <p class="text-xs font-semibold text-green-900 dark:text-green-200 mt-1">Total kaos (pcs)</p>
              </div>
              <div class="rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 p-4 text-center">
                <p class="text-3xl font-bold text-sky-800 dark:text-sky-300"><?= number_format($rekap['total_pendek'], 0, ',', '.') ?></p>
                <p class="text-xs font-semibold text-sky-900 dark:text-sky-200 mt-1">Lengan pendek</p>
              </div>
              <div class="rounded-xl bg-violet-50 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-800 p-4 text-center">
                <p class="text-3xl font-bold text-violet-800 dark:text-violet-300"><?= number_format($rekap['total_panjang'], 0, ',', '.') ?></p>
                <p class="text-xs font-semibold text-violet-900 dark:text-violet-200 mt-1">Lengan panjang</p>
              </div>
              <div class="rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-600 p-4 text-center">
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-200"><?= number_format($rekap['madrasah_ada_kaos'], 0, ',', '.') ?></p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-1">Madrasah pesan kaos</p>
              </div>
            </div>
          </section>

          <section>
            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">Total semua madrasah per ukuran</h3>
            <div class="overflow-x-auto">
              <table class="monitor-table w-full text-sm border-collapse border border-gray-300 dark:border-gray-600">
                <thead>
                  <tr class="bg-gray-100 dark:bg-gray-900">
                    <th class="border border-gray-300 dark:border-gray-600 text-left px-3 py-2">Jenis kaos</th>
                    <?php foreach ($sizes as $sz): ?>
                      <th class="border border-gray-300 dark:border-gray-600 cell-qty font-bold"><?= sanitize($sz) ?></th>
                    <?php endforeach; ?>
                    <th class="border border-gray-300 dark:border-gray-600 cell-qty font-bold bg-green-100 dark:bg-green-900/40">Jumlah</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="border border-gray-300 dark:border-gray-600 px-3 py-2 font-medium">Lengan pendek</td>
                    <?php foreach ($sizes as $sz): ?>
                      <td class="border border-gray-300 dark:border-gray-600 cell-qty font-semibold"><?= (int) $pu[$sz] ?></td>
                    <?php endforeach; ?>
                    <td class="border border-gray-300 dark:border-gray-600 cell-qty font-bold bg-green-50 dark:bg-green-950/30"><?= (int) $rekap['total_pendek'] ?></td>
                  </tr>
                  <tr>
                    <td class="border border-gray-300 dark:border-gray-600 px-3 py-2 font-medium">Lengan panjang</td>
                    <?php foreach ($sizes as $sz): ?>
                      <td class="border border-gray-300 dark:border-gray-600 cell-qty font-semibold"><?= (int) $pj[$sz] ?></td>
                    <?php endforeach; ?>
                    <td class="border border-gray-300 dark:border-gray-600 cell-qty font-bold bg-green-50 dark:bg-green-950/30"><?= (int) $rekap['total_panjang'] ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="text-xs text-gray-500 mt-2">Angka di atas = jumlah lembar kaos yang harus disiapkan per ukuran (S–XXXL).</p>
          </section>

          <section>
            <div class="flex flex-wrap items-end justify-between gap-3 mb-3 no-print">
              <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rincian per madrasah</h3>
              <label class="text-sm w-full sm:w-auto">
                <span class="sr-only">Cari madrasah</span>
                <input type="search" id="filter-madrasah" placeholder="Ketik nama madrasah / kecamatan…"
                       class="w-full sm:w-72 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
              </label>
            </div>
            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3 hidden print:block">Rincian per madrasah</h3>
            <div class="overflow-x-auto">
              <table class="monitor-table w-full text-xs sm:text-sm border-collapse border border-gray-300 dark:border-gray-600" id="tabel-madrasah">
                <thead>
                  <tr class="bg-gray-100 dark:bg-gray-900">
                    <th rowspan="2" class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-left sticky left-0 bg-gray-100 dark:bg-gray-900">No</th>
                    <th rowspan="2" class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-left min-w-[10rem]">Madrasah</th>
                    <th rowspan="2" class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-left">Kecamatan</th>
                    <th colspan="<?= count($sizes) ?>" class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center bg-sky-50 dark:bg-sky-950/30">Lengan pendek</th>
                    <th colspan="<?= count($sizes) ?>" class="border border-gray-300 dark:border-gray-600 px-2 py-1 text-center bg-violet-50 dark:bg-violet-950/30">Lengan panjang</th>
                    <th rowspan="2" class="border border-gray-300 dark:border-gray-600 cell-qty font-bold bg-green-100 dark:bg-green-900/40">Total</th>
                  </tr>
                  <tr class="bg-gray-50 dark:bg-gray-900/80">
                    <?php foreach ($sizes as $sz): ?>
                      <th class="border border-gray-300 dark:border-gray-600 cell-qty text-sky-800 dark:text-sky-300"><?= sanitize($sz) ?></th>
                    <?php endforeach; ?>
                    <?php foreach ($sizes as $sz): ?>
                      <th class="border border-gray-300 dark:border-gray-600 cell-qty text-violet-800 dark:text-violet-300"><?= sanitize($sz) ?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($rekap['per_madrasah'] === []): ?>
                    <tr><td colspan="<?= 3 + count($sizes) * 2 + 1 ?>" class="border px-4 py-8 text-center text-gray-500">Belum ada pemesanan kaos.</td></tr>
                  <?php else: ?>
                    <?php foreach ($rekap['per_madrasah'] as $i => $m): ?>
                      <?php
                      $searchBlob = strtolower($m['nama_madrasah'] . ' ' . $m['kecamatan']);
                      ?>
                      <tr class="madrasah-row hover:bg-gray-50 dark:hover:bg-gray-900/50" data-search="<?= sanitize($searchBlob) ?>">
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1.5"><?= $i + 1 ?></td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 font-medium"><?= sanitize($m['nama_madrasah']) ?></td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1.5"><?= sanitize($m['kecamatan']) ?></td>
                        <?php foreach ($sizes as $sz): ?>
                          <?php $q = (int) ($m['ukuran_pendek'][$sz] ?? 0); ?>
                          <td class="border border-gray-300 dark:border-gray-600 cell-qty <?= $q === 0 ? 'cell-qty-empty' : 'font-semibold' ?>"><?= $q === 0 ? '·' : (string) $q ?></td>
                        <?php endforeach; ?>
                        <?php foreach ($sizes as $sz): ?>
                          <?php $q = (int) ($m['ukuran_panjang'][$sz] ?? 0); ?>
                          <td class="border border-gray-300 dark:border-gray-600 cell-qty <?= $q === 0 ? 'cell-qty-empty' : 'font-semibold' ?>"><?= $q === 0 ? '·' : (string) $q ?></td>
                        <?php endforeach; ?>
                        <td class="border border-gray-300 dark:border-gray-600 cell-qty font-bold"><?= (int) $m['total'] ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <p class="text-xs text-gray-500 mt-2 no-print">Titik (·) = tidak pesan ukuran tersebut. Saat cetak, semua baris ikut tercetak.</p>
          </section>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <footer class="bg-green-900 text-green-100 py-5 mt-8 no-print">
    <div class="max-w-6xl mx-auto px-6 text-center text-xs sm:text-sm">© 2026 LP Ma'arif NU Kabupaten Magelang · Halaman khusus konveksi</div>
  </footer>

  <?= siteThemeFooterScript() ?>
  <?php if ($hasAccess): ?>
  <script>
    (function () {
      var input = document.getElementById('filter-madrasah');
      if (!input) return;
      input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        document.querySelectorAll('#tabel-madrasah .madrasah-row').forEach(function (tr) {
          var blob = tr.getAttribute('data-search') || '';
          tr.style.display = q === '' || blob.indexOf(q) !== -1 ? '' : 'none';
        });
      });
    })();
  </script>
  <?php endif; ?>
</body>
</html>
