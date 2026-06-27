<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/pemesanan_functions.php';

$jenis = trim($_GET['jenis'] ?? '');
$layanan = $jenis !== '' ? getPemesananLayanan($jenis) : null;
$catalog = pemesananLayananCatalog();

if ($jenis !== '' && $layanan === null) {
    header('Location: ' . url('pemesanan/'));
    exit;
}

$errors = [];
$success = isset($_GET['success']);
$formData = $layanan ? pemesananFormDefaults($jenis) : [];

if ($layanan && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = validatePemesanan($_POST, $jenis);

    if (!empty($result['errors'])) {
        $errors = $result['errors'];
        $formData = array_merge($formData, $result['data']);
    } else {
        try {
            if (addPemesanan($result['data'])) {
                header('Location: ' . url('pemesanan/?jenis=' . rawurlencode($jenis) . '&success=1'));
                exit;
            }
            $errors[] = 'Gagal menyimpan pemesanan. Silakan coba lagi.';
            $formData = array_merge($formData, $result['data']);
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan ke database. Muat ulang halaman lalu coba lagi, atau hubungi LP Ma\'arif NU.';
            if (is_file(dirname(__DIR__) . '/includes/config.local.php')) {
                $errors[] = 'Debug: ' . $e->getMessage();
            }
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
  <title><?= sanitize($layanan ? $layanan['title'] : 'Pemesanan Layanan') ?> | LP Ma'arif NU Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-4 min-w-0">
        <img src="<?= url('image/logo.png') ?>" alt="Logo" class="w-12 h-12 rounded-full bg-white p-1 shrink-0">
        <div class="min-w-0">
          <h1 class="text-lg md:text-xl font-bold truncate">LP Ma'arif NU Kabupaten Magelang</h1>
          <p class="text-sm text-green-100"><?= $layanan ? 'Form Pemesanan' : 'Menu Pemesanan' ?></p>
        </div>
      </div>
      <a href="<?= url($layanan ? 'pemesanan/' : 'dashboard') ?>" class="text-sm bg-green-900 hover:bg-green-950 px-3 py-2 rounded-lg transition shrink-0">
        <?= $layanan ? '← Menu' : '← Layanan' ?>
      </a>
    </div>
  </header>

  <main class="max-w-3xl mx-auto px-6 py-10">
    <?php if (!$layanan): ?>
      <div class="text-center mb-10">
        <h2 class="text-2xl font-bold text-green-800 mb-2">Pemesanan Layanan</h2>
        <p class="text-gray-600 text-sm">Pilih jenis pemesanan yang ingin Anda isi.</p>
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <?php foreach ($catalog as $key => $item): ?>
          <a href="<?= url('pemesanan/?jenis=' . rawurlencode($key)) ?>"
             class="bg-white rounded-2xl shadow-lg border border-green-100 p-6 hover:shadow-xl hover:border-green-300 transition">
            <div class="text-3xl mb-3"><?= $item['icon'] ?></div>
            <h3 class="font-bold text-green-800 mb-1"><?= sanitize($item['label']) ?></h3>
            <p class="text-xs text-gray-500"><?= sanitize($item['subtitle']) ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-green-100">
      <div class="bg-green-700 text-white px-8 py-6">
        <h2 class="text-xl md:text-2xl font-bold leading-snug"><?= sanitize($layanan['title']) ?></h2>
        <p class="text-green-100 mt-2"><?= sanitize($layanan['subtitle']) ?></p>
      </div>

      <div class="px-8 py-8">
        <?php if ($success): ?>
          <div class="mb-8 rounded-xl bg-green-50 border border-green-200 px-6 py-5 text-green-800">
            <h3 class="font-semibold text-lg mb-1">Pemesanan Berhasil!</h3>
            <p>Terima kasih, data pemesanan Anda telah tersimpan. Tim LP Ma'arif NU akan menghubungi Anda melalui WhatsApp.</p>
            <a href="<?= url('pemesanan/') ?>" class="inline-block mt-4 text-green-700 font-semibold text-sm hover:underline">← Kembali ke menu pemesanan</a>
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
        <form method="post" action="" class="space-y-8" id="form-pemesanan">
          <input type="hidden" name="jenis_layanan" value="<?= sanitize($jenis) ?>">

          <div class="space-y-6">
            <div>
              <label for="nama_madrasah" class="block text-sm font-semibold text-gray-700 mb-2">
                NAMA MADRASAH / SEKOLAH <span class="text-red-500">*</span>
              </label>
              <input type="text" id="nama_madrasah" name="nama_madrasah" required
                     value="<?= fieldValue('nama_madrasah', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>

            <div>
              <label for="nama_kepala" class="block text-sm font-semibold text-gray-700 mb-2">
                NAMA KEPALA / KEPSEK <span class="text-red-500">*</span>
              </label>
              <input type="text" id="nama_kepala" name="nama_kepala" required
                     value="<?= fieldValue('nama_kepala', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>

            <div>
              <label for="nomor_wa" class="block text-sm font-semibold text-gray-700 mb-2">NOMOR WA <span class="text-red-500">*</span></label>
              <input type="tel" id="nomor_wa" name="nomor_wa" required placeholder="08xxxxxxxxxx"
                     value="<?= fieldValue('nomor_wa', $formData) ?>"
                     class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>

            <?php if (!empty($layanan['jenjang'])): ?>
            <fieldset>
              <legend class="block text-sm font-semibold text-gray-700 mb-3">
                <?= sanitize(strtoupper($layanan['jenjang_label'])) ?> <span class="text-red-500">*</span>
              </legend>
              <div class="space-y-3">
                <?php foreach ($layanan['jenjang'] as $opt): ?>
                  <label class="flex items-center gap-3 cursor-pointer rounded-lg border border-gray-200 px-4 py-3 hover:bg-green-50 has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                    <input type="radio" name="jenjang" value="<?= sanitize($opt) ?>" required
                           <?= ($formData['jenjang'] ?? '') === $opt ? 'checked' : '' ?>
                           class="text-green-700 focus:ring-green-600">
                    <span class="font-medium"><?= sanitize($opt) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </fieldset>
            <?php endif; ?>
          </div>

          <?php if ($layanan['tipe'] === 'jumlah'): ?>
          <div class="border-t border-gray-200 pt-8">
            <div class="rounded-xl border border-green-200 bg-green-50 p-5 mb-5">
              <p class="text-sm font-semibold text-green-900"><?= sanitize($layanan['label']) ?></p>
            </div>
            <label for="jumlah" class="block text-sm font-semibold text-gray-700 mb-2">
              <?= sanitize(strtoupper($layanan['jumlah_label'])) ?> <span class="text-red-500">*</span>
            </label>
            <input type="number" id="jumlah" name="jumlah" min="1" max="9999" required
                   value="<?= fieldValue('jumlah', $formData) ?>"
                   class="w-32 rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600">
          </div>
          <?php elseif ($layanan['tipe'] === 'batik'): ?>
          <?php $selectedBatik = parseJenisBatikSelected($formData['jenis_batik'] ?? []); ?>
          <div class="border-t border-gray-200 pt-8 space-y-6">
            <fieldset>
              <legend class="block text-sm font-semibold text-gray-700 mb-3">JENIS PEMESANAN <span class="text-red-500">*</span></legend>
              <p class="text-xs text-gray-500 mb-3">Bisa memilih lebih dari satu.</p>
              <div class="space-y-3">
                <?php foreach (jenisBatikOptions() as $opt): ?>
                  <label class="flex items-center gap-3 cursor-pointer rounded-lg border border-gray-200 px-4 py-3 hover:bg-green-50 has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                    <input type="checkbox" name="jenis_batik[]" value="<?= sanitize($opt) ?>"
                           <?= in_array($opt, $selectedBatik, true) ? 'checked' : '' ?>
                           class="text-green-700 focus:ring-green-600 rounded">
                    <span class="font-medium"><?= sanitize($opt) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </fieldset>

            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Satuan 1</label>
                <select name="satuan_jenis_1" class="w-full rounded-lg border border-gray-300 px-3 py-2 mb-2 focus:ring-2 focus:ring-green-600">
                  <option value="">-- Pilih --</option>
                  <?php foreach (satuanBatikOptions() as $opt): ?>
                    <option value="<?= sanitize($opt) ?>" <?= ($formData['satuan_jenis_1'] ?? '') === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="number" name="satuan_jumlah_1" min="0" max="9999" placeholder="Jumlah"
                       value="<?= fieldValue('satuan_jumlah_1', $formData) ?>"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-green-600">
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Satuan 2 (opsional)</label>
                <select name="satuan_jenis_2" class="w-full rounded-lg border border-gray-300 px-3 py-2 mb-2 focus:ring-2 focus:ring-green-600">
                  <option value="">-- Pilih --</option>
                  <?php foreach (satuanBatikOptions() as $opt): ?>
                    <option value="<?= sanitize($opt) ?>" <?= ($formData['satuan_jenis_2'] ?? '') === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="number" name="satuan_jumlah_2" min="0" max="9999" placeholder="Jumlah"
                       value="<?= fieldValue('satuan_jumlah_2', $formData) ?>"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-green-600">
              </div>
            </div>

            <div>
              <p class="text-sm font-semibold text-gray-700 mb-3">Jumlah per Ukuran (opsional)</p>
              <div class="grid grid-cols-5 gap-2">
                <?php foreach (['S' => 'ukuran_s', 'M' => 'ukuran_m', 'L' => 'ukuran_l', 'XL' => 'ukuran_xl', 'XXL' => 'ukuran_xxl'] as $label => $name): ?>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 text-center"><?= $label ?></label>
                    <input type="number" name="<?= $name ?>" min="0" max="9999"
                           value="<?= fieldValue($name, $formData) ?>"
                           class="w-full rounded-lg border border-gray-300 px-2 py-2 text-sm text-center focus:ring-2 focus:ring-green-600">
                  </div>
                <?php endforeach; ?>
              </div>
              <p class="text-xs text-gray-500 mt-2">Isi satuan Roll/Meter dan/atau jumlah ukuran baju.</p>
            </div>
          </div>
          <?php elseif ($layanan['tipe'] === 'kenuan'): ?>
          <?php
            $kelasFields = bukuKenuanKelasFields();
            $selectedKenuanJenjang = normalizeJenjangPemesanan($formData['jenjang'] ?? '');
            if (!in_array($selectedKenuanJenjang, bukuKenuanJenjangOptions(), true)) {
                $selectedKenuanJenjang = '';
            }
          ?>
          <div class="border-t border-gray-200 pt-8 space-y-6" id="kenuan-form">
            <fieldset>
              <legend class="block text-sm font-semibold text-gray-700 mb-3">PILIH JENJANG <span class="text-red-500">*</span></legend>
              <div class="space-y-3">
                <?php foreach (bukuKenuanJenjangOptions() as $opt): ?>
                  <label class="flex items-center gap-3 cursor-pointer rounded-lg border border-gray-200 px-4 py-3 hover:bg-green-50 has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                    <input type="radio" name="jenjang" value="<?= sanitize($opt) ?>"
                           <?= $selectedKenuanJenjang === $opt ? 'checked' : '' ?>
                           class="kenuan-jenjang-radio text-green-700 focus:ring-green-600">
                    <span class="font-medium"><?= sanitize($opt) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </fieldset>

            <div id="kenuan-kelas-panel" class="<?= $selectedKenuanJenjang !== '' ? '' : 'hidden' ?>">
              <p class="text-sm font-semibold text-gray-700 mb-1">JUMLAH BUKU PER KELAS <span class="text-red-500">*</span></p>
              <p class="text-xs text-gray-500 mb-4">Isi jumlah buku minimal satu kelas pada jenjang yang dipilih.</p>
              <?php foreach (bukuKenuanKelasGroups() as $groupLabel => $groupKeys): ?>
                <div class="kenuan-kelas-group <?= $selectedKenuanJenjang === $groupLabel ? '' : 'hidden' ?>"
                     data-jenjang="<?= sanitize($groupLabel) ?>">
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <?php foreach ($groupKeys as $key): ?>
                      <div>
                        <label for="<?= sanitize($key) ?>" class="block text-xs font-medium text-gray-600 mb-1"><?= sanitize($kelasFields[$key]) ?></label>
                        <input type="number" id="<?= sanitize($key) ?>" name="<?= sanitize($key) ?>" min="0" max="9999"
                               value="<?= fieldValue($key, $formData) ?>"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-green-600">
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div>
            <label for="catatan" class="block text-sm font-semibold text-gray-700 mb-2">Catatan (opsional)</label>
            <textarea id="catatan" name="catatan" rows="3"
                      class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600"><?= fieldValue('catatan', $formData) ?></textarea>
          </div>

          <button type="submit"
                  class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-4 rounded-xl shadow transition">
            Kirim Pemesanan
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </main>

  <script>
    (function () {
      var form = document.getElementById('form-pemesanan');
      if (!form) return;
      form.addEventListener('submit', function (e) {
        var batikBoxes = form.querySelectorAll('input[name="jenis_batik[]"]');
        if (batikBoxes.length > 0) {
          var anyChecked = false;
          batikBoxes.forEach(function (box) { if (box.checked) anyChecked = true; });
          if (!anyChecked) {
            e.preventDefault();
            alert('Pilih minimal satu Jenis Pemesanan Batik.');
            return;
          }
        }
        var kenuanPanel = document.getElementById('kenuan-kelas-panel');
        if (kenuanPanel) {
          var jenjangSelected = form.querySelector('input.kenuan-jenjang-radio:checked');
          if (!jenjangSelected) {
            e.preventDefault();
            alert('Pilih jenjang terlebih dahulu (MI/SD, MTS/SMP, atau MA/SMA/SMK).');
            return;
          }
          var visibleGroup = kenuanPanel.querySelector('.kenuan-kelas-group:not(.hidden)');
          var anyKelas = false;
          if (visibleGroup) {
            visibleGroup.querySelectorAll('input[type="number"]').forEach(function (input) {
              if (parseInt(input.value || '0', 10) > 0) anyKelas = true;
            });
          }
          if (!anyKelas) {
            e.preventDefault();
            alert('Isi jumlah buku minimal satu kelas.');
            return;
          }
        }
        var btn = form.querySelector('button[type="submit"]');
        if (btn) { btn.disabled = true; btn.textContent = 'Menyimpan...'; }
      });

      var kenuanPanel = document.getElementById('kenuan-kelas-panel');
      if (kenuanPanel) {
        var groups = kenuanPanel.querySelectorAll('.kenuan-kelas-group');
        var radios = form.querySelectorAll('input.kenuan-jenjang-radio');
        function syncKenuanJenjang() {
          var selected = '';
          radios.forEach(function (r) { if (r.checked) selected = r.value; });
          if (!selected) {
            kenuanPanel.classList.add('hidden');
            groups.forEach(function (g) { g.classList.add('hidden'); });
            return;
          }
          kenuanPanel.classList.remove('hidden');
          groups.forEach(function (g) {
            var active = g.dataset.jenjang === selected;
            g.classList.toggle('hidden', !active);
            if (!active) {
              g.querySelectorAll('input[type="number"]').forEach(function (i) { i.value = '0'; });
            }
          });
        }
        radios.forEach(function (r) { r.addEventListener('change', syncKenuanJenjang); });
        syncKenuanJenjang();
      }
    })();
  </script>
</body>
</html>
