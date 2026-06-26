<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$errors = [];
$success = isset($_GET['success']);
$formData = [
    'nama' => '',
    'nip' => '',
    'nomor_wa' => '',
    'tempat_lahir' => '',
    'tanggal_lahir' => '',
    'jabatan' => '',
    'jenis_lembaga' => '',
    'asal_lembaga' => '',
    'kode_provinsi' => '',
    'nama_provinsi' => '',
    'kode_kabupaten' => '',
    'nama_kabupaten' => '',
    'kode_kecamatan' => '',
    'nama_kecamatan' => '',
    'kode_kelurahan' => '',
    'nama_kelurahan' => '',
    'alamat_detail' => '',
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

          <div class="space-y-4">
            <p class="block text-sm font-semibold text-gray-700">ALAMAT LEMBAGA <span class="text-red-500">*</span></p>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label for="kode_provinsi" class="block text-xs font-medium text-gray-600 mb-1">Provinsi</label>
                <select id="kode_provinsi" name="kode_provinsi" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white">
                  <option value="">-- Pilih Provinsi --</option>
                </select>
                <input type="hidden" id="nama_provinsi" name="nama_provinsi" value="<?= fieldValue('nama_provinsi', $formData) ?>">
              </div>
              <div>
                <label for="kode_kabupaten" class="block text-xs font-medium text-gray-600 mb-1">Kabupaten / Kota</label>
                <select id="kode_kabupaten" name="kode_kabupaten" required disabled
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white disabled:bg-gray-100">
                  <option value="">-- Pilih Kabupaten/Kota --</option>
                </select>
                <input type="hidden" id="nama_kabupaten" name="nama_kabupaten" value="<?= fieldValue('nama_kabupaten', $formData) ?>">
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label for="kode_kecamatan" class="block text-xs font-medium text-gray-600 mb-1">Kecamatan</label>
                <select id="kode_kecamatan" name="kode_kecamatan" required disabled
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white disabled:bg-gray-100">
                  <option value="">-- Pilih Kecamatan --</option>
                </select>
                <input type="hidden" id="nama_kecamatan" name="nama_kecamatan" value="<?= fieldValue('nama_kecamatan', $formData) ?>">
              </div>
              <div>
                <label for="kode_kelurahan" class="block text-xs font-medium text-gray-600 mb-1">Kelurahan / Desa</label>
                <select id="kode_kelurahan" name="kode_kelurahan" required disabled
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white disabled:bg-gray-100">
                  <option value="">-- Pilih Kelurahan/Desa --</option>
                </select>
                <input type="hidden" id="nama_kelurahan" name="nama_kelurahan" value="<?= fieldValue('nama_kelurahan', $formData) ?>">
              </div>
            </div>

            <div>
              <label for="alamat_detail" class="block text-xs font-medium text-gray-600 mb-1">Alamat Detail (Jalan, RT/RW, dll.)</label>
              <textarea id="alamat_detail" name="alamat_detail" required rows="2" placeholder="Contoh: Jl. Raya Magelang No. 12, RT 03/RW 05"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent"><?= fieldValue('alamat_detail', $formData) ?></textarea>
            </div>

            <p class="text-xs text-gray-500">Data wilayah diambil dari <a href="https://wilayah.id/" target="_blank" rel="noopener" class="text-green-700 hover:underline">Wilayah.id</a></p>
          </div>

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

  <script>
    (function () {
      const WILAYAH_API = <?= json_encode(url('rakerdinma/wilayah.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const saved = {
        kode_provinsi: <?= json_encode($formData['kode_provinsi'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        kode_kabupaten: <?= json_encode($formData['kode_kabupaten'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        kode_kecamatan: <?= json_encode($formData['kode_kecamatan'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        kode_kelurahan: <?= json_encode($formData['kode_kelurahan'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
      };

      const selects = {
        provinsi: document.getElementById('kode_provinsi'),
        kabupaten: document.getElementById('kode_kabupaten'),
        kecamatan: document.getElementById('kode_kecamatan'),
        kelurahan: document.getElementById('kode_kelurahan'),
      };

      const names = {
        provinsi: document.getElementById('nama_provinsi'),
        kabupaten: document.getElementById('nama_kabupaten'),
        kecamatan: document.getElementById('nama_kecamatan'),
        kelurahan: document.getElementById('nama_kelurahan'),
      };

      async function fetchWilayah(level, code) {
        let url = WILAYAH_API + '?level=' + encodeURIComponent(level);
        if (code) {
          url += '&code=' + encodeURIComponent(code);
        }
        const res = await fetch(url);
        if (!res.ok) {
          throw new Error('Gagal memuat data wilayah');
        }
        const json = await res.json();
        return json.data || [];
      }

      function fillSelect(select, items, placeholder, selectedCode) {
        select.innerHTML = '';
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = placeholder;
        select.appendChild(defaultOpt);

        items.forEach(function (item) {
          const opt = document.createElement('option');
          opt.value = item.code;
          opt.textContent = item.name;
          opt.dataset.name = item.name;
          if (selectedCode && item.code === selectedCode) {
            opt.selected = true;
          }
          select.appendChild(opt);
        });
      }

      function syncName(select, nameInput) {
        const opt = select.options[select.selectedIndex];
        nameInput.value = opt && opt.dataset.name ? opt.dataset.name : '';
      }

      function resetFrom(level) {
        if (level === 'provinsi') {
          selects.kabupaten.innerHTML = '<option value="">-- Pilih Kabupaten/Kota --</option>';
          selects.kabupaten.disabled = true;
          names.kabupaten.value = '';
        }
        if (level === 'provinsi' || level === 'kabupaten') {
          selects.kecamatan.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
          selects.kecamatan.disabled = true;
          names.kecamatan.value = '';
        }
        if (level !== 'kelurahan') {
          selects.kelurahan.innerHTML = '<option value="">-- Pilih Kelurahan/Desa --</option>';
          selects.kelurahan.disabled = true;
          names.kelurahan.value = '';
        }
      }

      selects.provinsi.addEventListener('change', async function () {
        syncName(selects.provinsi, names.provinsi);
        resetFrom('provinsi');
        if (!this.value) return;

        selects.kabupaten.disabled = true;
        try {
          const items = await fetchWilayah('regencies', this.value);
          fillSelect(selects.kabupaten, items, '-- Pilih Kabupaten/Kota --', '');
          selects.kabupaten.disabled = false;
        } catch (e) {
          alert('Gagal memuat data kabupaten/kota.');
        }
      });

      selects.kabupaten.addEventListener('change', async function () {
        syncName(selects.kabupaten, names.kabupaten);
        resetFrom('kabupaten');
        if (!this.value) return;

        selects.kecamatan.disabled = true;
        try {
          const items = await fetchWilayah('districts', this.value);
          fillSelect(selects.kecamatan, items, '-- Pilih Kecamatan --', '');
          selects.kecamatan.disabled = false;
        } catch (e) {
          alert('Gagal memuat data kecamatan.');
        }
      });

      selects.kecamatan.addEventListener('change', async function () {
        syncName(selects.kecamatan, names.kecamatan);
        resetFrom('kecamatan');
        if (!this.value) return;

        selects.kelurahan.disabled = true;
        try {
          const items = await fetchWilayah('villages', this.value);
          fillSelect(selects.kelurahan, items, '-- Pilih Kelurahan/Desa --', '');
          selects.kelurahan.disabled = false;
        } catch (e) {
          alert('Gagal memuat data kelurahan/desa.');
        }
      });

      selects.kelurahan.addEventListener('change', function () {
        syncName(selects.kelurahan, names.kelurahan);
      });

      async function initWilayah() {
        try {
          const provinces = await fetchWilayah('provinces');
          fillSelect(selects.provinsi, provinces, '-- Pilih Provinsi --', saved.kode_provinsi);
          syncName(selects.provinsi, names.provinsi);

          if (saved.kode_provinsi) {
            const regencies = await fetchWilayah('regencies', saved.kode_provinsi);
            fillSelect(selects.kabupaten, regencies, '-- Pilih Kabupaten/Kota --', saved.kode_kabupaten);
            selects.kabupaten.disabled = false;
            syncName(selects.kabupaten, names.kabupaten);
          }

          if (saved.kode_kabupaten) {
            const districts = await fetchWilayah('districts', saved.kode_kabupaten);
            fillSelect(selects.kecamatan, districts, '-- Pilih Kecamatan --', saved.kode_kecamatan);
            selects.kecamatan.disabled = false;
            syncName(selects.kecamatan, names.kecamatan);
          }

          if (saved.kode_kecamatan) {
            const villages = await fetchWilayah('villages', saved.kode_kecamatan);
            fillSelect(selects.kelurahan, villages, '-- Pilih Kelurahan/Desa --', saved.kode_kelurahan);
            selects.kelurahan.disabled = false;
            syncName(selects.kelurahan, names.kelurahan);
          }
        } catch (e) {
          alert('Gagal memuat data provinsi. Periksa koneksi internet.');
        }
      }

      initWilayah();
    })();
  </script>

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
