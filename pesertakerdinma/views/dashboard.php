<?php

declare(strict_types=1);

/** @var array $stats */
$labels = fn(array $data): array => array_keys($data);
$values = fn(array $data): array => array_values($data);
?>
<div class="bg-white rounded-2xl shadow border border-green-100 p-5 sm:p-6 mb-6">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">
    <div>
      <h2 class="text-2xl font-bold text-green-800">Dashboard Utama</h2>
      <p class="text-sm text-gray-500 mt-1">Ringkasan pendaftaran RAKERDINMA 2026</p>
    </div>
    <div class="flex items-center gap-4 md:pl-6 md:border-l md:border-green-100 shrink-0">
      <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-700">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Peserta</p>
        <p class="text-4xl sm:text-5xl font-extrabold text-green-700 leading-none tabular-nums mt-0.5"><?= (int) $stats['total'] ?></p>
        <p class="text-xs text-gray-500 mt-1">terdaftar</p>
      </div>
    </div>
  </div>
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
