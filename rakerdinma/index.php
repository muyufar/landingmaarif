<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$errors = [];
$success = isset($_GET['success']);
$formData = array_merge([
    'nama' => '',
    'nip' => '',
    'nomor_wa' => '',
    'tempat_lahir' => '',
    'tanggal_lahir' => '',
    'jabatan' => '',
    'jenis_lembaga' => '',
    'asal_lembaga' => '',
    'kode_kecamatan' => '',
    'nama_kecamatan' => '',
    'alamat_detail' => '',
    'alat_transportasi' => '',
], defaultWilayahMagelang(), [
    'kode_kelurahan' => '',
    'nama_kelurahan' => '',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = validatePendaftaran($_POST, null, true);

    if (!empty($result['errors'])) {
        $errors = $result['errors'];
        $formData = array_merge($formData, $result['data']);
    } else {
        try {
            if (addPeserta($result['data'])) {
                header('Location: ' . url('rakerdinma/?success=1'));
                exit;
            }
            if (nomorWaSudahTerdaftar($result['data']['nomor_wa'])) {
                $errors[] = 'Nomor WA sudah terdaftar. Satu nomor hanya dapat mendaftar sekali.';
            } else {
                $errors[] = 'Gagal menyimpan data. Silakan coba lagi.';
            }
            $formData = array_merge($formData, $result['data']);
        } catch (PDOException $e) {
            $errors[] = 'Koneksi database gagal. Hubungi panitia.';
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
  <title><?= sanitize(EVENT_TITLE) ?> | LP Ma'arif NU Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-3xl mx-auto px-6 py-4 flex items-center space-x-4">
      <img src="<?= url('image/logo.png') ?>" alt="Logo LP Ma'arif NU" class="w-12 h-12 rounded-full bg-white p-1">
      <div>
        <h1 class="text-lg md:text-xl font-bold">LP Ma'arif NU Kabupaten Magelang</h1>
        <p class="text-sm text-green-100">Form Pendaftaran RAKERDINMA 2026</p>
      </div>
    </div>
  </header>

  <main class="max-w-3xl mx-auto px-6 py-10">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-green-100">
      <div class="bg-green-700 text-white px-8 py-6">
        <h2 class="text-xl md:text-2xl font-bold leading-snug"><?= sanitize(EVENT_TITLE) ?></h2>
        <p class="text-green-100 mt-2"><?= sanitize(EVENT_SUBTITLE) ?></p>
      </div>

      <div class="px-8 py-8">
        <div class="mb-6 text-center">
          <a href="<?= url('rakerdinma/sertifikat') ?>" class="text-sm text-green-700 hover:text-green-900 font-medium underline">
            Sudah terdaftar? Download sertifikat RAKERDINMA di sini
          </a>
        </div>

        <?php if ($success): ?>
          <div class="mb-8 rounded-xl bg-green-50 border border-green-200 px-6 py-5 text-green-800">
            <h3 class="font-semibold text-lg mb-1">Pendaftaran Berhasil!</h3>
            <p>Terima kasih, data Anda telah tersimpan. Silakan tunggu informasi selanjutnya dari panitia.</p>
            <a href="<?= url('rakerdinma/sertifikat') ?>"
               class="inline-block mt-4 bg-green-700 hover:bg-green-800 text-white font-semibold px-5 py-2.5 rounded-lg transition">
              Download Sertifikat RAKERDINMA
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

        <form method="post" action="" id="form-pendaftaran" class="space-y-6">
          <div>
            <label for="nama" class="block text-sm font-semibold text-gray-700 mb-2">NAMA <span class="text-red-500">*</span></label>
            <input type="text" id="nama" name="nama" required value="<?= fieldValue('nama', $formData) ?>"
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
          </div>

          <div>
            <label for="nip" class="block text-sm font-semibold text-gray-700 mb-2">NIP</label>
            <input type="text" id="nip" name="nip" value="<?= fieldValue('nip', $formData) ?>"
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
          </div>

          <div>
            <label for="nomor_wa" class="block text-sm font-semibold text-gray-700 mb-2">NOMOR WA <span class="text-red-500">*</span></label>
            <input type="tel" id="nomor_wa" name="nomor_wa" required placeholder="08xxxxxxxxxx" value="<?= fieldValue('nomor_wa', $formData) ?>"
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
          </div>

          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label for="tempat_lahir" class="block text-sm font-semibold text-gray-700 mb-2">TEMPAT LAHIR <span class="text-red-500">*</span></label>
              <input type="text" id="tempat_lahir" name="tempat_lahir" required value="<?= fieldValue('tempat_lahir', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
            </div>
            <div>
              <label for="tanggal_lahir" class="block text-sm font-semibold text-gray-700 mb-2">TANGGAL LAHIR <span class="text-red-500">*</span></label>
              <input type="date" id="tanggal_lahir" name="tanggal_lahir" required value="<?= fieldValue('tanggal_lahir', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
            </div>
          </div>

          <?php require dirname(__DIR__) . '/pesertakerdinma/_jabatan_transportasi_fields.php'; ?>

          <?php $formData = $formData; require dirname(__DIR__) . '/pesertakerdinma/_jenis_lembaga_field.php'; ?>

          <div>
            <label for="asal_lembaga" class="block text-sm font-semibold text-gray-700 mb-2">NAMA LEMBAGA <span class="text-red-500">*</span></label>
            <input type="text" id="asal_lembaga" name="asal_lembaga" required placeholder="Contoh: MI Ma'arif Giritengah"
                   value="<?= fieldValue('asal_lembaga', $formData) ?>"
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
          </div>

          <?php require dirname(__DIR__) . '/pesertakerdinma/_wilayah_registrasi_fields.php'; ?>

          <div class="flex flex-col sm:flex-row gap-4 pt-4">
            <button type="submit" id="btn-submit"
                    class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg transition shadow">
              Kirim Pendaftaran
            </button>
            <button type="reset"
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-6 py-3 rounded-lg transition">
              Reset Form
            </button>
          </div>
        </form>
      </div>
    </div>

    <p class="text-center text-sm text-gray-500 mt-6">
      <a href="<?= url() ?>" class="text-green-700 hover:underline">← Kembali ke Beranda</a>
    </p>
  </main>

  <footer class="bg-green-900 text-green-100 py-6 mt-10">
    <div class="max-w-3xl mx-auto px-6 text-center text-sm">
      © 2026 LP Ma'arif NU Kabupaten Magelang
    </div>
  </footer>

  <?php require dirname(__DIR__) . '/pesertakerdinma/_jabatan_transportasi_script.php'; ?>
  <?php require dirname(__DIR__) . '/pesertakerdinma/_wilayah_registrasi_script.php'; ?>

  <script>
    (function () {
      var form = document.getElementById('form-pendaftaran');
      if (!form) return;
      form.addEventListener('submit', function () {
        var btn = document.getElementById('btn-submit');
        if (btn) {
          btn.disabled = true;
          btn.textContent = 'Menyimpan...';
        }
      });
    })();
  </script>

</body>
</html>
