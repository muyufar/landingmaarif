<?php declare(strict_types=1); /** @var array $petugasList */ ?>
<div class="grid lg:grid-cols-2 gap-6">
  <div class="bg-white rounded-2xl border shadow-lg p-6">
    <h2 class="text-lg font-bold text-green-800 mb-4">Buat Akun Petugas Distributor</h2>
    <form method="post" class="space-y-4">
      <input type="hidden" name="create_petugas" value="1">
      <div>
        <label class="block text-sm font-semibold mb-1">Nama Lengkap</label>
        <input type="text" name="nama" required class="w-full rounded-lg border px-4 py-2">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Username</label>
        <input type="text" name="username" required class="w-full rounded-lg border px-4 py-2" autocomplete="off">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Password</label>
        <input type="password" name="password" required minlength="6" class="w-full rounded-lg border px-4 py-2">
      </div>
      <button type="submit" class="w-full bg-green-700 text-white py-2.5 rounded-lg font-semibold">Simpan Akun</button>
    </form>
  </div>

  <div class="bg-white rounded-2xl border shadow-lg overflow-hidden">
    <div class="px-5 py-4 border-b font-bold">Daftar Petugas</div>
    <div class="divide-y">
      <?php foreach ($petugasList as $p): ?>
        <?php if (($p['role'] ?? '') !== 'petugas') continue; ?>
        <div class="px-5 py-4 flex items-center justify-between gap-3 text-sm">
          <div>
            <p class="font-semibold"><?= sanitize($p['nama'] ?? '') ?></p>
            <p class="text-xs text-gray-500">@<?= sanitize($p['username'] ?? '') ?></p>
          </div>
          <form method="post">
            <input type="hidden" name="toggle_petugas_id" value="<?= (int) $p['id'] ?>">
            <input type="hidden" name="toggle_action" value="<?= (int) ($p['aktif'] ?? 0) ? 'nonaktifkan' : 'aktifkan' ?>">
            <button type="submit" class="text-xs px-3 py-1 rounded-lg <?= (int) ($p['aktif'] ?? 0) ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
              <?= (int) ($p['aktif'] ?? 0) ? 'Nonaktifkan' : 'Aktifkan' ?>
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
