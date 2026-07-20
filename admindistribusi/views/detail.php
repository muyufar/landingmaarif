<?php declare(strict_types=1); /** @var array $satuan @var array $totalTerima @var array $totalTerimaGuru @var array $pengirimanList @var array|null $pengkinian */ ?>
<?php $kurang = satuanKurangDetail($satuan, $totalTerima, $totalTerimaGuru); ?>
<div class="mb-4"><a href="<?= url('admindistribusi/?page=list') ?>" class="text-green-700 text-sm hover:underline">← Kembali</a></div>

<div class="bg-white rounded-2xl border shadow-lg p-6 mb-6">
  <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
    <div>
      <h2 class="text-xl font-bold text-green-800"><?= sanitize($satuan['nama_lembaga'] ?? '') ?></h2>
      <p class="text-sm text-gray-500">NPSN: <?= sanitize($satuan['npsn'] ?? '') ?></p>
    </div>
    <span class="text-sm font-bold px-3 py-1 rounded-full <?= distribusiStatusBadgeClass($satuan['status'] ?? '') ?>">
      <?= sanitize(distribusiStatusLabel($satuan['status'] ?? '')) ?>
    </span>
  </div>
  <p class="text-sm text-gray-700 mb-4"><?= sanitize($satuan['alamat'] ?? '') ?></p>
  <?php if ($pengkinian): ?>
    <p class="text-xs text-gray-500 mb-4">HP Kepsek: <?= sanitize($pengkinian['nomor_hp_kepsek'] ?? '-') ?> · HP Operator: <?= sanitize($pengkinian['nomor_hp_operator'] ?? '-') ?></p>
  <?php endif; ?>
  <table class="w-full text-sm border">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-3 py-2 text-left">Kelas</th>
        <th class="px-3 py-2 text-left">Jenis</th>
        <th class="px-3 py-2">Kebutuhan</th>
        <th class="px-3 py-2">Terima</th>
        <th class="px-3 py-2">Kurang</th>
      </tr>
    </thead>
    <tbody>
      <?php for ($i = 1; $i <= 6; $i++):
        $need = (int) ($satuan['kebutuhan_kelas_' . $i] ?? 0);
        $got = $totalTerima[$i] ?? 0;
        $needGuru = (int) ($satuan['kebutuhan_guru_kelas_' . $i] ?? 0);
        $gotGuru = $totalTerimaGuru[$i] ?? 0;
      ?>
        <tr class="border-t">
          <td class="px-3 py-2" rowspan="<?= $needGuru > 0 ? 2 : 1 ?>">Kelas <?= $i ?></td>
          <td class="px-3 py-2 text-gray-600">LKPD Siswa</td>
          <td class="px-3 py-2 text-center"><?= $need ?></td>
          <td class="px-3 py-2 text-center"><?= $got ?></td>
          <td class="px-3 py-2 text-center text-amber-700 font-semibold"><?= max(0, $need - $got) ?></td>
        </tr>
        <?php if ($needGuru > 0): ?>
        <tr class="border-t bg-gray-50/50">
          <td class="px-3 py-2 text-gray-600">Buku Guru</td>
          <td class="px-3 py-2 text-center"><?= $needGuru ?></td>
          <td class="px-3 py-2 text-center"><?= $gotGuru ?></td>
          <td class="px-3 py-2 text-center text-amber-700 font-semibold"><?= max(0, $needGuru - $gotGuru) ?></td>
        </tr>
        <?php endif; ?>
      <?php endfor; ?>
    </tbody>
  </table>
</div>

<div class="bg-white rounded-2xl border shadow-lg overflow-hidden">
  <div class="px-5 py-4 border-b font-bold">Riwayat Pengiriman</div>
  <?php if (empty($pengirimanList)): ?>
    <p class="p-5 text-gray-500 text-sm">Belum ada pengiriman.</p>
  <?php else: ?>
    <div class="divide-y">
      <?php foreach ($pengirimanList as $p): ?>
        <div class="px-5 py-4 text-sm">
          <p class="font-semibold">#<?= (int) $p['id'] ?> · <?= sanitize($p['nama_petugas'] ?? '') ?> · <?= sanitize($p['status'] ?? '') ?></p>
          <p class="text-xs text-gray-500"><?= sanitize($p['dispatched_at'] ?? '') ?><?= !empty($p['received_at']) ? ' → ' . sanitize($p['received_at']) : '' ?></p>
          <?php if (!empty($p['catatan'])): ?><p class="text-xs mt-1 italic"><?= sanitize($p['catatan']) ?></p><?php endif; ?>
          <?php if (($p['status'] ?? '') !== 'delivery'): ?>
            <p class="text-xs mt-1">LKPD: <?php for ($i = 1; $i <= 6; $i++): ?>K<?= $i ?>=<?= (int) ($p['terima_kelas_' . $i] ?? 0) ?> <?php endfor; ?></p>
            <p class="text-xs mt-0.5">Guru: <?php for ($i = 1; $i <= 6; $i++): ?>K<?= $i ?>=<?= (int) ($p['terima_guru_kelas_' . $i] ?? 0) ?> <?php endfor; ?></p>
            <div class="mt-2 flex gap-3">
              <?php if (!empty($p['file_surat_jalan_distributor'])): ?>
                <a class="text-green-700 underline" href="<?= url('admindistribusi/?download_file=distributor&pengiriman_id=' . (int) $p['id']) ?>" target="_blank">SJ Distributor</a>
              <?php endif; ?>
              <?php if (!empty($p['file_surat_jalan_sekolah'])): ?>
                <a class="text-green-700 underline" href="<?= url('admindistribusi/?download_file=sekolah&pengiriman_id=' . (int) $p['id']) ?>" target="_blank">SJ Sekolah</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
