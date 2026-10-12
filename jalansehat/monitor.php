<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/jalan_sehat_functions.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['monitor_action'] ?? '') === 'toggle_kaos_selesai') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $apiSettings = getJalanSehatPengaturan();
    } catch (PDOException $e) {
        http_response_code(503);
        echo json_encode(['ok' => false, 'message' => 'Database tidak tersedia.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!jalanSehatMonitorKaosEnabled($apiSettings)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Monitor kaos tidak aktif.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $id = (int) ($_POST['id'] ?? 0);
    $selesai = ($_POST['selesai'] ?? '') === '1' || ($_POST['selesai'] ?? '') === 'true';
    echo json_encode(setJalanSehatKaosKonveksiSelesai($id, $selesai), JSON_UNESCAPED_UNICODE);
    exit;
}

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
$showRekap = $monitorEnabled && !$dbError;

if ($showRekap) {
    try {
        ensureJalanSehatSchemaMigrations();
        $rows = loadJalanSehatPendaftaran();
        $rekap = getJalanSehatRekapKaosPencetak($rows);
        $updatedAt = date('d/m/Y H:i');
    } catch (PDOException $e) {
        $dbError = true;
        $showRekap = false;
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
    .madrasah-row.row-selesai { background-color: rgb(236 253 245); }
    .dark .madrasah-row.row-selesai { background-color: rgb(6 78 59 / 0.35); }
    .madrasah-row.row-selesai .nama-madrasah-cell { opacity: 0.72; }
    .kaos-selesai-cb { width: 1.15rem; height: 1.15rem; accent-color: #15803d; cursor: pointer; }
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
      <?php if ($showRekap): ?>
        <button type="button" onclick="window.print()" class="text-sm bg-white text-green-900 hover:bg-green-50 font-semibold px-4 py-2 rounded-lg">Cetak / PDF</button>
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
    <?php elseif ($showRekap && $rekap !== null): ?>
      <?php
      $pu = $rekap['per_ukuran']['pendek'];
      $pj = $rekap['per_ukuran']['panjang'];
      $progTotal = (int) $rekap['madrasah_ada_kaos'];
      $progDone = (int) $rekap['madrasah_selesai'];
      $progPct = $progTotal > 0 ? (int) round(100 * $progDone / $progTotal) : 0;
      $kecamatanList = [];
      $jenjangPresent = [];
      foreach ($rekap['per_madrasah'] as $mRow) {
          $kc = (string) $mRow['kecamatan'];
          if ($kc !== '' && $kc !== '—') {
              $kecamatanList[$kc] = true;
          }
          $jenjangPresent[(string) $mRow['jenjang']] = true;
      }
      $kecamatanList = array_keys($kecamatanList);
      sort($kecamatanList, SORT_NATURAL | SORT_FLAG_CASE);
      $jenjangFilterOpts = [];
      foreach (array_merge(jalanSehatJenjangOptions(), ['Lainnya']) as $jenjangOpt) {
          if (!empty($jenjangPresent[$jenjangOpt])) {
              $jenjangFilterOpts[] = $jenjangOpt;
          }
      }
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
          <div class="no-print rounded-xl border border-gray-200 bg-gray-50 px-4 py-4 space-y-3">
            <p class="text-sm font-semibold text-gray-800">Filter tampilan</p>
            <div class="flex flex-wrap gap-2">
              <label class="text-sm flex-1 min-w-[10rem] sm:max-w-[14rem]">
                <span class="block text-xs font-medium text-gray-600 mb-1">Kecamatan</span>
                <select id="filter-kecamatan" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                  <option value="">Semua kecamatan</option>
                  <?php foreach ($kecamatanList as $kec): ?>
                    <option value="<?= sanitize($kec) ?>"><?= sanitize($kec) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label class="text-sm flex-1 min-w-[8rem] sm:max-w-[11rem]">
                <span class="block text-xs font-medium text-gray-600 mb-1">Jenjang</span>
                <select id="filter-jenjang" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                  <option value="">Semua jenjang</option>
                  <?php foreach ($jenjangFilterOpts as $jenjangOpt): ?>
                    <option value="<?= sanitize($jenjangOpt) ?>"><?= sanitize($jenjangOpt) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label class="text-sm flex-1 min-w-[8rem] sm:max-w-[11rem]">
                <span class="block text-xs font-medium text-gray-600 mb-1">Status kerja</span>
                <select id="filter-status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                  <option value="all">Semua status</option>
                  <option value="pending">Belum dikerjakan</option>
                  <option value="done">Sudah selesai</option>
                </select>
              </label>
              <label class="text-sm flex-1 min-w-[12rem] sm:max-w-[16rem]">
                <span class="block text-xs font-medium text-gray-600 mb-1">Cari nama</span>
                <input type="search" id="filter-madrasah" placeholder="Nama madrasah…"
                       class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
              </label>
            </div>
            <p id="filter-active-hint" class="text-xs text-gray-600 hidden"></p>
          </div>

          <section>
            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">Ringkasan cetak</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="rounded-xl bg-green-50 dark:bg-green-950/50 border border-green-200 dark:border-green-800 p-4 text-center">
                <p id="stat-total-pcs" class="text-3xl font-bold text-green-800 dark:text-green-300"><?= number_format($rekap['total_pcs'], 0, ',', '.') ?></p>
                <p class="text-xs font-semibold text-green-900 dark:text-green-200 mt-1">Total kaos (pcs)</p>
              </div>
              <div class="rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 p-4 text-center">
                <p id="stat-total-pendek" class="text-3xl font-bold text-sky-800 dark:text-sky-300"><?= number_format($rekap['total_pendek'], 0, ',', '.') ?></p>
                <p class="text-xs font-semibold text-sky-900 dark:text-sky-200 mt-1">Lengan pendek</p>
              </div>
              <div class="rounded-xl bg-violet-50 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-800 p-4 text-center">
                <p id="stat-total-panjang" class="text-3xl font-bold text-violet-800 dark:text-violet-300"><?= number_format($rekap['total_panjang'], 0, ',', '.') ?></p>
                <p class="text-xs font-semibold text-violet-900 dark:text-violet-200 mt-1">Lengan panjang</p>
              </div>
              <div class="rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-600 p-4 text-center">
                <p id="stat-madrasah-count" class="text-3xl font-bold text-gray-800 dark:text-gray-200"><?= number_format($rekap['madrasah_ada_kaos'], 0, ',', '.') ?></p>
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mt-1">Madrasah pesan kaos</p>
              </div>
            </div>
            <?php if ($progTotal > 0): ?>
            <div class="mt-4 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/70 dark:bg-emerald-950/30 px-4 py-4 no-print">
              <div class="flex flex-wrap items-center justify-between gap-2 text-sm mb-2">
                <span class="font-semibold text-emerald-900 dark:text-emerald-200">Centang = pesanan madrasah sudah dikerjakan</span>
                <span id="progress-label" class="text-emerald-800 dark:text-emerald-300 font-medium">
                  <?= number_format($progDone, 0, ',', '.') ?> / <?= number_format($progTotal, 0, ',', '.') ?> madrasah selesai (<?= $progPct ?>%)
                </span>
              </div>
              <div class="h-3 bg-white/80 dark:bg-gray-900/50 rounded-full overflow-hidden border border-emerald-100 dark:border-emerald-900">
                <div id="progress-bar" class="h-full bg-emerald-600 transition-all duration-300" style="width: <?= $progPct ?>%"></div>
              </div>
            </div>
            <?php endif; ?>
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
                      <td id="stat-pendek-<?= sanitize($sz) ?>" class="border border-gray-300 dark:border-gray-600 cell-qty font-semibold"><?= (int) $pu[$sz] ?></td>
                    <?php endforeach; ?>
                    <td id="stat-pendek-jumlah" class="border border-gray-300 dark:border-gray-600 cell-qty font-bold bg-green-50 dark:bg-green-950/30"><?= (int) $rekap['total_pendek'] ?></td>
                  </tr>
                  <tr>
                    <td class="border border-gray-300 dark:border-gray-600 px-3 py-2 font-medium">Lengan panjang</td>
                    <?php foreach ($sizes as $sz): ?>
                      <td id="stat-panjang-<?= sanitize($sz) ?>" class="border border-gray-300 dark:border-gray-600 cell-qty font-semibold"><?= (int) $pj[$sz] ?></td>
                    <?php endforeach; ?>
                    <td id="stat-panjang-jumlah" class="border border-gray-300 dark:border-gray-600 cell-qty font-bold bg-green-50 dark:bg-green-950/30"><?= (int) $rekap['total_panjang'] ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="text-xs text-gray-500 mt-2">Angka di atas = jumlah lembar kaos yang harus disiapkan per ukuran (S–XXXL).</p>
          </section>

          <section>
            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">Rincian per madrasah</h3>
            <div class="overflow-x-auto">
              <table class="monitor-table w-full text-xs sm:text-sm border-collapse border border-gray-300 dark:border-gray-600" id="tabel-madrasah">
                <thead>
                  <tr class="bg-gray-100 dark:bg-gray-900">
                    <th rowspan="2" class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center min-w-[3rem]">Selesai</th>
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
                    <tr><td colspan="<?= 4 + count($sizes) * 2 + 1 ?>" class="border px-4 py-8 text-center text-gray-500">Belum ada pemesanan kaos.</td></tr>
                  <?php else: ?>
                    <?php foreach ($rekap['per_madrasah'] as $i => $m): ?>
                      <?php
                      $searchBlob = strtolower($m['nama_madrasah'] . ' ' . $m['kecamatan']);
                      $isSelesai = !empty($m['selesai']);
                      ?>
                      <tr class="madrasah-row hover:bg-gray-50 dark:hover:bg-gray-900/50 <?= $isSelesai ? 'row-selesai' : '' ?>"
                          data-search="<?= sanitize($searchBlob) ?>"
                          data-selesai="<?= $isSelesai ? '1' : '0' ?>"
                          data-kecamatan="<?= sanitize((string) $m['kecamatan']) ?>"
                          data-jenjang="<?= sanitize((string) $m['jenjang']) ?>"
                          data-id="<?= (int) $m['id'] ?>">
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center align-middle">
                          <label class="inline-flex items-center justify-center cursor-pointer print:hidden" title="Tandai sudah dikerjakan">
                            <input type="checkbox" class="kaos-selesai-cb" data-id="<?= (int) $m['id'] ?>"
                                   <?= $isSelesai ? 'checked' : '' ?> aria-label="Sudah dikerjakan: <?= sanitize($m['nama_madrasah']) ?>">
                          </label>
                          <span class="hidden print:inline font-bold"><?= $isSelesai ? '✓' : '—' ?></span>
                        </td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 row-no"><?= $i + 1 ?></td>
                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-1.5 font-medium nama-madrasah-cell"><?= sanitize($m['nama_madrasah']) ?></td>
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
            <p class="text-xs text-gray-500 mt-2 no-print">Centang disimpan otomatis (sama di semua perangkat). Titik (·) = tidak pesan ukuran tersebut. Baris hijau = sudah dikerjakan.</p>
          </section>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <footer class="bg-green-900 text-green-100 py-5 mt-8 no-print">
    <div class="max-w-6xl mx-auto px-6 text-center text-xs sm:text-sm">© 2026 LP Ma'arif NU Kabupaten Magelang · Halaman khusus konveksi</div>
  </footer>

  <?= siteThemeFooterScript() ?>
  <?php if ($showRekap && $rekap !== null): ?>
  <script>
    (function () {
      var monitorUrl = <?= json_encode(url('jalansehat/monitor'), JSON_UNESCAPED_UNICODE) ?>;
      var searchInput = document.getElementById('filter-madrasah');
      var statusSelect = document.getElementById('filter-status');
      var kecamatanSelect = document.getElementById('filter-kecamatan');
      var jenjangSelect = document.getElementById('filter-jenjang');
      var progressLabel = document.getElementById('progress-label');
      var progressBar = document.getElementById('progress-bar');
      var filterHint = document.getElementById('filter-active-hint');
      var sizeLabels = <?= json_encode($sizes, JSON_UNESCAPED_UNICODE) ?>;
      var monitorRows = <?= json_encode($rekap['per_madrasah'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

      function filterState() {
        return {
          q: searchInput ? searchInput.value.trim().toLowerCase() : '',
          status: statusSelect ? statusSelect.value : 'all',
          kecamatan: kecamatanSelect ? kecamatanSelect.value : '',
          jenjang: jenjangSelect ? jenjangSelect.value : '',
        };
      }

      function rowMatches(row, state) {
        var blob = (row.nama_madrasah + ' ' + row.kecamatan).toLowerCase();
        if (state.q !== '' && blob.indexOf(state.q) === -1) return false;
        if (state.kecamatan !== '' && row.kecamatan !== state.kecamatan) return false;
        if (state.jenjang !== '' && row.jenjang !== state.jenjang) return false;
        var selesai = !!row.selesai;
        if (state.status === 'done' && !selesai) return false;
        if (state.status === 'pending' && selesai) return false;
        return true;
      }

      function trMatches(tr, state) {
        var blob = tr.getAttribute('data-search') || '';
        if (state.q !== '' && blob.indexOf(state.q) === -1) return false;
        if (state.kecamatan !== '' && tr.getAttribute('data-kecamatan') !== state.kecamatan) return false;
        if (state.jenjang !== '' && tr.getAttribute('data-jenjang') !== state.jenjang) return false;
        var selesai = tr.getAttribute('data-selesai') === '1';
        if (state.status === 'done' && !selesai) return false;
        if (state.status === 'pending' && selesai) return false;
        return true;
      }

      function setText(id, n) {
        var el = document.getElementById(id);
        if (el) el.textContent = Number(n).toLocaleString('id-ID');
      }

      function updateSummaryFromFilter(state) {
        var totals = { pcs: 0, pendek: 0, panjang: 0, count: 0, done: 0 };
        var perPendek = {};
        var perPanjang = {};
        sizeLabels.forEach(function (sz) {
          perPendek[sz] = 0;
          perPanjang[sz] = 0;
        });
        monitorRows.forEach(function (row) {
          if (!rowMatches(row, state)) return;
          totals.count++;
          totals.pcs += row.total;
          totals.pendek += row.pendek;
          totals.panjang += row.panjang;
          if (row.selesai) totals.done++;
          sizeLabels.forEach(function (sz) {
            perPendek[sz] += (row.ukuran_pendek && row.ukuran_pendek[sz]) ? row.ukuran_pendek[sz] : 0;
            perPanjang[sz] += (row.ukuran_panjang && row.ukuran_panjang[sz]) ? row.ukuran_panjang[sz] : 0;
          });
        });
        setText('stat-total-pcs', totals.pcs);
        setText('stat-total-pendek', totals.pendek);
        setText('stat-total-panjang', totals.panjang);
        setText('stat-madrasah-count', totals.count);
        sizeLabels.forEach(function (sz) {
          setText('stat-pendek-' + sz, perPendek[sz]);
          setText('stat-panjang-' + sz, perPanjang[sz]);
        });
        setText('stat-pendek-jumlah', totals.pendek);
        setText('stat-panjang-jumlah', totals.panjang);
        if (progressLabel) {
          var pct = totals.count > 0 ? Math.round(100 * totals.done / totals.count) : 0;
          progressLabel.textContent = totals.done.toLocaleString('id-ID') + ' / ' + totals.count.toLocaleString('id-ID') + ' madrasah selesai (' + pct + '%)';
          if (progressBar) progressBar.style.width = pct + '%';
        }
        if (filterHint) {
          var parts = [];
          if (state.kecamatan) parts.push('Kecamatan: ' + state.kecamatan);
          if (state.jenjang) parts.push('Jenjang: ' + state.jenjang);
          if (parts.length) {
            filterHint.textContent = 'Ringkasan angka mengikuti filter — ' + parts.join(' · ');
            filterHint.classList.remove('hidden');
          } else {
            filterHint.classList.add('hidden');
          }
        }
      }

      function applyFilters() {
        var state = filterState();
        var no = 0;
        document.querySelectorAll('#tabel-madrasah .madrasah-row').forEach(function (tr) {
          var show = trMatches(tr, state);
          tr.style.display = show ? '' : 'none';
          if (show) {
            no++;
            var cell = tr.querySelector('.row-no');
            if (cell) cell.textContent = String(no);
          }
        });
        updateSummaryFromFilter(state);
      }

      function refreshProgress() {
        applyFilters();
      }

      function setRowSelesai(tr, selesai) {
        tr.setAttribute('data-selesai', selesai ? '1' : '0');
        tr.classList.toggle('row-selesai', selesai);
        var cb = tr.querySelector('.kaos-selesai-cb');
        if (cb) cb.checked = selesai;
      }

      if (searchInput) searchInput.addEventListener('input', applyFilters);
      if (statusSelect) statusSelect.addEventListener('change', applyFilters);
      if (kecamatanSelect) kecamatanSelect.addEventListener('change', applyFilters);
      if (jenjangSelect) jenjangSelect.addEventListener('change', applyFilters);

      document.querySelectorAll('.kaos-selesai-cb').forEach(function (cb) {
        cb.addEventListener('change', function () {
          var tr = cb.closest('tr');
          if (!tr) return;
          var selesai = cb.checked;
          var fd = new FormData();
          fd.append('monitor_action', 'toggle_kaos_selesai');
          fd.append('id', cb.getAttribute('data-id') || '');
          fd.append('selesai', selesai ? '1' : '0');
          cb.disabled = true;
          fetch(monitorUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
              if (!res || !res.ok) {
                cb.checked = !selesai;
                alert((res && res.message) ? res.message : 'Gagal menyimpan centang.');
                return;
              }
              setRowSelesai(tr, selesai);
              var id = parseInt(tr.getAttribute('data-id') || '0', 10);
              monitorRows.forEach(function (row) {
                if (row.id === id) row.selesai = selesai;
              });
              refreshProgress();
            })
            .catch(function () {
              cb.checked = !selesai;
              alert('Koneksi gagal. Coba lagi.');
            })
            .finally(function () { cb.disabled = false; });
        });
      });
    })();
  </script>
  <?php endif; ?>
</body>
</html>
