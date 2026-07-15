<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/pengkinian_data_functions.php';

$errors = [];
$success = isset($_GET['success']);
$wasUpdate = isset($_GET['updated']);
$formData = pengkinianDataDefaultForm();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $existingRow = null;
    $npsnProbe = normalizeNpsn($_POST['npsn'] ?? '');
    if ($npsnProbe !== '') {
        $existingId = findPengkinianDataId([
            'npsn' => $npsnProbe,
            'nama_satuan_pendidikan' => '',
            'kode_kelurahan' => '',
        ]);
        if ($existingId !== null) {
            $existingRow = getPengkinianDataById($existingId);
        }
    }

    $result = validatePengkinianData($_POST, $_FILES, $existingRow);

    if (!empty($result['errors'])) {
        $errors = $result['errors'];
        $formData = array_merge($formData, $result['data']);
    } else {
        try {
            $saved = savePengkinianData($result['data']);
            $query = $saved['updated'] ? '?success=1&updated=1' : '?success=1';
            header('Location: ' . url('pengkinian/' . $query));
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan data. Silakan coba lagi atau hubungi panitia.';
            $formData = array_merge($formData, $result['data']);
        }
    }
}

function fieldValue(string $key, array $formData): string
{
    return sanitize($formData[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= sanitize(PENGKINIAN_DATA_TITLE) ?> | LP Ma'arif NU Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-4">
        <img src="<?= url('image/logo.png') ?>" alt="Logo LP Ma'arif NU" class="w-12 h-12 rounded-full bg-white p-1">
        <div>
          <h1 class="text-lg md:text-xl font-bold">LP Ma'arif NU Kabupaten Magelang</h1>
          <p class="text-sm text-green-100">Pembaruan Data Satuan Pendidikan</p>
        </div>
      </div>
      <a href="<?= url('dashboard') ?>" class="text-sm bg-green-900 hover:bg-green-950 px-4 py-2 rounded-lg transition whitespace-nowrap">← Layanan</a>
    </div>
  </header>

  <main class="max-w-3xl mx-auto px-6 py-10">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-green-100">
      <div class="bg-green-700 text-white px-8 py-6">
        <h2 class="text-xl md:text-2xl font-bold leading-snug"><?= sanitize(PENGKINIAN_DATA_TITLE) ?></h2>
        <p class="text-green-100 mt-2"><?= sanitize(PENGKINIAN_DATA_SUBTITLE) ?></p>
      </div>

      <div class="px-8 py-8">
        <p class="text-sm text-gray-600 mb-6 leading-relaxed">
          Form ini digunakan untuk memperbarui data nomor HP Kepala Sekolah dan Operator pada satuan pendidikan
          binaan LP Ma'arif NU Kabupaten Magelang. Jika satuan pendidikan yang sama sudah pernah mengisi,
          data akan diperbarui otomatis berdasarkan <strong>NPSN</strong>.
        </p>

        <?php if ($success): ?>
          <div class="mb-8 rounded-xl bg-green-50 border border-green-200 px-6 py-5 text-green-800">
            <h3 class="font-semibold text-lg mb-1"><?= $wasUpdate ? 'Data Berhasil Diperbarui!' : 'Data Berhasil Tersimpan!' ?></h3>
            <p>
              <?= $wasUpdate
                  ? 'Data satuan pendidikan Anda telah diperbarui. Terima kasih.'
                  : 'Terima kasih, data satuan pendidikan Anda telah tersimpan.' ?>
            </p>
            <a href="<?= url('pengkinian/') ?>"
               class="inline-block mt-4 bg-green-700 hover:bg-green-800 text-white font-semibold px-5 py-2.5 rounded-lg transition">
              Isi Form Lagi
            </a>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="mb-8 rounded-xl bg-red-50 border border-red-200 px-6 py-5 text-red-800">
            <h3 class="font-semibold mb-2">Periksa kembali formulir:</h3>
            <ul class="list-disc list-inside space-y-1">
              <?php foreach ($errors as $error): ?>
                <li><?= sanitize($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" action="" id="form-pengkinian" enctype="multipart/form-data" class="space-y-6">
          <div>
            <label for="npsn" class="block text-sm font-semibold text-gray-700 mb-2">
              NPSN <span class="text-red-500">*</span>
            </label>
            <input type="text" id="npsn" name="npsn" required inputmode="numeric" pattern="[0-9]{8,10}"
                   placeholder="Contoh: 20312345" maxlength="10"
                   value="<?= fieldValue('npsn', $formData) ?>"
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
            <p class="text-xs text-gray-500 mt-1">Nomor Pokok Sekolah Nasional, 8–10 digit angka.</p>
          </div>

          <div>
            <label for="nama_satuan_pendidikan" class="block text-sm font-semibold text-gray-700 mb-2">
              Nama Satuan Pendidikan <span class="text-red-500">*</span>
            </label>
            <input type="text" id="nama_satuan_pendidikan" name="nama_satuan_pendidikan" required
                   placeholder="Contoh: MI Ma'arif Donorejo"
                   value="<?= fieldValue('nama_satuan_pendidikan', $formData) ?>"
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
          </div>

          <div>
            <label for="jenjang" class="block text-sm font-semibold text-gray-700 mb-2">
              Jenjang <span class="text-red-500">*</span>
            </label>
            <select id="jenjang" name="jenjang" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white">
              <option value="">-- Pilih Jenjang --</option>
              <?php foreach (pengkinianJenjangOptions() as $opt): ?>
                <option value="<?= sanitize($opt) ?>" <?= fieldValue('jenjang', $formData) === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label for="nama_kepala_sekolah" class="block text-sm font-semibold text-gray-700 mb-2">
                Nama Kepala Sekolah <span class="text-red-500">*</span>
              </label>
              <input type="text" id="nama_kepala_sekolah" name="nama_kepala_sekolah" required
                     value="<?= fieldValue('nama_kepala_sekolah', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
            </div>
            <div>
              <label for="nama_operator" class="block text-sm font-semibold text-gray-700 mb-2">
                Nama Operator <span class="text-red-500">*</span>
              </label>
              <input type="text" id="nama_operator" name="nama_operator" required
                     value="<?= fieldValue('nama_operator', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label for="tempat_lahir" class="block text-sm font-semibold text-gray-700 mb-2">
                Tempat Lahir <span class="text-red-500">*</span>
              </label>
              <input type="text" id="tempat_lahir" name="tempat_lahir" required
                     value="<?= fieldValue('tempat_lahir', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
            </div>
            <div>
              <label for="tanggal_lahir" class="block text-sm font-semibold text-gray-700 mb-2">
                Tanggal Lahir <span class="text-red-500">*</span>
              </label>
              <input type="date" id="tanggal_lahir" name="tanggal_lahir" required
                     value="<?= fieldValue('tanggal_lahir', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label for="niy_nip" class="block text-sm font-semibold text-gray-700 mb-2">
                NIY/NIP <span class="text-red-500">*</span>
              </label>
              <input type="text" id="niy_nip" name="niy_nip" required
                     value="<?= fieldValue('niy_nip', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
            </div>
            <div>
              <label for="jabatan" class="block text-sm font-semibold text-gray-700 mb-2">
                Jabatan <span class="text-red-500">*</span>
              </label>
              <select id="jabatan" name="jabatan" required
                      class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white">
                <option value="">-- Pilih Jabatan --</option>
                <?php foreach (pengkinianJabatanOptions() as $opt): ?>
                  <option value="<?= sanitize($opt) ?>" <?= fieldValue('jabatan', $formData) === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label for="nomor_hp_kepsek" class="block text-sm font-semibold text-gray-700 mb-2">
                Nomor HP Kepala Sekolah <span class="text-red-500">*</span>
              </label>
              <input type="tel" id="nomor_hp_kepsek" name="nomor_hp_kepsek" required placeholder="08xxxxxxxxxx"
                     value="<?= fieldValue('nomor_hp_kepsek', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
            </div>
            <div>
              <label for="nomor_hp_operator" class="block text-sm font-semibold text-gray-700 mb-2">
                Nomor HP Operator <span class="text-red-500">*</span>
              </label>
              <input type="tel" id="nomor_hp_operator" name="nomor_hp_operator" required placeholder="08xxxxxxxxxx"
                     value="<?= fieldValue('nomor_hp_operator', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
            </div>
          </div>

          <?php
            $wilayahSectionTitle = 'ALAMAT SATUAN PENDIDIKAN';
            require dirname(__DIR__) . '/pesertakerdinma/_wilayah_registrasi_fields.php';
          ?>

          <div class="rounded-xl border border-green-200 bg-green-50/50 p-5 space-y-5">
            <h3 class="text-sm font-bold text-green-900 uppercase tracking-wide">Data SK Kepala Terakhir</h3>

            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <label for="tgl_tmt_sk" class="block text-sm font-semibold text-gray-700 mb-2">
                  Tgl TMT SK <span class="text-red-500">*</span>
                </label>
                <input type="date" id="tgl_tmt_sk" name="tgl_tmt_sk" required
                       value="<?= fieldValue('tgl_tmt_sk', $formData) ?>"
                       class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white">
              </div>
              <div>
                <label for="tgl_akhir_tmt_sk" class="block text-sm font-semibold text-gray-700 mb-2">
                  Tgl Akhir TMT SK <span class="text-red-500">*</span>
                </label>
                <input type="date" id="tgl_akhir_tmt_sk" name="tgl_akhir_tmt_sk" required
                       value="<?= fieldValue('tgl_akhir_tmt_sk', $formData) ?>"
                       class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white">
              </div>
            </div>

            <div>
              <label for="file_sk_kepala" class="block text-sm font-semibold text-gray-700 mb-2">
                Upload Scan SK Terakhir <span class="text-red-500">*</span>
              </label>
              <input type="file" id="file_sk_kepala" name="file_sk_kepala" accept=".pdf,.jpg,.jpeg,.png"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 bg-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-green-700 file:text-white file:font-semibold">
              <p class="text-xs text-gray-500 mt-1">PDF, JPG, atau PNG. Maks. 5 MB. Kosongkan jika memperbarui data dan file SK tidak berubah.</p>
            </div>

            <div>
              <label for="status_sk_kepala" class="block text-sm font-semibold text-gray-700 mb-2">
                Status SK Kepala <span class="text-red-500">*</span>
              </label>
              <select id="status_sk_kepala" name="status_sk_kepala" required
                      class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white">
                <option value="">-- Pilih Status --</option>
                <?php foreach (pengkinianStatusSkOptions() as $opt): ?>
                  <option value="<?= sanitize($opt) ?>" <?= fieldValue('status_sk_kepala', $formData) === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <button type="submit" id="btn-submit"
                  class="w-full bg-green-700 hover:bg-green-800 text-white font-bold px-6 py-4 rounded-xl shadow transition disabled:opacity-60">
            Simpan Data
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <footer class="bg-green-900 text-green-100 py-6 mt-auto">
    <div class="max-w-3xl mx-auto px-6 text-center text-sm">
      © 2026 LP Ma'arif NU Kabupaten Magelang
    </div>
  </footer>

  <?php if (!$success): ?>
    <?php require dirname(__DIR__) . '/pesertakerdinma/_wilayah_registrasi_script.php'; ?>
    <script>
      document.getElementById('form-pengkinian')?.addEventListener('submit', function () {
        const btn = document.getElementById('btn-submit');
        if (btn) {
          btn.disabled = true;
          btn.textContent = 'Menyimpan...';
        }
      });
    </script>
  <?php endif; ?>

</body>
</html>
