<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Layanan | LP Ma'arif NU Kabupaten Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-4">
        <img src="<?= url('image/logo.png') ?>" alt="Logo LP Ma'arif NU" class="w-12 h-12 rounded-full bg-white p-1">
        <div>
          <h1 class="text-lg md:text-xl font-bold">LP Ma'arif NU Kabupaten Magelang</h1>
          <p class="text-sm text-green-100">Dashboard Layanan Online</p>
        </div>
      </div>
      <a href="<?= url() ?>" class="text-sm bg-green-900 hover:bg-green-950 px-4 py-2 rounded-lg transition whitespace-nowrap">← Beranda</a>
    </div>
  </header>

  <main class="max-w-5xl mx-auto px-6 py-12">
    <div class="text-center mb-10">
      <h2 class="text-3xl font-bold text-green-800 mb-3">Pilih Layanan</h2>
      <p class="text-gray-600">Akses formulir pendaftaran dan pemesanan melalui menu di bawah ini.</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <a href="<?= url('rakerdinma') ?>"
         class="group bg-white rounded-2xl shadow-lg border border-green-100 p-8 hover:shadow-xl hover:border-green-300 transition">
        <div class="text-4xl mb-4">📋</div>
        <h3 class="text-xl font-bold text-green-800 group-hover:text-green-700 mb-2">Pendaftaran RAKERDINMA 2026</h3>
        <p class="text-gray-600 text-sm leading-relaxed">
          Formulir pendaftaran Rapat Kerja Dinas Ma'arif LP Ma'arif NU Kabupaten Magelang Tahun 2026.
        </p>
        <span class="inline-block mt-5 text-green-700 font-semibold text-sm">Buka Formulir →</span>
      </a>

      <a href="<?= url('pemesanan') ?>"
         class="group bg-white rounded-2xl shadow-lg border border-green-100 p-8 hover:shadow-xl hover:border-green-300 transition">
        <div class="text-4xl mb-4">📚</div>
        <h3 class="text-xl font-bold text-green-800 group-hover:text-green-700 mb-2">Pemesanan Layanan</h3>
        <p class="text-gray-600 text-sm leading-relaxed">
          MOPDIK, Batik Ma'arif, Buku Ke-NU-an, dan Buku Tulis Karakter Aswaja untuk lembaga binaan LP Ma'arif NU.
        </p>
        <span class="inline-block mt-5 text-green-700 font-semibold text-sm">Buka Menu Pemesanan →</span>
      </a>

      <a href="<?= url('pengkinian') ?>"
         class="group bg-white rounded-2xl shadow-lg border border-green-100 p-8 hover:shadow-xl hover:border-green-300 transition">
        <div class="text-4xl mb-4">📱</div>
        <h3 class="text-xl font-bold text-green-800 group-hover:text-green-700 mb-2">Pembaruan Data Satuan Pendidikan</h3>
        <p class="text-gray-600 text-sm leading-relaxed">
          Perbarui nomor HP Kepala Sekolah dan Operator beserta alamat satuan pendidikan binaan LP Ma'arif NU.
        </p>
        <span class="inline-block mt-5 text-green-700 font-semibold text-sm">Buka Formulir →</span>
      </a>

      <a href="<?= url('distribusi') ?>"
         class="group bg-white rounded-2xl shadow-lg border border-green-100 p-8 hover:shadow-xl hover:border-green-300 transition">
        <div class="text-4xl mb-4">📦</div>
        <h3 class="text-xl font-bold text-green-800 group-hover:text-green-700 mb-2">Tracking Distribusi LKPD</h3>
        <p class="text-gray-600 text-sm leading-relaxed">
          Monitoring pendistribusian buku ajar LKPD dari distributor ke satuan pendidikan MI Ma'arif NU Magelang.
        </p>
        <span class="inline-block mt-5 text-green-700 font-semibold text-sm">Portal Petugas →</span>
      </a>
    </div>
  </main>

  <footer class="bg-green-900 text-green-100 py-6 mt-auto">
    <div class="max-w-5xl mx-auto px-6 text-center text-sm">
      © 2026 LP Ma'arif NU Kabupaten Magelang
    </div>
  </footer>

</body>
</html>
