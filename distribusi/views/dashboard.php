<?php declare(strict_types=1);
/** @var array $stats @var array $deliveryRows @var array|null $petugas */
$statusKeys = ['packing', 'delivery', 'receive', 'done'];
$statusLabels = array_map('distribusiStatusLabel', $statusKeys);
$statusCounts = array_map(fn ($k) => (int) ($stats[$k] ?? 0), $statusKeys);
$statusBuku = array_map(fn ($k) => (int) ($stats['buku'][$k] ?? 0), $statusKeys);
$siapKirim = (int) ($stats['packing'] ?? 0) + (int) ($stats['receive'] ?? 0);
$bukuSiapKirim = (int) ($stats['buku']['packing'] ?? 0) + (int) ($stats['buku']['receive'] ?? 0);
$pctDone = (float) ($stats['pct_done'] ?? 0);
$pctProgress = (float) ($stats['pct_progress'] ?? 0);
$deliveryCount = count($deliveryRows);
$chartJson = json_encode([
    'statusLabels' => $statusLabels,
    'statusCounts' => $statusCounts,
    'statusBuku' => $statusBuku,
    'tugasLabels' => ['Siap Kirim', 'Sedang Delivery', 'Selesai'],
    'tugasCounts' => [$siapKirim, (int) ($stats['delivery'] ?? 0), (int) ($stats['done'] ?? 0)],
    'bukuLkpd' => (int) ($stats['buku_lkpd'] ?? 0),
    'bukuGuru' => (int) ($stats['total_buku_guru'] ?? 0),
], JSON_UNESCAPED_UNICODE);
?>
<div class="space-y-6">
  <!-- Hero -->
  <div class="rounded-2xl bg-gradient-to-br from-green-800 via-green-700 to-teal-600 text-white p-6 shadow-lg">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <p class="text-green-100 text-sm">Selamat datang,</p>
        <h2 class="text-xl lg:text-2xl font-bold"><?= sanitize($petugas['nama'] ?? 'Petugas') ?></h2>
        <p class="text-green-100 text-sm mt-1">Ringkasan tugas distribusi LKPD hari ini</p>
      </div>
      <div class="flex gap-3">
        <a href="<?= url('distribusi/?page=kirim') ?>"
           class="inline-flex items-center justify-center bg-white text-green-800 font-semibold text-sm px-4 py-2.5 rounded-lg hover:bg-green-50 shadow">
          Kirim Buku
        </a>
        <a href="<?= url('distribusi/?page=terima') ?>"
           class="inline-flex items-center justify-center bg-blue-500 text-white font-semibold text-sm px-4 py-2.5 rounded-lg hover:bg-blue-400 shadow">
          Terima Buku
        </a>
      </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-6">
      <div class="bg-white/10 rounded-xl px-4 py-3 border border-white/20">
        <p class="text-2xl font-bold"><?= $siapKirim ?></p>
        <p class="text-xs text-green-100">Siap dikirim</p>
        <p class="text-[10px] text-green-200 mt-0.5">Packing + Receive</p>
      </div>
      <div class="bg-white/10 rounded-xl px-4 py-3 border border-white/20">
        <p class="text-2xl font-bold"><?= $deliveryCount ?></p>
        <p class="text-xs text-green-100">Menunggu terima</p>
        <p class="text-[10px] text-green-200 mt-0.5">Status Delivery</p>
      </div>
      <div class="bg-white/10 rounded-xl px-4 py-3 border border-white/20">
        <p class="text-2xl font-bold"><?= number_format($bukuSiapKirim, 0, ',', '.') ?></p>
        <p class="text-xs text-green-100">Buku siap kirim</p>
      </div>
      <div class="bg-white/10 rounded-xl px-4 py-3 border border-white/20">
        <p class="text-2xl font-bold"><?= $pctDone ?>%</p>
        <p class="text-xs text-green-100">Distribusi selesai</p>
        <p class="text-[10px] text-green-200 mt-0.5"><?= (int) ($stats['done'] ?? 0) ?> / <?= (int) ($stats['total'] ?? 0) ?> satuan</p>
      </div>
    </div>

    <div class="mt-5 pt-4 border-t border-white/20">
      <div class="flex justify-between text-xs text-green-100 mb-1.5">
        <span>Progres keseluruhan</span>
        <span><strong class="text-white"><?= $pctDone ?>%</strong> Done</span>
      </div>
      <div class="h-2.5 rounded-full bg-white/20 overflow-hidden flex">
        <?php if ($pctDone > 0): ?>
          <div class="bg-emerald-300 h-full" style="width: <?= min(100, $pctDone) ?>%"></div>
        <?php endif; ?>
        <?php if ($pctProgress > 0): ?>
          <div class="bg-amber-300 h-full" style="width: <?= min(100 - $pctDone, $pctProgress) ?>%"></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- KPI -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <?php
    $cards = [
        ['key' => 'packing', 'dot' => 'bg-slate-400', 'border' => 'border-slate-200', 'icon' => '📦'],
        ['key' => 'delivery', 'dot' => 'bg-blue-500', 'border' => 'border-blue-200', 'icon' => '🚚'],
        ['key' => 'receive', 'dot' => 'bg-amber-500', 'border' => 'border-amber-200', 'icon' => '📋'],
        ['key' => 'done', 'dot' => 'bg-green-600', 'border' => 'border-green-200', 'icon' => '✅'],
    ];
    foreach ($cards as $c):
        $k = $c['key'];
    ?>
    <div class="bg-white rounded-xl p-4 border <?= $c['border'] ?> shadow-sm">
      <div class="flex items-center justify-between">
        <p class="text-xs text-gray-500 flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full <?= $c['dot'] ?>"></span>
          <?= sanitize(distribusiStatusLabel($k)) ?>
        </p>
        <span><?= $c['icon'] ?></span>
      </div>
      <p class="text-2xl font-bold text-green-800 mt-2"><?= (int) ($stats[$k] ?? 0) ?></p>
      <p class="text-xs text-gray-400"><?= number_format((int) ($stats['buku'][$k] ?? 0), 0, ',', '.') ?> buku</p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Aksi cepat -->
  <div class="grid sm:grid-cols-2 gap-4">
    <a href="<?= url('distribusi/?page=kirim') ?>"
       class="block rounded-2xl bg-gradient-to-br from-green-700 to-green-600 text-white p-6 shadow-md hover:shadow-lg hover:from-green-800 transition">
      <p class="text-lg font-bold">Kirim Buku</p>
      <p class="text-sm text-green-100 mt-1">Input NPSN → Packing ke Delivery + notifikasi WA</p>
      <?php if ($siapKirim > 0): ?>
        <p class="mt-3 text-xs bg-white/20 inline-block px-2 py-1 rounded"><?= $siapKirim ?> satuan siap dikirim</p>
      <?php endif; ?>
    </a>
    <a href="<?= url('distribusi/?page=terima') ?>"
       class="block rounded-2xl bg-gradient-to-br from-blue-600 to-blue-500 text-white p-6 shadow-md hover:shadow-lg hover:from-blue-700 transition">
      <p class="text-lg font-bold">Terima Buku</p>
      <p class="text-sm text-blue-100 mt-1">Upload surat jalan + catat jumlah diterima</p>
      <?php if ($deliveryCount > 0): ?>
        <p class="mt-3 text-xs bg-white/20 inline-block px-2 py-1 rounded"><?= $deliveryCount ?> menunggu penerimaan</p>
      <?php endif; ?>
    </a>
  </div>

  <!-- Charts -->
  <div class="grid lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-2xl border shadow-sm p-5">
      <h3 class="font-bold text-gray-900">Status Semua Satuan</h3>
      <p class="text-xs text-gray-500 mb-3">Gambaran umum progres distribusi</p>
      <div class="relative h-56">
        <canvas id="chartPetugasStatus"></canvas>
      </div>
    </div>

    <div class="bg-white rounded-2xl border shadow-sm p-5">
      <h3 class="font-bold text-gray-900">Fokus Tugas Petugas</h3>
      <p class="text-xs text-gray-500 mb-3">Siap kirim vs sedang antar vs selesai</p>
      <div class="relative h-56">
        <canvas id="chartPetugasTugas"></canvas>
      </div>
    </div>

    <div class="bg-white rounded-2xl border shadow-sm p-5 lg:col-span-2">
      <h3 class="font-bold text-gray-900">Volume Buku per Tahap</h3>
      <p class="text-xs text-gray-500 mb-3">Perkiraan buku LKPD di setiap status</p>
      <div class="relative h-52">
        <canvas id="chartPetugasBuku"></canvas>
      </div>
    </div>
  </div>

  <!-- Delivery aktif -->
  <div class="bg-white rounded-2xl border border-blue-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b bg-blue-50/50 flex flex-wrap items-center justify-between gap-2">
      <div>
        <h2 class="font-bold text-blue-900">Sedang Delivery (<?= $deliveryCount ?>)</h2>
        <p class="text-xs text-gray-500 mt-0.5">Satuan yang menunggu proses penerimaan & upload surat jalan</p>
      </div>
      <?php if ($deliveryCount > 0): ?>
        <a href="<?= url('distribusi/?page=terima') ?>" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg font-semibold hover:bg-blue-700">
          Buka halaman Terima
        </a>
      <?php endif; ?>
    </div>
    <?php if (empty($deliveryRows)): ?>
      <div class="p-8 text-center">
        <p class="text-gray-500 text-sm">Tidak ada pengiriman aktif saat ini.</p>
        <a href="<?= url('distribusi/?page=kirim') ?>" class="inline-block mt-3 text-sm text-green-700 font-semibold hover:underline">
          &rarr; Catat pengiriman baru di Kirim Buku
        </a>
      </div>
    <?php else: ?>
      <div class="divide-y">
        <?php foreach ($deliveryRows as $row):
            $totalBuku = satuanTotalKebutuhanBuku($row);
        ?>
          <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-gray-50">
            <div class="min-w-0">
              <p class="font-semibold text-gray-900 truncate"><?= sanitize($row['nama_lembaga'] ?? '') ?></p>
              <p class="text-xs text-gray-500">NPSN: <?= sanitize($row['npsn'] ?? '') ?> · <?= number_format($totalBuku, 0, ',', '.') ?> buku</p>
            </div>
            <a href="<?= url('distribusi/?page=terima&npsn=' . urlencode($row['npsn'] ?? '')) ?>"
               class="shrink-0 text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center font-medium">
              Proses Penerimaan
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script type="application/json" id="petugas-chart-data"><?= $chartJson ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  var el = document.getElementById('petugas-chart-data');
  if (!el || !window.Chart) return;
  var d = JSON.parse(el.textContent || '{}');
  var statusColors = ['#94a3b8', '#3b82f6', '#f59e0b', '#16a34a'];
  var tugasColors = ['#64748b', '#2563eb', '#16a34a'];

  Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
  Chart.defaults.color = '#64748b';

  new Chart(document.getElementById('chartPetugasStatus'), {
    type: 'doughnut',
    data: {
      labels: d.statusLabels || [],
      datasets: [{
        data: d.statusCounts || [],
        backgroundColor: statusColors,
        borderWidth: 2,
        borderColor: '#fff',
        hoverOffset: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '55%',
      plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12 } }
      }
    }
  });

  new Chart(document.getElementById('chartPetugasTugas'), {
    type: 'bar',
    data: {
      labels: d.tugasLabels || [],
      datasets: [{
        label: 'Jumlah Satuan',
        data: d.tugasCounts || [],
        backgroundColor: tugasColors.map(function (c) { return c + 'cc'; }),
        borderColor: tugasColors,
        borderWidth: 1,
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  });

  new Chart(document.getElementById('chartPetugasBuku'), {
    type: 'bar',
    data: {
      labels: d.statusLabels || [],
      datasets: [{
        label: 'Jumlah Buku',
        data: d.statusBuku || [],
        backgroundColor: statusColors.map(function (c) { return c + '99'; }),
        borderColor: statusColors,
        borderWidth: 1,
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: function (v) { return v.toLocaleString('id-ID'); } }
        }
      }
    }
  });
})();
</script>
