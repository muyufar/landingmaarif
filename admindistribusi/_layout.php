<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= sanitize($pageTitle ?? 'Admin Distribusi') ?> | LP Ma'arif NU</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold">Admin Distribusi LKPD</h1>
        <p class="text-sm text-green-100">Super Admin LP Ma'arif NU Kab. Magelang</p>
      </div>
      <?php if (isDistribusiSuperAdminLoggedIn()): ?>
      <nav class="flex flex-wrap gap-2 text-sm">
        <a href="<?= url('admindistribusi/?page=dashboard') ?>" class="px-3 py-2 rounded-lg bg-green-900/50 hover:bg-green-700">Dashboard</a>
        <a href="<?= url('admindistribusi/?page=import') ?>" class="px-3 py-2 rounded-lg bg-green-900/50 hover:bg-green-700">Import Data</a>
        <a href="<?= url('admindistribusi/?page=list') ?>" class="px-3 py-2 rounded-lg bg-green-900/50 hover:bg-green-700">Monitoring</a>
        <a href="<?= url('admindistribusi/?page=petugas') ?>" class="px-3 py-2 rounded-lg bg-green-900/50 hover:bg-green-700">Akun Petugas</a>
        <a href="<?= url('distribusi/') ?>" class="px-3 py-2 rounded-lg bg-green-900/50 hover:bg-green-700">Portal Petugas</a>
        <a href="<?= url('admindistribusi/?logout=1') ?>" class="px-3 py-2 rounded-lg bg-green-950">Logout</a>
      </nav>
      <?php endif; ?>
    </div>
  </header>
  <main class="max-w-6xl mx-auto px-4 py-8">
    <?php if (!empty($flashMessage)): ?><div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm"><?= sanitize($flashMessage) ?></div><?php endif; ?>
    <?php if (!empty($flashError)): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm"><?= sanitize($flashError) ?></div><?php endif; ?>
    <?= $content ?? '' ?>
  </main>
</body>
</html>
