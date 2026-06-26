<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$errors = [];
$success = false;
$formData = [
    'nama' => '',
    'nip' => '',
    'nomor_wa' => '',
    'tempat_lahir' => '',
    'tanggal_lahir' => '',
    'jabatan' => '',
    'asal_lembaga' => '',
    'alamat_lembaga' => '',
    'alat_transportasi' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = validatePendaftaran($_POST);

    if (!empty($result['errors'])) {
        $errors = $result['errors'];
        $formData = array_merge($formData, $result['data']);
    } else {
        try {
            if (addPeserta($result['data'])) {
                $success = true;
                $formData = [
                    'nama' => '',
                    'nip' => '',
                    'nomor_wa' => '',
                    'tempat_lahir' => '',
                    'tanggal_lahir' => '',
                    'jabatan' => '',
                    'asal_lembaga' => '',
                    'alamat_lembaga' => '',
                    'alat_transportasi' => '',
                ];
            } else {
                $errors[] = 'Gagal menyimpan data. Silakan coba lagi.';
                $formData = array_merge($formData, $result['data']);
            }
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
      <img src="/image/logo.png" alt="Logo LP Ma'arif NU" class="w-12 h-12 rounded-full bg-white p-1">
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
        <?php if ($success): ?>
          <div class="mb-8 rounded-xl bg-green-50 border border-green-200 px-6 py-5 text-green-800">
            <h3 class="font-semibold text-lg mb-1">Pendaftaran Berhasil!</h3>
            <p>Terima kasih, data Anda telah tersimpan. Silakan tunggu informasi selanjutnya dari panitia.</p>
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

        <form method="post" action="" class="space-y-6">
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

          <div>
            <label for="jabatan" class="block text-sm font-semibold text-gray-700 mb-2">JABATAN <span class="text-red-500">*</span></label>
            <input type="text" id="jabatan" name="jabatan" required value="<?= fieldValue('jabatan', $formData) ?>"
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
          </div>

          <div>
            <label for="asal_lembaga" class="block text-sm font-semibold text-gray-700 mb-2">ASAL LEMBAGA <span class="text-red-500">*</span></label>
            <input type="text" id="asal_lembaga" name="asal_lembaga" required value="<?= fieldValue('asal_lembaga', $formData) ?>"
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
          </div>

          <div>
            <label for="alamat_lembaga" class="block text-sm font-semibold text-gray-700 mb-2">ALAMAT LEMBAGA <span class="text-red-500">*</span></label>
            <textarea id="alamat_lembaga" name="alamat_lembaga" required rows="3"
                      class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent"><?= fieldValue('alamat_lembaga', $formData) ?></textarea>
          </div>

          <div>
            <label for="alat_transportasi" class="block text-sm font-semibold text-gray-700 mb-2">ALAT TRANSPORTASI <span class="text-red-500">*</span></label>
            <input type="text" id="alat_transportasi" name="alat_transportasi" required placeholder="Contoh: Motor, Mobil, Bus, dll." value="<?= fieldValue('alat_transportasi', $formData) ?>"
                   class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent">
          </div>

          <div class="flex flex-col sm:flex-row gap-4 pt-4">
            <button type="submit"
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
      <a href="/" class="text-green-700 hover:underline">← Kembali ke Beranda</a>
    </p>
  </main>

  <footer class="bg-green-900 text-green-100 py-6 mt-10">
    <div class="max-w-3xl mx-auto px-6 text-center text-sm">
      © 2026 LP Ma'arif NU Kabupaten Magelang
    </div>
  </footer>

</body>
</html>
