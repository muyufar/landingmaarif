<?php declare(strict_types=1); /** @var array $rows @var string $search @var string $statusFilter */ ?>
<div class="bg-white rounded-2xl border border-green-100 shadow-lg overflow-hidden">
  <div class="p-5 border-b">
    <form method="get" class="flex flex-wrap gap-2">
      <input type="hidden" name="page" value="list">
      <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Cari NPSN, nama, alamat..."
             class="flex-1 min-w-[200px] rounded-lg border px-3 py-2 text-sm">
      <select name="status" class="rounded-lg border px-3 py-2 text-sm">
        <option value="">Semua Status</option>
        <?php foreach ([DIST_STATUS_PACKING, DIST_STATUS_DELIVERY, DIST_STATUS_RECEIVE, DIST_STATUS_DONE] as $st): ?>
          <option value="<?= sanitize($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= sanitize(distribusiStatusLabel($st)) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="bg-green-700 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
    </form>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-green-50 text-xs uppercase">
        <tr>
          <th class="px-4 py-3 text-left">NPSN</th>
          <th class="px-4 py-3 text-left">Lembaga</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <?php foreach ($rows as $row): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 font-mono text-xs"><?= sanitize($row['npsn'] ?? '') ?></td>
            <td class="px-4 py-3">
              <p class="font-semibold"><?= sanitize($row['nama_lembaga'] ?? '') ?></p>
              <p class="text-xs text-gray-500 truncate max-w-xs"><?= sanitize($row['alamat'] ?? '') ?></p>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs font-bold px-2 py-1 rounded-full <?= distribusiStatusBadgeClass($row['status'] ?? '') ?>">
                <?= sanitize(distribusiStatusLabel($row['status'] ?? '')) ?>
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <a href="<?= url('distribusi/?page=detail&id=' . (int) $row['id']) ?>" class="text-green-700 hover:underline">Detail</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
