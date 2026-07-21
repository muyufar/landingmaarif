<?php declare(strict_types=1); ?>
<div class="bg-white rounded-2xl border shadow-lg p-6 max-w-2xl">
  <h2 class="text-xl font-bold text-green-800 mb-2">Import Data Satuan Pendidikan</h2>
  <p class="text-sm text-gray-600 mb-4">
    Upload file <strong>REKAP SISWA DAN KEBUTUHAN BUKU LKS MI MAARIF MGL.xlsx</strong>.
    Sistem otomatis membaca tab <strong>DATA BUKU PERSEKOLAH</strong> (prioritas) atau tab data sekolah,
    termasuk jumlah siswa per kelas, <strong>buku guru</strong>, dan total buku.
  </p>

  <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-xs text-gray-700 mb-6">
    <p class="font-semibold text-green-900 mb-1">Format otomatis dikenali:</p>
    <ul class="list-disc list-inside space-y-0.5">
      <li>Tab <strong>DATA BUKU PERSEKOLAH</strong>: detail per mapel + buku guru (kolom G, O, V, AD, AL, AT) + total (BB)</li>
      <li>Tab DATA SEKOLAH DAN SISWA: fallback jumlah siswa K1–K6 (kolom H–M)</li>
      <li>Alamat diisi otomatis: Kec. [kecamatan], Kabupaten Magelang</li>
      <li>Baris tanpa NPSN valid dilewati (~40 baris SD tanpa NPSN)</li>
    </ul>
  </div>

  <form method="post" enctype="multipart/form-data" class="space-y-4">
    <input type="file" name="import_file" accept=".csv,.xlsx,.xls" required
           class="w-full rounded-lg border px-4 py-3 bg-white file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-green-700 file:text-white">
    <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-bold py-3 rounded-xl">Import Data</button>
  </form>

  <p class="text-xs text-gray-500 mt-4">
    File CSV sederhana juga didukung jika kolom: npsn, nama_lembaga, kecamatan/alamat, kelas_1 … kelas_6.
    Untuk input satu sekolah saja, gunakan
    <a href="<?= url('admindistribusi/?page=create') ?>" class="text-green-700 underline">Tambah Satuan Manual</a>.
  </p>
</div>
