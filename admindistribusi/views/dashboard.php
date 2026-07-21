<?php declare(strict_types=1);
/** @var array $stats */
$statusKeys = ['packing', 'delivery', 'receive', 'done'];
$statusLabels = array_map('distribusiStatusLabel', $statusKeys);
$statusCounts = array_map(fn ($k) => (int) ($stats[$k] ?? 0), $statusKeys);
$statusBuku = array_map(fn ($k) => (int) ($stats['buku'][$k] ?? 0), $statusKeys);
$kelasLabels = ['K1', 'K2', 'K3', 'K4', 'K5', 'K6'];
$kelasData = [];
for ($i = 1; $i <= 6; $i++) {
    $kelasData[] = (int) ($stats['kelas'][$i] ?? 0);
}
$pctDone = (float) ($stats['pct_done'] ?? 0);
$pctProgress = (float) ($stats['pct_progress'] ?? 0);
$pctPacking = $stats['total'] > 0 ? round(($stats['packing'] / $stats['total']) * 100, 1) : 0;
$chartJson = json_encode([
    'statusLabels' => $statusLabels,
    'statusCounts' => $statusCounts,
    'statusBuku' => $statusBuku,
    'kelasLabels' => $kelasLabels,
    'kelasData' => $kelasData,
    'bukuLkpd' => (int) ($stats['buku_lkpd'] ?? 0),
    'bukuGuru' => (int) ($stats['total_buku_guru'] ?? 0),
], JSON_UNESCAPED_UNICODE);
?>
<div class="space-y-6">
  <!-- Hero ringkasan -->
  <div class="rounded-2xl bg-gradient-to-br from-green-800 via-green-700 to-emerald-600 text-white p-6 lg:p-8 shadow-lg">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
      <div>
        <p class="text-green-100 text-sm font-medium uppercase tracking-wide">Ringkasan Distribusi LKPD</p>
        <h2 class="text-2xl lg:text-3xl font-bold mt-1">Monitoring Kab. Magelang</h2>
        <p class="text-green-100 text-sm mt-2 max-w-xl">
          Pantau progres pengiriman buku LKPD ke <?= number_format((int) ($stats['total'] ?? 0), 0, ',', '.') ?> satuan pendidikan MI Ma'arif NU.
        </p>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 lg:gap-4 shrink-0">
        <div class="bg-white/10 backdrop-blur rounded-xl px-4 py-3 text-center border border-white/20">
          <p class="text-2xl lg:text-3xl font-bold"><?= number_format((int) ($stats['total'] ?? 0), 0, ',', '.') ?></p>
          <p class="text-xs text-green-100 mt-0.5">Satuan</p>
        </div>
        <div class="bg-white/10 backdrop-blur rounded-xl px-4 py-3 text-center border border-white/20">
          <p class="text-2xl lg:text-3xl font-bold"><?= number_format((int) ($stats['total_buku'] ?? 0), 0, ',', '.') ?></p>
          <p class="text-xs text-green-100 mt-0.5">Total Buku</p>
        </div>
        <div class="bg-white/10 backdrop-blur rounded-xl px-4 py-3 text-center border border-white/20 col-span-2 sm:col-span-1">
          <p class="text-2xl lg:text-3xl font-bold"><?= number_format((int) ($stats['total_siswa'] ?? 0), 0, ',', '.') ?></p>
          <p class="text-xs text-green-100 mt-0.5">Total Siswa</p>
        </div>
      </div>
    </div>

    <!-- Progress pipeline -->
    <div class="mt-6 pt-5 border-t border-white/20">
      <div class="flex justify-between text-xs text-green-100 mb-2">
        <span>Progres distribusi satuan</span>
        <span><strong class="text-white"><?= $pctDone ?>%</strong> selesai (Done)</span>
      </div>
      <div class="h-3 rounded-full bg-white/20 overflow-hidden flex">
        <?php if ($pctDone > 0): ?>
          <div class="bg-emerald-300 h-full" style="width: <?= min(100, $pctDone) ?>%" title="Done"></div>
        <?php endif; ?>
        <?php if ($pctProgress > 0): ?>
          <div class="bg-amber-300 h-full" style="width: <?= min(100 - $pctDone, $pctProgress) ?>%" title="Delivery + Receive"></div>
        <?php endif; ?>
        <?php $pctRest = max(0, 100 - $pctDone - $pctProgress); ?>
        <?php if ($pctRest > 0): ?>
          <div class="bg-white/40 h-full flex-1" title="Packing"></div>
        <?php endif; ?>
      </div>
      <div class="flex flex-wrap gap-4 mt-3 text-xs text-green-100">
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-300"></span> Done (<?= (int) ($stats['done'] ?? 0) ?>)</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-300"></span> Proses (<?= (int) ($stats['delivery'] ?? 0) + (int) ($stats['receive'] ?? 0) ?>)</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-white/60"></span> Packing (<?= (int) ($stats['packing'] ?? 0) ?>)</span>
      </div>
    </div>
  </div>

  <!-- KPI cards -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php
    $cards = [
        ['key' => 'packing', 'color' => 'border-slate-200', 'dot' => 'bg-slate-400', 'icon' => '📦'],
        ['key' => 'delivery', 'color' => 'border-blue-200', 'dot' => 'bg-blue-500', 'icon' => '🚚'],
        ['key' => 'receive', 'color' => 'border-amber-200', 'dot' => 'bg-amber-500', 'icon' => '📋'],
        ['key' => 'done', 'color' => 'border-green-200', 'dot' => 'bg-green-600', 'icon' => '✅'],
    ];
    foreach ($cards as $c):
        $k = $c['key'];
    ?>
    <div class="bg-white rounded-xl p-4 border <?= $c['color'] ?> shadow-sm hover:shadow-md transition-shadow">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-xs text-gray-500 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full <?= $c['dot'] ?>"></span>
            <?= sanitize(distribusiStatusLabel($k)) ?>
          </p>
          <p class="text-2xl font-bold text-gray-900 mt-1"><?= (int) ($stats[$k] ?? 0) ?></p>
          <p class="text-xs text-gray-400 mt-0.5">satuan</p>
        </div>
        <span class="text-xl opacity-80"><?= $c['icon'] ?></span>
      </div>
      <p class="text-xs text-green-700 font-medium mt-3 pt-3 border-t border-gray-100">
        <?= number_format((int) ($stats['buku'][$k] ?? 0), 0, ',', '.') ?> buku
      </p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Charts -->
  <div class="grid lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-2xl border shadow-sm p-5">
      <h3 class="font-bold text-gray-900 mb-1">Status Satuan Pendidikan</h3>
      <p class="text-xs text-gray-500 mb-4">Perbandingan jumlah MI per tahap distribusi</p>
      <div class="relative h-64 flex items-center justify-center">
        <canvas id="chartStatus"></canvas>
      </div>
    </div>

    <div class="bg-white rounded-2xl border shadow-sm p-5">
      <h3 class="font-bold text-gray-900 mb-1">Volume Buku per Status</h3>
      <p class="text-xs text-gray-500 mb-4">Total buku LKPD pada setiap tahap</p>
      <div class="relative h-64">
        <canvas id="chartBukuStatus"></canvas>
      </div>
    </div>

    <div class="bg-white rounded-2xl border shadow-sm p-5">
      <h3 class="font-bold text-gray-900 mb-1">Total Siswa per Kelas</h3>
      <p class="text-xs text-gray-500 mb-4">Akumulasi jumlah siswa K1–K6 seluruh satuan</p>
      <div class="relative h-64">
        <canvas id="chartKelas"></canvas>
      </div>
    </div>

    <div class="bg-white rounded-2xl border shadow-sm p-5">
      <h3 class="font-bold text-gray-900 mb-1">Komposisi Kebutuhan Buku</h3>
      <p class="text-xs text-gray-500 mb-4">LKPD siswa (mapel × siswa) vs buku guru</p>
      <div class="relative h-64 flex items-center justify-center">
        <canvas id="chartKomposisi"></canvas>
      </div>
      <div class="grid grid-cols-2 gap-3 mt-2 text-center text-xs">
        <div class="rounded-lg bg-green-50 py-2 px-3">
          <p class="text-gray-500">LKPD Siswa</p>
          <p class="font-bold text-green-800"><?= number_format((int) ($stats['buku_lkpd'] ?? 0), 0, ',', '.') ?></p>
        </div>
        <div class="rounded-lg bg-purple-50 py-2 px-3">
          <p class="text-gray-500">Buku Guru</p>
          <p class="font-bold text-purple-800"><?= number_format((int) ($stats['total_buku_guru'] ?? 0), 0, ',', '.') ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick actions -->
  <div>
    <h3 class="font-bold text-gray-900 mb-3">Aksi Cepat</h3>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <a href="<?= url('admindistribusi/?page=create') ?>"
         class="group flex items-center gap-3 bg-white border border-green-200 rounded-xl p-4 hover:shadow-md hover:border-green-400 transition-all">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 text-lg group-hover:bg-green-200">➕</span>
        <div>
          <p class="font-semibold text-sm text-gray-900">Tambah Satuan</p>
          <p class="text-xs text-gray-500">Input manual</p>
        </div>
      </a>
      <a href="<?= url('admindistribusi/?page=import') ?>"
         class="group flex items-center gap-3 bg-white border rounded-xl p-4 hover:shadow-md hover:border-green-300 transition-all">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-lg group-hover:bg-blue-200">📥</span>
        <div>
          <p class="font-semibold text-sm text-gray-900">Import Excel</p>
          <p class="text-xs text-gray-500">Upload massal</p>
        </div>
      </a>
      <a href="<?= url('admindistribusi/?page=list') ?>"
         class="group flex items-center gap-3 bg-white border rounded-xl p-4 hover:shadow-md hover:border-green-300 transition-all">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-lg group-hover:bg-amber-200">📊</span>
        <div>
          <p class="font-semibold text-sm text-gray-900">Data Satuan</p>
          <p class="text-xs text-gray-500">CRUD & monitoring</p>
        </div>
      </a>
      <a href="<?= url('admindistribusi/?page=petugas') ?>"
         class="group flex items-center gap-3 bg-white border rounded-xl p-4 hover:shadow-md hover:border-green-300 transition-all">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-lg group-hover:bg-purple-200">👤</span>
        <div>
          <p class="font-semibold text-sm text-gray-900">Akun Petugas</p>
          <p class="text-xs text-gray-500">Kelola distributor</p>
        </div>
      </a>
    </div>
  </div>
</div>

<script type="application/json" id="dashboard-chart-data"><?= $chartJson ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  var el = document.getElementById('dashboard-chart-data');
  if (!el || !window.Chart) return;
  var d = JSON.parse(el.textContent || '{}');

  Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
  Chart.defaults.color = '#64748b';

  var statusColors = ['#94a3b8', '#3b82f6', '#f59e0b', '#16a34a'];

  new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
      labels: d.statusLabels || [],
      datasets: [{
        data: d.statusCounts || [],
        backgroundColor: statusColors,
        borderWidth: 2,
        borderColor: '#fff',
        hoverOffset: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '58%',
      plugins: {
        legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' } },
        tooltip: {
          callbacks: {
            label: function (ctx) {
              var total = (d.statusCounts || []).reduce(function (a, b) { return a + b; }, 0) || 1;
              var pct = Math.round((ctx.raw / total) * 1000) / 10;
              return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
            }
          }
        }
      }
    }
  });

  new Chart(document.getElementById('chartBukuStatus'), {
    type: 'bar',
    data: {
      labels: d.statusLabels || [],
      datasets: [{
        label: 'Jumlah Buku',
        data: d.statusBuku || [],
        backgroundColor: statusColors.map(function (c) { return c + 'cc'; }),
        borderColor: statusColors,
        borderWidth: 1,
        borderRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: {
        x: {
          beginAtZero: true,
          ticks: { callback: function (v) { return v.toLocaleString('id-ID'); } }
        }
      }
    }
  });

  new Chart(document.getElementById('chartKelas'), {
    type: 'bar',
    data: {
      labels: d.kelasLabels || [],
      datasets: [{
        label: 'Jumlah Siswa',
        data: d.kelasData || [],
        backgroundColor: 'rgba(22, 163, 74, 0.75)',
        borderColor: '#15803d',
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

  new Chart(document.getElementById('chartKomposisi'), {
    type: 'pie',
    data: {
      labels: ['LKPD Siswa', 'Buku Guru'],
      datasets: [{
        data: [d.bukuLkpd || 0, d.bukuGuru || 0],
        backgroundColor: ['#16a34a', '#9333ea'],
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true } },
        tooltip: {
          callbacks: {
            label: function (ctx) {
              var sum = (d.bukuLkpd || 0) + (d.bukuGuru || 0) || 1;
              var pct = Math.round((ctx.raw / sum) * 1000) / 10;
              return ctx.label + ': ' + ctx.raw.toLocaleString('id-ID') + ' (' + pct + '%)';
            }
          }
        }
      }
    }
  });
})();
</script>
