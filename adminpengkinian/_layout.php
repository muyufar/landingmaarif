<?php

declare(strict_types=1);

function pengkinianNavClass(string $page, string $current): string
{
    return $page === $current
        ? 'bg-green-700 text-white'
        : 'text-green-100 hover:bg-green-700/60 hover:text-white';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= sanitize($pageTitle ?? 'Admin Pembaruan Data') ?> | LP Ma'arif NU Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <?php if (!empty($extraHead)): ?><?= $extraHead ?><?php endif; ?>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-4">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-4">
          <img src="<?= url('image/logo.png') ?>" alt="Logo" class="w-12 h-12 rounded-full bg-white p-1">
          <div>
            <h1 class="text-lg md:text-xl font-bold">Admin Pembaruan Data Satuan</h1>
            <p class="text-sm text-green-100">HP Kepsek & Operator</p>
          </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <a href="<?= url('dashboard') ?>"
             class="text-sm bg-green-900 hover:bg-green-950 px-4 py-2 rounded-lg transition">Layanan</a>
          <a href="<?= url('pengkinian/') ?>"
             class="text-sm bg-green-900 hover:bg-green-950 px-4 py-2 rounded-lg transition">Form Publik</a>
          <?php if (isPengkinianAdminLoggedIn()): ?>
          <nav class="flex rounded-lg overflow-hidden border border-green-700 text-sm font-medium">
            <a href="<?= url('adminpengkinian/?page=dashboard') ?>"
               class="px-4 py-2 transition <?= pengkinianNavClass('dashboard', $currentPage ?? '') ?>">Dashboard</a>
            <a href="<?= url('adminpengkinian/?page=list') ?>"
               class="px-4 py-2 transition <?= pengkinianNavClass('list', $currentPage ?? '') ?>">List Data</a>
          </nav>
          <a href="<?= url('adminpengkinian/?logout=1') ?>"
             class="text-sm bg-green-900 hover:bg-green-950 px-4 py-2 rounded-lg transition">Logout</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <main class="max-w-[1400px] mx-auto px-4 sm:px-6 py-8">
    <?php if (!empty($flashMessage)): ?>
      <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm">
        <?= sanitize($flashMessage) ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
      <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
        <?= sanitize($flashError) ?>
      </div>
    <?php endif; ?>
    <?= $content ?? '' ?>
  </main>

  <?php if (!empty($extraScripts)): ?><?= $extraScripts ?><?php endif; ?>
</body>
</html>
