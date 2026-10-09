<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/jalan_sehat_functions.php';

$errors = [];
$formData = jalanSehatFormDefaults();
$settings = jalanSehatPengaturanDefaults();
$paketAktif = [];
$dbError = false;

try {
    $settings = getJalanSehatPengaturan();
    $paketAktif = loadJalanSehatPaket(true);
} catch (PDOException $e) {
    $dbError = true;
}

$pendaftaranDibuka = $settings['pendaftaran_dibuka'] === '1';
$izinkanBebas = $settings['izinkan_jumlah_bebas'] === '1' || $paketAktif === [];
$adaHarga = jalanSehatAdaHarga($settings);
$hargaKupon = (int) $settings['harga_kupon'];
$hargaKaos = (int) $settings['harga_kaos'];

$lastSubmission = null;
if (isset($_GET['success']) && isset($_SESSION['jalan_sehat_last'])) {
    $lastSubmission = $_SESSION['jalan_sehat_last'];
}

if (!$dbError && $pendaftaranDibuka && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = validateJalanSehat($_POST, $settings, $paketAktif);
    $formData = array_merge($formData, $result['data']);

    if (!empty($result['errors'])) {
        $errors = $result['errors'];
    } else {
        try {
            addJalanSehatPendaftaran($result['data']);
            $_SESSION['jalan_sehat_last'] = $result['data'];
            header('Location: ' . url('jalansehat/?success=1'));
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan pendaftaran. Silakan coba lagi atau hubungi panitia.';
        }
    }
}

if ($formData['pilihan_paket'] === '' && $paketAktif === []) {
    $formData['pilihan_paket'] = 'custom';
}

function jsFieldValue(string $key, array $formData): string
{
    return sanitize((string) ($formData[$key] ?? ''));
}

$inputClass = 'w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent bg-white';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?= siteHeadIcons() ?>
  <title><?= sanitize(JALAN_SEHAT_TITLE) ?> | LP Ma'arif NU Magelang</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

  <header class="bg-green-800 text-white shadow-lg">
    <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-4">
        <img src="<?= url('image/logo.png') ?>" alt="Logo LP Ma'arif NU" class="w-12 h-12 rounded-full bg-white p-1">
        <div>
          <h1 class="text-lg md:text-xl font-bold">LP Ma'arif NU Kabupaten Magelang</h1>
          <p class="text-sm text-green-100">Jalan Sehat Hari Santri Nasional 2026</p>
        </div>
      </div>
      <a href="<?= url('dashboard') ?>" class="text-sm bg-green-900 hover:bg-green-950 px-4 py-2 rounded-lg transition whitespace-nowrap">← Layanan</a>
    </div>
  </header>

  <main class="max-w-3xl w-full mx-auto px-6 py-10 flex-1">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-green-100">
      <div class="bg-green-700 text-white px-8 py-6">
        <h2 class="text-xl md:text-2xl font-bold leading-snug"><?= sanitize(JALAN_SEHAT_TITLE) ?></h2>
        <p class="text-green-100 mt-2"><?= sanitize(JALAN_SEHAT_SUBTITLE) ?></p>
      </div>

      <div class="px-8 py-8">
        <?php if ($dbError): ?>
          <div class="rounded-xl bg-red-50 border border-red-200 px-6 py-5 text-red-800">
            Layanan sedang tidak dapat diakses. Silakan coba beberapa saat lagi.
          </div>
        <?php elseif ($lastSubmission !== null): ?>
          <?php
            $kuponOk = (int) $lastSubmission['jumlah_kupon'];
            $kaosOk = (int) $lastSubmission['jumlah_kaos'];
          ?>
          <div class="rounded-xl bg-green-50 border border-green-200 px-6 py-5 text-green-800">
            <h3 class="font-semibold text-lg mb-1">Pendaftaran Berhasil Tersimpan!</h3>
            <p class="text-sm">Terima kasih, pendaftaran Jalan Sehat untuk madrasah Anda sudah kami terima.</p>
            <dl class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-2 text-sm bg-white rounded-lg border border-green-100 p-4">
              <dt class="text-gray-500">Madrasah</dt>
              <dd class="sm:col-span-2 font-semibold text-gray-800"><?= sanitize((string) $lastSubmission['nama_madrasah']) ?></dd>
              <dt class="text-gray-500">Kepala</dt>
              <dd class="sm:col-span-2 text-gray-800"><?= sanitize((string) $lastSubmission['nama_kepala']) ?></dd>
              <?php if (!empty($lastSubmission['paket_nama'])): ?>
                <dt class="text-gray-500">Paket</dt>
                <dd class="sm:col-span-2 text-gray-800"><?= sanitize((string) $lastSubmission['paket_nama']) ?></dd>
              <?php endif; ?>
              <dt class="text-gray-500">Kupon door prize</dt>
              <dd class="sm:col-span-2 font-semibold text-gray-800"><?= $kuponOk ?> kupon</dd>
              <dt class="text-gray-500">Kaos</dt>
              <dd class="sm:col-span-2 font-semibold text-gray-800">
                <?= $kaosOk ?> pcs
                <?php $rincianOk = formatRincianUkuranKaos($lastSubmission); ?>
                <?php if ($rincianOk !== ''): ?><span class="font-normal text-gray-600">(<?= sanitize($rincianOk) ?>)</span><?php endif; ?>
              </dd>
              <?php if ($adaHarga): ?>
                <dt class="text-gray-500">Estimasi biaya</dt>
                <dd class="sm:col-span-2 font-bold text-green-800"><?= sanitize(formatRupiahJalanSehat(hitungBiayaJalanSehat($kuponOk, $kaosOk, $settings))) ?></dd>
              <?php endif; ?>
            </dl>
            <a href="<?= url('jalansehat/') ?>"
               class="inline-block mt-4 bg-green-700 hover:bg-green-800 text-white font-semibold px-5 py-2.5 rounded-lg transition">
              Daftarkan Madrasah Lain
            </a>
          </div>
        <?php elseif (!$pendaftaranDibuka): ?>
          <div class="rounded-xl bg-amber-50 border border-amber-200 px-6 py-5 text-amber-900">
            <h3 class="font-semibold text-lg mb-1">Pendaftaran Ditutup</h3>
            <p class="text-sm">Pendaftaran Jalan Sehat Hari Santri Nasional 2026 saat ini sudah ditutup. Silakan hubungi panitia untuk informasi lebih lanjut.</p>
          </div>
        <?php else: ?>

        <p class="text-sm text-gray-600 mb-6 leading-relaxed">
          Silakan isi formulir di bawah ini untuk mendaftarkan madrasah Anda pada kegiatan
          <strong>Jalan Sehat Hari Santri Nasional 2026 PCNU Kabupaten Magelang</strong>.
          Satu formulir untuk satu madrasah.
        </p>

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

        <form method="post" action="" id="form-jalan-sehat" class="space-y-8" novalidate>
          <div class="rounded-xl border border-gray-200 bg-gray-50/80 p-5 space-y-5">
            <h3 class="text-sm font-bold text-green-900 uppercase tracking-wide">Data Madrasah</h3>

            <div>
              <label for="nama_madrasah" class="block text-sm font-semibold text-gray-700 mb-2">
                1. Nama Madrasah <span class="text-red-500">*</span>
              </label>
              <input type="text" id="nama_madrasah" name="nama_madrasah" required maxlength="200"
                     placeholder="Contoh: MI Ma'arif Donorejo"
                     value="<?= jsFieldValue('nama_madrasah', $formData) ?>" class="<?= $inputClass ?>">
            </div>

            <?php
              $wilayahSectionTitle = '2. Alamat Madrasah';
              require dirname(__DIR__) . '/pesertakerdinma/_wilayah_registrasi_fields.php';
            ?>

            <div>
              <label for="nama_kepala" class="block text-sm font-semibold text-gray-700 mb-2">
                3. Nama Kepala Madrasah <span class="text-red-500">*</span>
              </label>
              <input type="text" id="nama_kepala" name="nama_kepala" required maxlength="150"
                     value="<?= jsFieldValue('nama_kepala', $formData) ?>" class="<?= $inputClass ?>">
            </div>

            <div>
              <label for="nomor_wa" class="block text-sm font-semibold text-gray-700 mb-2">
                Nomor WhatsApp yang bisa dihubungi <span class="text-gray-400 font-normal">(opsional)</span>
              </label>
              <input type="tel" id="nomor_wa" name="nomor_wa" placeholder="08xxxxxxxxxx" maxlength="20"
                     value="<?= jsFieldValue('nomor_wa', $formData) ?>" class="<?= $inputClass ?> md:max-w-md">
            </div>
          </div>

          <div class="rounded-xl border border-green-200 bg-green-50/50 p-5 space-y-5">
            <div>
              <h3 class="text-sm font-bold text-green-900 uppercase tracking-wide">4. Jumlah Pemesanan</h3>
              <p class="text-xs text-gray-600 mt-1">Pesanan terdiri dari <strong>kupon door prize</strong> dan <strong>kaos</strong>.</p>
            </div>

            <?php if ($settings['info_surat_edaran'] !== '' || $adaHarga): ?>
              <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold mb-1">Ketentuan sesuai Surat Edaran</p>
                <?php if ($settings['info_surat_edaran'] !== ''): ?>
                  <p class="leading-relaxed"><?= nl2br(sanitize($settings['info_surat_edaran'])) ?></p>
                <?php endif; ?>
                <?php if ($adaHarga): ?>
                  <ul class="mt-2 space-y-0.5">
                    <?php if ($hargaKupon > 0): ?><li>• Kupon door prize: <strong><?= sanitize(formatRupiahJalanSehat($hargaKupon)) ?></strong> / kupon</li><?php endif; ?>
                    <?php if ($hargaKaos > 0): ?><li>• Kaos: <strong><?= sanitize(formatRupiahJalanSehat($hargaKaos)) ?></strong> / pcs</li><?php endif; ?>
                  </ul>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($paketAktif !== []): ?>
              <div>
                <p class="text-sm font-semibold text-gray-700 mb-2">
                  Langkah 1 — Pilih salah satu paket <span class="text-red-500">*</span>
                </p>
                <div class="grid sm:grid-cols-2 gap-3" id="paket-options">
                  <?php foreach ($paketAktif as $paket): ?>
                    <?php
                      $pid = (string) $paket['id'];
                      $pKupon = (int) $paket['jumlah_kupon'];
                      $pKaos = (int) $paket['jumlah_kaos'];
                    ?>
                    <label class="paket-card relative flex cursor-pointer rounded-xl border-2 border-gray-200 bg-white p-4 hover:border-green-400 transition has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                      <input type="radio" name="pilihan_paket" value="<?= sanitize($pid) ?>" class="sr-only paket-radio"
                             data-kupon="<?= $pKupon ?>" data-kaos="<?= $pKaos ?>"
                             <?= $formData['pilihan_paket'] === $pid ? 'checked' : '' ?>>
                      <span class="flex-1">
                        <span class="block font-bold text-green-800"><?= sanitize((string) $paket['nama']) ?></span>
                        <span class="block text-sm text-gray-700 mt-1"><?= sanitize(formatPaketJalanSehat($pKupon, $pKaos)) ?></span>
                        <?php if (!empty($paket['keterangan'])): ?>
                          <span class="block text-xs text-gray-500 mt-1"><?= sanitize((string) $paket['keterangan']) ?></span>
                        <?php endif; ?>
                        <?php if ($adaHarga): ?>
                          <span class="block text-sm font-semibold text-green-700 mt-2"><?= sanitize(formatRupiahJalanSehat(hitungBiayaJalanSehat($pKupon, $pKaos, $settings))) ?></span>
                        <?php endif; ?>
                      </span>
                      <span class="paket-check hidden absolute top-3 right-3 w-6 h-6 rounded-full bg-green-600 text-white text-xs font-bold items-center justify-center">✓</span>
                    </label>
                  <?php endforeach; ?>
                  <?php if ($izinkanBebas): ?>
                    <label class="paket-card relative flex cursor-pointer rounded-xl border-2 border-dashed border-gray-300 bg-white p-4 hover:border-green-400 transition has-[:checked]:border-green-600 has-[:checked]:bg-green-50">
                      <input type="radio" name="pilihan_paket" value="custom" class="sr-only paket-radio"
                             <?= $formData['pilihan_paket'] === 'custom' ? 'checked' : '' ?>>
                      <span class="flex-1">
                        <span class="block font-bold text-gray-800">Isi jumlah sendiri</span>
                        <span class="block text-sm text-gray-600 mt-1">Tentukan sendiri jumlah kupon dan kaos yang dipesan.</span>
                      </span>
                      <span class="paket-check hidden absolute top-3 right-3 w-6 h-6 rounded-full bg-green-600 text-white text-xs font-bold items-center justify-center">✓</span>
                    </label>
                  <?php endif; ?>
                </div>
              </div>
            <?php else: ?>
              <input type="hidden" name="pilihan_paket" value="custom">
            <?php endif; ?>

            <div id="jumlah-section">
              <?php if ($paketAktif !== []): ?>
                <p class="text-sm font-semibold text-gray-700 mb-1">Langkah 2 — Periksa jumlah pesanan</p>
                <p id="jumlah-hint" class="text-xs text-gray-500 mb-3">Pilih paket terlebih dahulu. Jumlah akan terisi otomatis sesuai paket.</p>
              <?php else: ?>
                <p class="text-xs text-gray-500 mb-3">Isi angka saja. Tulis <strong>0</strong> jika tidak memesan salah satunya.</p>
              <?php endif; ?>

              <div class="space-y-5">
                <div>
                  <label for="jumlah_kupon" class="block text-sm font-semibold text-gray-700 mb-2">a. Kupon Door Prize</label>
                  <div class="flex items-stretch sm:max-w-xs">
                    <input type="number" id="jumlah_kupon" name="jumlah_kupon" min="0" step="1" inputmode="numeric"
                           value="<?= (int) $formData['jumlah_kupon'] ?>"
                           class="<?= $inputClass ?> rounded-r-none text-lg font-semibold read-only:bg-gray-100 read-only:text-gray-600">
                    <span class="inline-flex items-center px-4 rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 text-sm text-gray-600">kupon</span>
                  </div>
                  <?php if ($hargaKupon > 0): ?>
                    <p class="text-xs text-gray-500 mt-1"><?= sanitize(formatRupiahJalanSehat($hargaKupon)) ?> / kupon</p>
                  <?php endif; ?>
                </div>
                <div id="kaos-block" class="transition space-y-5">
                  <div>
                    <p class="text-sm font-semibold text-gray-700">b. Kaos — pilih model & ukuran</p>
                    <p id="kaos-hint" class="text-xs text-gray-500 mb-3">
                      Isi jumlah per ukuran untuk <strong>lengan pendek</strong> dan/atau <strong>lengan panjang</strong>.
                      Biarkan <strong>0</strong> jika tidak dipesan.
                      <?php if ($hargaKaos > 0): ?>Harga <?= sanitize(formatRupiahJalanSehat($hargaKaos)) ?> / pcs.<?php endif; ?>
                    </p>
                    <div class="grid sm:grid-cols-2 gap-3 mb-4">
                      <figure class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                        <img src="<?= url('image/jalansehat-kaos-pendek.jpg') ?>" alt="Desain kaos lengan pendek" class="w-full h-auto object-cover">
                        <figcaption class="text-xs text-center py-2 font-semibold text-green-800 bg-green-50">Model Lengan Pendek</figcaption>
                      </figure>
                      <figure class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                        <img src="<?= url('image/jalansehat-kaos-panjang.jpg') ?>" alt="Desain kaos lengan panjang" class="w-full h-auto object-cover">
                        <figcaption class="text-xs text-center py-2 font-semibold text-green-800 bg-green-50">Model Lengan Panjang</figcaption>
                      </figure>
                    </div>
                    <details class="rounded-xl border border-amber-200 bg-amber-50/80 mb-4 group">
                      <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-amber-900 list-none flex items-center justify-between">
                        Panduan ukuran kaos (cm)
                        <span class="text-xs font-normal text-amber-800 group-open:hidden">Klik untuk lihat</span>
                      </summary>
                      <div class="px-4 pb-4 overflow-x-auto">
                        <table class="w-full min-w-[280px] text-sm border border-amber-200 bg-white rounded-lg overflow-hidden">
                          <thead>
                            <tr class="bg-red-600 text-white">
                              <th class="px-3 py-2 text-center font-bold">SIZE</th>
                              <th class="px-3 py-2 text-center font-bold">LEBAR</th>
                              <th class="px-3 py-2 text-center font-bold">TINGGI</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach (jalanSehatPanduanUkuranKaos() as $size => $uk): ?>
                              <tr class="border-t border-amber-100">
                                <td class="px-3 py-2 text-center font-bold"><?= sanitize($size) ?></td>
                                <td class="px-3 py-2 text-center"><?= (int) $uk['lebar'] ?> CM</td>
                                <td class="px-3 py-2 text-center"><?= (int) $uk['tinggi'] ?> CM</td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                        <img src="<?= url('image/jalansehat-ukuran-kaos.jpg') ?>" alt="Tabel ukuran kaos" class="mt-3 rounded-lg border border-amber-200 max-w-md w-full">
                      </div>
                    </details>
                  </div>
                  <?php foreach (jalanSehatUkuranKaosByJenis() as $jenisKey => $jenisInfo): ?>
                    <div class="rounded-xl border border-green-200 bg-white p-4">
                      <p class="text-sm font-bold text-green-800 mb-2"><?= sanitize($jenisInfo['label']) ?></p>
                      <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                        <?php foreach ($jenisInfo['columns'] as $label => $column): ?>
                          <label class="block rounded-lg border border-gray-300 bg-gray-50 p-2 text-center focus-within:ring-2 focus-within:ring-green-600">
                            <span class="block text-sm font-bold text-green-800"><?= sanitize($label) ?></span>
                            <input type="number" name="<?= sanitize($column) ?>"
                                   data-label="<?= sanitize($label) ?>"
                                   data-jenis="<?= sanitize($jenisKey === 'pendek' ? 'Pendek' : 'Panjang') ?>"
                                   min="0" step="1" inputmode="numeric"
                                   value="<?= (int) ($formData[$column] ?? 0) ?>"
                                   aria-label="Kaos <?= sanitize($jenisInfo['label']) ?> ukuran <?= sanitize($label) ?>"
                                   class="kaos-ukuran mt-1 w-full rounded-md border border-gray-200 px-1 py-1.5 text-center text-lg font-semibold focus:outline-none bg-white">
                          </label>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                  <p id="kaos-status" class="text-sm font-semibold text-gray-700">Total kaos: 0 pcs</p>
                </div>
              </div>
            </div>

            <div id="ringkasan-pesanan" class="rounded-lg bg-white border border-green-200 px-4 py-3 text-sm">
              <p class="text-gray-500">Ringkasan pesanan Anda:</p>
              <p id="ringkasan-teks" class="font-bold text-green-800 text-base mt-1">-</p>
              <?php if ($adaHarga): ?>
                <p class="mt-1 text-gray-700">Estimasi biaya: <strong id="ringkasan-biaya" class="text-green-800">Rp 0</strong></p>
              <?php endif; ?>
            </div>
          </div>

          <div>
            <label for="catatan" class="block text-sm font-semibold text-gray-700 mb-2">
              Catatan <span class="text-gray-400 font-normal">(opsional)</span>
            </label>
            <textarea id="catatan" name="catatan" rows="3" placeholder="Informasi tambahan untuk panitia (jika ada)"
                      class="<?= $inputClass ?>"><?= jsFieldValue('catatan', $formData) ?></textarea>
          </div>

          <button type="submit" id="btn-submit"
                  class="w-full bg-green-700 hover:bg-green-800 text-white font-bold px-6 py-4 rounded-xl shadow transition disabled:opacity-60">
            Kirim Pendaftaran
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <footer class="bg-green-900 text-green-100 py-6 mt-10">
    <div class="max-w-3xl mx-auto px-6 text-center text-sm">
      © 2026 LP Ma'arif NU Kabupaten Magelang
    </div>
  </footer>

  <?php if (!$dbError && $pendaftaranDibuka && $lastSubmission === null): ?>
  <?php require dirname(__DIR__) . '/pesertakerdinma/_wilayah_registrasi_script.php'; ?>
  <script>
    (function () {
      const form = document.getElementById('form-jalan-sehat');
      const kupon = document.getElementById('jumlah_kupon');
      const ukuranInputs = Array.from(document.querySelectorAll('.kaos-ukuran'));
      const kaosBlock = document.getElementById('kaos-block');
      const kaosStatus = document.getElementById('kaos-status');
      const radios = Array.from(document.querySelectorAll('.paket-radio'));
      const hint = document.getElementById('jumlah-hint');
      const teks = document.getElementById('ringkasan-teks');
      const biaya = document.getElementById('ringkasan-biaya');
      const hargaKupon = <?= $hargaKupon ?>;
      const hargaKaos = <?= $hargaKaos ?>;

      // mode: 'none' (belum pilih paket), 'custom', atau 'paket'
      let mode = radios.length === 0 ? 'custom' : 'none';
      let targetKaos = 0;

      function toInt(el) {
        const n = parseInt(el.value, 10);
        return Number.isFinite(n) && n > 0 ? n : 0;
      }

      function rupiah(n) {
        return 'Rp ' + n.toLocaleString('id-ID');
      }

      function totalUkuran() {
        return ukuranInputs.reduce(function (sum, el) { return sum + toInt(el); }, 0);
      }

      function rincianUkuran() {
        return ukuranInputs
          .filter(function (el) { return toInt(el) > 0; })
          .map(function (el) {
            var jenis = el.dataset.jenis ? el.dataset.jenis + ' ' : '';
            return jenis + el.dataset.label + ': ' + toInt(el);
          })
          .join(', ');
      }

      function jumlahKaos() {
        return mode === 'paket' ? targetKaos : totalUkuran();
      }

      function setKaosAktif(aktif) {
        kaosBlock.classList.toggle('opacity-50', !aktif);
        kaosBlock.classList.toggle('pointer-events-none', !aktif);
        ukuranInputs.forEach(function (el) { el.readOnly = !aktif; });
      }

      function updateKaosStatus() {
        const total = totalUkuran();
        if (mode === 'none') {
          kaosStatus.textContent = 'Pilih paket terlebih dahulu.';
          kaosStatus.className = 'mt-2 text-sm font-semibold text-gray-500';
        } else if (mode === 'paket') {
          if (total === targetKaos) {
            kaosStatus.textContent = '✓ Rincian ukuran lengkap: ' + total + ' dari ' + targetKaos + ' kaos.';
            kaosStatus.className = 'mt-2 text-sm font-semibold text-green-700';
          } else if (total < targetKaos) {
            kaosStatus.textContent = 'Terisi ' + total + ' dari ' + targetKaos + ' kaos — kurang ' + (targetKaos - total) + ' pcs lagi.';
            kaosStatus.className = 'mt-2 text-sm font-semibold text-amber-600';
          } else {
            kaosStatus.textContent = 'Terisi ' + total + ' dari ' + targetKaos + ' kaos — kelebihan ' + (total - targetKaos) + ' pcs.';
            kaosStatus.className = 'mt-2 text-sm font-semibold text-red-600';
          }
        } else {
          kaosStatus.textContent = 'Total kaos: ' + total + ' pcs';
          kaosStatus.className = 'mt-2 text-sm font-semibold text-gray-700';
        }
      }

      function updateRingkasan() {
        updateKaosStatus();
        const k = toInt(kupon);
        const s = jumlahKaos();
        const parts = [];
        if (k > 0) parts.push(k + ' kupon door prize');
        if (s > 0) {
          const rincian = rincianUkuran();
          parts.push(s + ' kaos' + (rincian ? ' (' + rincian + ')' : ''));
        }
        teks.textContent = parts.length ? parts.join(' + ') : 'Belum ada pesanan';
        teks.className = parts.length ? 'font-bold text-green-800 text-base mt-1' : 'font-semibold text-red-600 text-base mt-1';
        if (biaya) biaya.textContent = rupiah(k * hargaKupon + s * hargaKaos);
      }

      function applyPilihan() {
        const selected = radios.find(function (r) { return r.checked; });
        document.querySelectorAll('.paket-card').forEach(function (card) {
          const check = card.querySelector('.paket-check');
          const isChecked = card.querySelector('.paket-radio').checked;
          check.classList.toggle('hidden', !isChecked);
          check.classList.toggle('flex', isChecked);
        });

        kaosBlock.classList.remove('hidden');

        if (radios.length === 0) {
          mode = 'custom';
          setKaosAktif(true);
        } else if (!selected) {
          mode = 'none';
          kupon.readOnly = true;
          setKaosAktif(false);
          if (hint) hint.textContent = 'Pilih paket terlebih dahulu. Jumlah akan terisi otomatis sesuai paket.';
        } else if (selected.value === 'custom') {
          mode = 'custom';
          kupon.readOnly = false;
          setKaosAktif(true);
          if (hint) hint.textContent = 'Isi jumlah kupon dan jumlah kaos per ukuran sesuai kebutuhan.';
        } else {
          mode = 'paket';
          targetKaos = parseInt(selected.dataset.kaos, 10) || 0;
          kupon.value = selected.dataset.kupon;
          kupon.readOnly = true;
          if (targetKaos > 0) {
            setKaosAktif(true);
            if (hint) hint.textContent = 'Jumlah kupon terisi otomatis. Bagi ' + targetKaos + ' kaos dari paket ke dalam ukuran yang dibutuhkan.';
          } else {
            ukuranInputs.forEach(function (el) { el.value = 0; });
            kaosBlock.classList.add('hidden');
            if (hint) hint.textContent = 'Jumlah terisi otomatis sesuai paket yang dipilih.';
          }
        }
        updateRingkasan();
      }

      radios.forEach(function (r) { r.addEventListener('change', applyPilihan); });
      [kupon].concat(ukuranInputs).forEach(function (el) { el.addEventListener('input', updateRingkasan); });
      applyPilihan();

      form.addEventListener('submit', function (e) {
        const problems = [];
        [
          ['nama_madrasah', 'Nama madrasah wajib diisi.'],
          ['kode_kecamatan', 'Kecamatan wajib dipilih.'],
          ['kode_kelurahan', 'Desa/Kelurahan wajib dipilih.'],
          ['alamat_detail', 'Alamat detail wajib diisi.'],
          ['nama_kepala', 'Nama kepala madrasah wajib diisi.'],
        ].forEach(function (item) {
          const el = document.getElementById(item[0]);
          if (el && el.value.trim() === '') problems.push(item[1]);
        });
        if (radios.length > 0 && !radios.some(function (r) { return r.checked; })) {
          problems.push('Pilih salah satu paket pemesanan.');
        }
        if (mode === 'paket' && targetKaos > 0 && totalUkuran() !== targetKaos) {
          problems.push('Rincian ukuran kaos harus berjumlah ' + targetKaos + ' pcs sesuai paket (saat ini ' + totalUkuran() + ' pcs).');
        }
        if (mode !== 'paket' && toInt(kupon) === 0 && totalUkuran() === 0) {
          problems.push('Isi jumlah kupon door prize atau jumlah kaos per ukuran (minimal salah satu lebih dari 0).');
        }
        if (problems.length) {
          e.preventDefault();
          alert(problems.join('\n'));
          return;
        }
        const btn = document.getElementById('btn-submit');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';
      });
    })();
  </script>
  <?php endif; ?>

</body>
</html>
