<?php declare(strict_types=1); ?>
<div class="bg-white rounded-2xl border shadow-lg p-6 max-w-2xl">
  <h2 class="text-xl font-bold text-green-800 mb-2">Import Data Satuan Pendidikan</h2>
  <p class="text-sm text-gray-600 mb-4">Upload file Excel (Sheet 1) atau CSV dengan kolom: <strong>NPSN, Nama Lembaga, Alamat, Kelas 1–6</strong>.</p>

  <div class="rounded-lg bg-gray-50 border p-4 text-xs text-gray-700 mb-6 font-mono">
    npsn,nama_lembaga,alamat,kelas_1,kelas_2,kelas_3,kelas_4,kelas_5,kelas_6<br>
    20312345,MI Ma'arif Donorejo,Jl. Raya...,120,115,110,0,0,0
  </div>

  <form method="post" enctype="multipart/form-data" class="space-y-4">
    <input type="file" name="import_file" accept=".csv,.xlsx,.xls" required
           class="w-full rounded-lg border px-4 py-3 bg-white file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-green-700 file:text-white">
    <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-bold py-3 rounded-xl">Import Data</button>
  </form>

  <p class="text-xs text-gray-500 mt-4">File referensi: REKAP SISWA DAN KEBUTUHAN BUKU LKS MI MAARIF MGL.xlsx — pastikan Sheet 1 berisi header di baris pertama.</p>
</div>
