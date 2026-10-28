<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/includes/jalan_sehat_functions.php';

const JALAN_SEHAT_TERIMA_PHONE = 'jalan_sehat_terima_phone';
const JALAN_SEHAT_TERIMA_IDS = 'jalan_sehat_terima_ids';
const JALAN_SEHAT_TERIMA_ID = 'jalan_sehat_terima_id';

$errors = [];
$lookupPhone = '';
$candidates = [];
$row = null;
$justConfirmed = isset($_GET['ok']);

if (isset($_GET['batal'])) {
    unset($_SESSION[JALAN_SEHAT_TERIMA_PHONE], $_SESSION[JALAN_SEHAT_TERIMA_IDS], $_SESSION[JALAN_SEHAT_TERIMA_ID]);
    header('Location: ' . url('jalansehat/tanda-terima'));
    exit;
}

$allowedIds = [];
if (isset($_SESSION[JALAN_SEHAT_TERIMA_IDS]) && is_array($_SESSION[JALAN_SEHAT_TERIMA_IDS])) {
    $allowedIds = array_values(array_filter(array_map('intval', $_SESSION[JALAN_SEHAT_TERIMA_IDS]), static fn (int $id): bool => $id > 0));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'lookup');

    if ($action === 'lookup') {
        unset($_SESSION[JALAN_SEHAT_TERIMA_PHONE], $_SESSION[JALAN_SEHAT_TERIMA_IDS], $_SESSION[JALAN_SEHAT_TERIMA_ID]);
        $allowedIds = [];
        $lookupPhone = trim((string) ($_POST['nomor_hp'] ?? ''));
        $norm = normalizeNomorWa($lookupPhone);
        if ($norm === '' || strlen($norm) < 9 || strlen($norm) > 15) {
            $errors[] = 'Masukkan nomor HP yang valid (contoh: 081234567890).';
        } else {
            try {
                $ids = findJalanSehatPendaftaranIdsByEditPhone($lookupPhone);
            } catch (PDOException) {
                $ids = [];
                $errors[] = 'Layanan sedang tidak dapat diakses. Silakan coba beberapa saat lagi.';
            }
            if ($errors === [] && $ids === []) {
                $errors[] = 'Tidak ada pendaftaran Jalan Sehat untuk nomor HP ini. Gunakan nomor WhatsApp atau nomor HP kepala yang diisi saat mendaftar.';
            } elseif ($errors === []) {
                $_SESSION[JALAN_SEHAT_TERIMA_PHONE] = $norm;
                $_SESSION[JALAN_SEHAT_TERIMA_IDS] = $ids;
                $allowedIds = $ids;
                if (count($ids) === 1) {
                    $_SESSION[JALAN_SEHAT_TERIMA_ID] = $ids[0];
                    header('Location: ' . url('jalansehat/tanda-terima'));
                    exit;
                }
                unset($_SESSION[JALAN_SEHAT_TERIMA_ID]);
            }
        }
    } elseif ($action === 'pilih') {
        $id = (int) ($_POST['pendaftaran_id'] ?? 0);
        if (!in_array($id, $allowedIds, true)) {
            $errors[] = 'Sesi verifikasi habis atau pilihan tidak valid. Masukkan nomor HP lagi.';
            $allowedIds = [];
            unset($_SESSION[JALAN_SEHAT_TERIMA_PHONE], $_SESSION[JALAN_SEHAT_TERIMA_IDS], $_SESSION[JALAN_SEHAT_TERIMA_ID]);
        } else {
            $_SESSION[JALAN_SEHAT_TERIMA_ID] = $id;
            header('Location: ' . url('jalansehat/tanda-terima'));
            exit;
        }
    } elseif ($action === 'konfirmasi') {
        $id = (int) ($_SESSION[JALAN_SEHAT_TERIMA_ID] ?? 0);
        $phone = (string) ($_SESSION[JALAN_SEHAT_TERIMA_PHONE] ?? '');
        if (!in_array($id, $allowedIds, true) || $phone === '') {
            $errors[] = 'Sesi verifikasi habis. Masukkan nomor HP lagi.';
        } else {
            $current = getJalanSehatPendaftaranById($id);
            if ($current === null) {
                $errors[] = 'Data pendaftaran tidak ditemukan.';
            } elseif (!empty($current['kaos_diambil_pada'])) {
                header('Location: ' . url('jalansehat/tanda-terima'));
                exit;
            } elseif ((int) $current['jumlah_kaos'] < 1) {
                $errors[] = 'Pendaftaran ini tidak memesan kaos, jadi tanda terima pengambilan tidak diperlukan.';
            } else {
                $nama = trim((string) ($_POST['penerima_nama'] ?? ''));
                if ($nama === '') {
                    $nama = (string) ($current['nama_kepala'] ?? '');
                }
                $jabatan = trim((string) ($_POST['penerima_jabatan'] ?? ''));
                if ($jabatan === '' || strcasecmp($jabatan, 'Konfirmasi elektronik') === 0) {
                    $jabatan = 'Kepala Madrasah';
                }
                $result = saveJalanSehatTandaTerimaKaos($id, [
                    'penerima_nama' => $nama,
                    'penerima_jabatan' => $jabatan,
                    'penerima_hp' => $phone,
                    'tanggal_ambil' => date('Y-m-d'),
                ]);
                if (!$result['ok']) {
                    $errors[] = $result['message'];
                } else {
                    header('Location: ' . url('jalansehat/tanda-terima?ok=1'));
                    exit;
                }
            }
        }
    }
}

$selectedId = (int) ($_SESSION[JALAN_SEHAT_TERIMA_ID] ?? 0);
if ($selectedId > 0 && in_array($selectedId, $allowedIds, true)) {
    $row = getJalanSehatPendaftaranById($selectedId);
} elseif ($allowedIds !== [] && $row === null && $errors === []) {
    foreach ($allowedIds as $id) {
        $candidate = getJalanSehatPendaftaranById($id);
        if ($candidate !== null) {
            $candidates[] = $candidate;
        }
    }
}

$verifiedPhone = (string) ($_SESSION[JALAN_SEHAT_TERIMA_PHONE] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?= siteHeadIcons() ?>
  <?= siteThemeHead() ?>
  <title>Tanda Terima Elektronik Kaos Jalan Sehat | LP Ma'arif NU Magelang</title>
  <?= siteTailwindScript() ?>
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: #fff !important; }
      .sheet { box-shadow: none !important; border: none !important; }
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

  <header class="bg-green-800 text-white shadow-lg no-print">
    <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
      <div class="flex items-center space-x-4">
        <img src="<?= url('image/logo.png') ?>" alt="Logo LP Ma'arif NU" class="w-12 h-12 rounded-full bg-white p-1">
        <div>
          <h1 class="text-lg md:text-xl font-bold">LP Ma'arif NU Kabupaten Magelang</h1>
          <p class="text-sm text-green-100">Tanda terima elektronik kaos</p>
        </div>
      </div>
      <a href="<?= url('jalansehat/') ?>" class="text-sm bg-green-900 hover:bg-green-950 px-4 py-2 rounded-lg transition whitespace-nowrap">← Form Daftar</a>
    </div>
  </header>

  <main class="max-w-3xl w-full mx-auto px-6 py-10 flex-1">
    <?php if ($row === null): ?>
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-green-100">
        <div class="bg-green-700 text-white px-8 py-6">
          <h2 class="text-xl md:text-2xl font-bold leading-snug">Tanda Terima Elektronik Pengambilan Kaos</h2>
          <p class="text-green-100 mt-2">Masukkan nomor HP yang dipakai saat pendaftaran Jalan Sehat.</p>
        </div>
        <div class="px-8 py-8 space-y-6">
          <p class="text-sm text-gray-600 leading-relaxed">
            Gunakan <strong>nomor WhatsApp</strong> atau <strong>nomor HP kepala madrasah</strong> yang diisi pada formulir pendaftaran.
            Setelah nomor cocok, rincian kaos tampil di layar dan bisa dikonfirmasi tanpa kertas.
          </p>
          <?php if (!empty($errors)): ?>
            <div class="rounded-xl bg-red-50 border border-red-200 px-6 py-5 text-red-800 text-sm">
              <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $error): ?>
                  <li><?= sanitize($error) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if ($candidates === []): ?>
            <form method="post" class="space-y-4">
              <input type="hidden" name="action" value="lookup">
              <div>
                <label for="nomor_hp" class="block text-sm font-semibold text-gray-700 mb-2">Nomor HP saat pendaftaran</label>
                <input type="tel" id="nomor_hp" name="nomor_hp" required placeholder="08xxxxxxxxxx" maxlength="20"
                       value="<?= sanitize($lookupPhone) ?>"
                       class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 bg-white md:max-w-md">
              </div>
              <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg">Lihat tanda terima</button>
            </form>
          <?php else: ?>
            <div class="rounded-xl border border-green-200 bg-green-50/60 px-5 py-4 text-sm text-green-900">
              Ditemukan <strong><?= count($candidates) ?></strong> pendaftaran. Pilih madrasah yang mengambil kaos.
            </div>
            <form method="post" class="space-y-3">
              <input type="hidden" name="action" value="pilih">
              <?php foreach ($candidates as $candidate): ?>
                <label class="flex cursor-pointer rounded-xl border-2 border-gray-200 bg-white p-4 hover:border-green-500 has-[:checked]:border-green-600 has-[:checked]:bg-green-50 transition">
                  <input type="radio" name="pendaftaran_id" value="<?= (int) $candidate['id'] ?>" required class="mt-1 mr-3">
                  <span>
                    <span class="block font-bold text-green-800"><?= sanitize((string) $candidate['nama_madrasah']) ?></span>
                    <span class="block text-sm text-gray-600 mt-1">Kepala: <?= sanitize((string) $candidate['nama_kepala']) ?></span>
                    <span class="block text-xs text-gray-500 mt-1">Kaos: <?= (int) $candidate['jumlah_kaos'] ?> pcs<?php if (!empty($candidate['kaos_diambil_pada'])): ?> · sudah dikonfirmasi<?php endif; ?></span>
                  </span>
                </label>
              <?php endforeach; ?>
              <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg">Tampilkan</button>
                <a href="<?= url('jalansehat/tanda-terima?batal=1') ?>" class="inline-flex items-center px-5 py-3 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">Ganti nomor HP</a>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <?php
        $kaos = (int) $row['jumlah_kaos'];
        $kupon = (int) $row['jumlah_kupon'];
        $qty = jalanSehatTotalKaosByJenis($row);
        $sizes = array_keys(jalanSehatUkuranKaosByJenis()['pendek']['columns']);
        $sudah = !empty($row['kaos_diambil_pada']);
        $jabatanTersimpan = trim((string) ($row['kaos_diambil_jabatan'] ?? ''));
        $elektronik = $sudah && strcasecmp($jabatanTersimpan, 'Konfirmasi elektronik') === 0;
        $jabatanTampil = jalanSehatJabatanPenerimaTampil($jabatanTersimpan);
        if ($jabatanTampil === '' && $elektronik) {
            $jabatanTampil = 'Kepala Madrasah';
        }
        $nomorSurat = nomorSuratTandaTerimaKaos($row);
        $rincian = formatRincianUkuranKaos($row);
      ?>
      <div class="no-print flex flex-wrap gap-3 mb-4">
        <a href="<?= url('jalansehat/tanda-terima?batal=1') ?>" class="text-sm text-green-800 font-semibold hover:underline">← Ganti nomor HP</a>
        <?php if ($sudah): ?>
          <button type="button" onclick="window.print()" class="text-sm bg-green-700 text-white font-semibold px-4 py-2 rounded-lg">Cetak / simpan PDF</button>
        <?php endif; ?>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="no-print mb-4 rounded-xl bg-red-50 border border-red-200 px-5 py-4 text-red-800 text-sm">
          <?php foreach ($errors as $error): ?><p><?= sanitize($error) ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($justConfirmed): ?>
        <div class="no-print mb-4 rounded-xl bg-green-50 border border-green-200 px-5 py-4 text-green-800 text-sm">
          Konfirmasi elektronik berhasil. Panitia sudah menerima tanda terima pengambilan kaos ini.
        </div>
      <?php endif; ?>

      <article class="sheet bg-white rounded-2xl shadow-lg border border-green-100 overflow-hidden">
        <div class="bg-green-700 text-white px-6 py-5 flex gap-4 items-center">
          <img src="<?= url('image/logo.png') ?>" alt="" class="w-14 h-14 rounded-full bg-white p-1">
          <div>
            <p class="text-xs uppercase tracking-wide text-green-100">LP Ma'arif NU PCNU Kabupaten Magelang</p>
            <h2 class="text-lg font-bold leading-snug">Tanda Terima Elektronik Pengambilan Kaos</h2>
            <p class="text-sm text-green-100">Jalan Sehat Hari Santri Nasional 2026</p>
          </div>
        </div>
        <div class="px-6 py-6 space-y-5 text-sm">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="font-semibold text-gray-700">Nomor: <?= sanitize($nomorSurat) ?></p>
            <?php if ($sudah): ?>
              <span class="inline-flex rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1">
                <?= $elektronik ? 'Dikonfirmasi elektronik' : 'Sudah diambil' ?>
              </span>
            <?php else: ?>
              <span class="inline-flex rounded-full bg-amber-100 text-amber-800 text-xs font-bold px-3 py-1">Belum dikonfirmasi</span>
            <?php endif; ?>
          </div>

          <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-3">
            <dt class="text-gray-500">Madrasah</dt>
            <dd class="sm:col-span-2 font-semibold"><?= sanitize((string) $row['nama_madrasah']) ?></dd>
            <dt class="text-gray-500">Kepala</dt>
            <dd class="sm:col-span-2"><?= sanitize((string) $row['nama_kepala']) ?></dd>
            <dt class="text-gray-500">Kecamatan</dt>
            <dd class="sm:col-span-2"><?= sanitize((string) ($row['nama_kecamatan'] ?? '')) ?: '—' ?></dd>
            <dt class="text-gray-500">Nomor terverifikasi</dt>
            <dd class="sm:col-span-2"><?= sanitize($verifiedPhone) ?></dd>
          </dl>

          <div class="overflow-x-auto">
            <table class="w-full text-xs sm:text-sm border-collapse">
              <thead>
                <tr class="bg-green-50 text-green-900">
                  <th class="border border-green-100 px-2 py-2 text-left">Jenis kaos</th>
                  <?php foreach ($sizes as $sz): ?>
                    <th class="border border-green-100 px-2 py-2 text-center"><?= sanitize($sz) ?></th>
                  <?php endforeach; ?>
                  <th class="border border-green-100 px-2 py-2 text-center">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (jalanSehatUkuranKaosByJenis() as $jenisKey => $jenisInfo): ?>
                  <?php $subtotal = $jenisKey === 'pendek' ? (int) $qty['pendek'] : (int) $qty['panjang']; ?>
                  <tr>
                    <td class="border border-gray-200 px-2 py-2"><?= sanitize($jenisInfo['label']) ?></td>
                    <?php foreach ($jenisInfo['columns'] as $column): ?>
                      <?php $n = (int) ($row[$column] ?? 0); ?>
                      <td class="border border-gray-200 px-2 py-2 text-center"><?= $n > 0 ? (string) $n : '—' ?></td>
                    <?php endforeach; ?>
                    <td class="border border-gray-200 px-2 py-2 text-center font-bold"><?= $subtotal ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <p>Total kaos: <strong><?= $kaos ?> pcs</strong><?php if ($rincian !== ''): ?> (<?= sanitize($rincian) ?>)<?php endif; ?>.</p>
          <?php if ($kupon > 0): ?>
            <p>Kupon door prize: <strong><?= $kupon ?> lembar</strong>.</p>
          <?php endif; ?>

          <?php if ($sudah): ?>
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-4 text-emerald-900">
              <p class="font-semibold">Penerimaan tercatat</p>
              <p class="mt-1">Pada <?= sanitize(formatTanggalIndonesia((string) $row['kaos_diambil_pada'])) ?>,
                diterima oleh <?= sanitize((string) ($row['kaos_diambil_nama'] ?? '')) ?>
                <?php if ($jabatanTampil !== ''): ?>, <?= sanitize($jabatanTampil) ?><?php endif; ?>
                <?php if (!empty($row['kaos_diambil_hp'])): ?> (<?= sanitize((string) $row['kaos_diambil_hp']) ?>)<?php endif; ?>.</p>
              <?php if ($elektronik): ?>
                <p class="mt-1 text-xs">Konfirmasi ini dilakukan secara elektronik menggunakan nomor HP pendaftaran. Dokumen ini berlaku sebagai tanda terima.</p>
              <?php endif; ?>
            </div>
          <?php elseif ($kaos < 1): ?>
            <p class="text-amber-800">Pendaftaran ini tidak memesan kaos.</p>
          <?php else: ?>
            <form method="post" class="no-print rounded-xl border border-green-200 bg-green-50/70 px-4 py-4 space-y-3">
              <input type="hidden" name="action" value="konfirmasi">
              <p class="font-semibold text-green-900">Konfirmasi penerimaan</p>
              <p class="text-gray-700">Dengan menekan tombol di bawah, Anda menyatakan kaos sesuai rincian di atas sudah diterima dalam keadaan baik.</p>
              <div>
                <label for="penerima_nama" class="block text-xs font-semibold text-gray-700 mb-1">Nama yang menerima</label>
                <input id="penerima_nama" name="penerima_nama" required maxlength="150"
                       value="<?= sanitize((string) $row['nama_kepala']) ?>"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white">
              </div>
              <div>
                <label for="penerima_jabatan" class="block text-xs font-semibold text-gray-700 mb-1">Jabatan</label>
                <input id="penerima_jabatan" name="penerima_jabatan" required maxlength="120"
                       value="Kepala Madrasah"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white">
              </div>
              <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold px-5 py-3 rounded-lg">
                Saya sudah menerima kaos ini
              </button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endif; ?>
  </main>

  <footer class="bg-green-900 text-green-100 py-6 mt-10 no-print">
    <div class="max-w-3xl mx-auto px-6 text-center text-sm">© 2026 LP Ma'arif NU Kabupaten Magelang</div>
  </footer>
  <?= siteThemeFooterScript() ?>
</body>
</html>
