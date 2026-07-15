<?php declare(strict_types=1); /** @var array $rows @var string $search @var string $statusFilter @var string $kecamatanFilter @var array $kecamatanOptions */ ?>
<div class="bg-white rounded-2xl border shadow-lg overflow-hidden">
  <div class="p-5 border-b flex flex-wrap gap-2 justify-between">
    <form method="get" class="flex flex-wrap gap-2 flex-1">
      <input type="hidden" name="page" value="list">
      <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Cari..." class="rounded-lg border px-3 py-2 text-sm flex-1 min-w-[180px]">
      <select name="kecamatan" class="rounded-lg border px-3 py-2 text-sm min-w-[160px]">
        <option value="">Semua Kecamatan</option>
        <?php foreach ($kecamatanOptions as $opt): ?>
          <option value="<?= sanitize($opt) ?>" <?= $kecamatanFilter === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="rounded-lg border px-3 py-2 text-sm">
        <option value="">Semua Status</option>
        <?php foreach ([DIST_STATUS_PACKING, DIST_STATUS_DELIVERY, DIST_STATUS_RECEIVE, DIST_STATUS_DONE] as $st): ?>
          <option value="<?= sanitize($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= sanitize(distribusiStatusLabel($st)) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="bg-green-700 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
      <?php if ($search !== '' || $statusFilter !== '' || $kecamatanFilter !== ''): ?>
        <a href="<?= url('admindistribusi/?page=list') ?>" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm">Reset</a>
      <?php endif; ?>
    </form>
    <a href="<?= url('admindistribusi/?export=csv') ?>" class="bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">Export CSV</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-green-50 text-xs uppercase">
        <tr>
          <th class="px-4 py-3 text-left">NPSN</th>
          <th class="px-4 py-3 text-left">Lembaga</th>
          <th class="px-4 py-3">K1-K6</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php foreach ($rows as $row): ?>
          <tr>
            <td class="px-4 py-3 font-mono text-xs"><?= sanitize($row['npsn'] ?? '') ?></td>
            <td class="px-4 py-3"><?= sanitize($row['nama_lembaga'] ?? '') ?></td>
            <td class="px-4 py-3 text-xs text-center whitespace-nowrap">
              <?php for ($i = 1; $i <= 6; $i++): ?><?= (int) ($row['kebutuhan_kelas_' . $i] ?? 0) ?><?= $i < 6 ? '/' : '' ?><?php endfor; ?>
            </td>
            <td class="px-4 py-3 text-center">
              <span class="text-xs font-bold px-2 py-1 rounded-full <?= distribusiStatusBadgeClass($row['status'] ?? '') ?>">
                <?= sanitize(distribusiStatusLabel($row['status'] ?? '')) ?>
              </span>
            </td>
            <td class="px-4 py-3"><a href="<?= url('admindistribusi/?page=detail&id=' . (int) $row['id']) ?>" class="text-green-700 hover:underline">Detail</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
