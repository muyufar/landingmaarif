<?php

declare(strict_types=1);

/** @var array $stats */
$labels = fn(array $data): array => array_keys($data);
$values = fn(array $data): array => array_values($data);
?>
<div class="mb-6">
  <h2 class="text-2xl font-bold text-green-800">Dashboard Utama</h2>
  <p class="text-sm text-gray-500 mt-1">Ringkasan pendaftaran RAKERDINMA 2026 — Total <strong><?= $stats['total'] ?></strong> peserta</p>
</div>

<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
  <div class="bg-white rounded-2xl shadow border border-green-100 p-5 xl:col-span-2">
    <h3 class="font-semibold text-green-800 mb-4">Pengisian per Kecamatan</h3>
    <div class="h-72"><canvas id="chartKecamatan"></canvas></div>
  </div>
  <div class="bg-white rounded-2xl shadow border border-green-100 p-5">
    <h3 class="font-semibold text-green-800 mb-4">Jenis Lembaga</h3>
    <div class="h-72"><canvas id="chartLembaga"></canvas></div>
  </div>
  <div class="bg-white rounded-2xl shadow border border-green-100 p-5">
    <h3 class="font-semibold text-green-800 mb-4">Jabatan</h3>
    <div class="h-64"><canvas id="chartJabatan"></canvas></div>
  </div>
  <div class="bg-white rounded-2xl shadow border border-green-100 p-5">
    <h3 class="font-semibold text-green-800 mb-4">Transportasi</h3>
    <div class="h-64"><canvas id="chartTransportasi"></canvas></div>
  </div>
  <div class="bg-white rounded-2xl shadow border border-green-100 p-5">
    <h3 class="font-semibold text-green-800 mb-4">Kelompok Umur</h3>
    <div class="h-64"><canvas id="chartUmur"></canvas></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const colors = ['#166534','#15803d','#16a34a','#22c55e','#4ade80','#86efac','#059669','#047857','#065f46','#10b981','#34d399','#6ee7b7','#a7f3d0','#064e3b','#14532d'];
function makeChart(id, type, labels, data, opts = {}) {
  return new Chart(document.getElementById(id), {
    type,
    data: {
      labels,
      datasets: [{ data, backgroundColor: colors.slice(0, labels.length), borderWidth: 1 }]
    },
    options: Object.assign({
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: type === 'bar' ? 'top' : 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
    }, opts)
  });
}
makeChart('chartKecamatan', 'bar', <?= json_encode($labels($stats['kecamatan'])) ?>, <?= json_encode($values($stats['kecamatan'])) ?>, {
  indexAxis: 'y',
  scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } },
  plugins: { legend: { display: false } }
});
makeChart('chartLembaga', 'doughnut', <?= json_encode($labels($stats['lembaga'])) ?>, <?= json_encode($values($stats['lembaga'])) ?>);
makeChart('chartJabatan', 'pie', <?= json_encode($labels($stats['jabatan'])) ?>, <?= json_encode($values($stats['jabatan'])) ?>);
makeChart('chartTransportasi', 'pie', <?= json_encode($labels($stats['transportasi'])) ?>, <?= json_encode($values($stats['transportasi'])) ?>);
makeChart('chartUmur', 'bar', <?= json_encode($labels($stats['umur'])) ?>, <?= json_encode($values($stats['umur'])) ?>, {
  scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
  plugins: { legend: { display: false } }
});
</script>
