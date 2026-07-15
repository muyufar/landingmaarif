<?php

declare(strict_types=1);

function distribusiNavClass(string $page, string $current): string
{
    return $page === $current ? 'bg-green-700 text-white' : 'text-green-100 hover:bg-green-700/60';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= sanitize($pageTitle ?? 'Distribusi LKPD') ?> | LP Ma'arif NU</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-5xl mx-auto px-4 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold">Distribusi LKPD MI Ma'arif</h1>
        <p class="text-sm text-green-100"><?= sanitize($petugas['nama'] ?? 'Petugas') ?></p>
      </div>
      <?php if (!empty($petugas)): ?>
      <nav class="flex flex-wrap gap-2 text-sm">
        <a href="<?= url('distribusi/?page=dashboard') ?>" class="px-3 py-2 rounded-lg <?= distribusiNavClass('dashboard', $currentPage ?? '') ?>">Dashboard</a>
        <a href="<?= url('distribusi/?page=kirim') ?>" class="px-3 py-2 rounded-lg <?= distribusiNavClass('kirim', $currentPage ?? '') ?>">Kirim Buku</a>
        <a href="<?= url('distribusi/?page=terima') ?>" class="px-3 py-2 rounded-lg <?= distribusiNavClass('terima', $currentPage ?? '') ?>">Terima Buku</a>
        <a href="<?= url('distribusi/?page=list') ?>" class="px-3 py-2 rounded-lg <?= distribusiNavClass('list', $currentPage ?? '') ?>">List Satuan</a>
        <a href="<?= url('distribusi/?logout=1') ?>" class="px-3 py-2 rounded-lg bg-green-900">Logout</a>
      </nav>
      <?php endif; ?>
    </div>
  </header>
  <main class="max-w-5xl mx-auto px-4 py-8">
    <?php if (!empty($flashMessage)): ?>
      <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm"><?= sanitize($flashMessage) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
      <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm"><?= sanitize($flashError) ?></div>
    <?php endif; ?>
    <?= $content ?? '' ?>
  </main>
  <?php if (!empty($extraScripts)): ?><?= $extraScripts ?><?php endif; ?>
</body>
</html>
