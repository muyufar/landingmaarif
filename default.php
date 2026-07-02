<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LP Ma'arif NU Kabupaten Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">

  <!-- Header -->
  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <img src="<?= url('image/logo.png') ?>" alt="Logo LP Ma'arif NU" class="w-14 h-14 rounded-full bg-white p-1">
        <div>
          <h1 class="text-xl md:text-2xl font-bold">LP Ma'arif NU Kabupaten Magelang</h1>
          <p class="text-sm text-green-100">Lembaga Pendidikan Nahdlatul Ulama</p>
        </div>
      </div>
      <nav class="hidden md:flex space-x-6 text-sm font-medium">
        <a href="#beranda" class="hover:text-yellow-300">Beranda</a>
        <a href="#tentang" class="hover:text-yellow-300">Tentang</a>
        <a href="#program" class="hover:text-yellow-300">Program</a>
        <a href="<?= url('dashboard') ?>" class="hover:text-yellow-300">Layanan Online</a>
        <a href="#kontak" class="hover:text-yellow-300">Kontak</a>
      </nav>
    </div>
  </header>

  <!-- Hero Section -->
  <section id="beranda" class="bg-gradient-to-r from-green-700 to-green-900 text-white">
    <div class="max-w-7xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-10 items-center">
      <div>
        <h2 class="text-4xl md:text-5xl font-extrabold leading-tight mb-6">
          Mewujudkan Pendidikan Islam Ahlussunnah wal Jama'ah yang Berkualitas
        </h2>
        <p class="text-lg text-green-100 mb-8">
          LP Ma'arif NU Kabupaten Magelang berkomitmen meningkatkan mutu pendidikan
          melalui madrasah dan sekolah yang unggul, religius, dan berdaya saing.
        </p>
        <div class="flex flex-col sm:flex-row gap-4">
          <a href="<?= url('dashboard') ?>"
             class="inline-block text-center bg-yellow-400 hover:bg-yellow-500 text-green-900 font-semibold px-6 py-3 rounded-full shadow-lg transition">
            Layanan Online
          </a>
          <a href="<?= url('rakerdinma') ?>"
             class="inline-block text-center bg-white hover:bg-green-50 text-green-800 font-semibold px-6 py-3 rounded-full shadow-lg transition border-2 border-white">
            Pendaftaran RAKERDINMA 2026
          </a>
          <a href="<?= url('rakerdinma/sertifikat') ?>"
             class="inline-block text-center bg-green-600 hover:bg-green-500 text-white font-semibold px-6 py-3 rounded-full shadow-lg transition border-2 border-green-500">
            Download Sertifikat
          </a>
        </div>
      </div>
      <div>
        <img src="https://suaranu.id/storage/2025/07/IMG-20250722-WA0064.jpg"
             alt="Pendidikan"
             class="rounded-3xl shadow-2xl w-full object-cover h-[400px]">
      </div>
    </div>
  </section>

  <!-- Tentang -->
  <section id="tentang" class="py-20 bg-white">
    <div class="max-w-5xl mx-auto px-6 text-center">
      <h3 class="text-3xl font-bold text-green-800 mb-6">Tentang Kami</h3>
      <p class="text-lg text-gray-600 leading-relaxed">
        LP Ma'arif NU Kabupaten Magelang merupakan lembaga yang menaungi sekolah dan
        madrasah di bawah Nahdlatul Ulama. Kami hadir untuk memperkuat pendidikan yang
        berkarakter Islami, nasionalis, dan berorientasi pada kemajuan zaman.
      </p>
    </div>
  </section>

  <!-- Program -->
  <section id="program" class="py-20 bg-gray-100">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-12">
        <h3 class="text-3xl font-bold text-green-800">Program Unggulan</h3>
        <p class="text-gray-600 mt-3">Beberapa program utama yang kami jalankan</p>
      </div>

      <div class="grid md:grid-cols-3 gap-8">
        <a href="<?= url('pemesanan') ?>" class="bg-white p-8 rounded-2xl shadow hover:shadow-lg transition block">
          <div class="text-4xl mb-4">📖</div>
          <h4 class="text-xl font-semibold mb-3">Pemesanan Layanan</h4>
          <p class="text-gray-600">
            MOPDIK, Batik Ma'arif, Buku Ke-NU-an, dan Buku Tulis Karakter Aswaja.
          </p>
        </a>

        <div class="bg-white p-8 rounded-2xl shadow hover:shadow-lg transition">
          <div class="text-4xl mb-4">📚</div>
          <h4 class="text-xl font-semibold mb-3">Peningkatan Mutu Pendidikan</h4>
          <p class="text-gray-600">
            Pelatihan guru, akreditasi, dan penguatan manajemen sekolah.
          </p>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow hover:shadow-lg transition">
          <div class="text-4xl mb-4">🕌</div>
          <h4 class="text-xl font-semibold mb-3">Penguatan Aswaja</h4>
          <p class="text-gray-600">
            Menanamkan nilai Ahlussunnah wal Jama'ah An-Nahdliyah kepada peserta didik.
          </p>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow hover:shadow-lg transition">
          <div class="text-4xl mb-4">🌍</div>
          <h4 class="text-xl font-semibold mb-3">Digitalisasi Pendidikan</h4>
          <p class="text-gray-600">
            Pengembangan teknologi dan sistem informasi pendidikan modern.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Statistik -->
  <section class="py-20 bg-green-800 text-white">
    <div class="max-w-6xl mx-auto px-6 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
      <div>
        <h4 class="text-4xl font-bold">300+</h4>
        <p class="text-green-100 mt-2">Lembaga Pendidikan</p>
      </div>
      <div>
        <h4 class="text-4xl font-bold">29.000+</h4>
        <p class="text-green-100 mt-2">Peserta Didik</p>
      </div>
      <div>
        <h4 class="text-4xl font-bold">3.000+</h4>
        <p class="text-green-100 mt-2">Guru & Tenaga Kependidikan</p>
      </div>
      <div>
        <h4 class="text-4xl font-bold">21</h4>
        <p class="text-green-100 mt-2">Kecamatan</p>
      </div>
    </div>
  </section>

  <!-- Kontak -->
  <section id="kontak" class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-6 text-center">
      <h3 class="text-3xl font-bold text-green-800 mb-6">Hubungi Kami</h3>
      <p class="text-gray-600 mb-8">
      Jl. Magelang - Yogyakarta No.KM 12, Palbapang, Bojong, Kec. Mungkid, Kabupaten Magelang, Jawa Tengah 56551
      </p>
      <div class="space-y-2 text-gray-700">
        <p>✉️ info@maarifnumagelang.or.id</p>
        <p>🌐 www.maarifnumagelang.or.id</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-green-900 text-green-100 py-6">
    <div class="max-w-7xl mx-auto px-6 text-center text-sm">
      © 2026 LP Ma'arif NU Kabupaten Magelang. All rights reserved.
    </div>
  </footer>

</body>
</html>