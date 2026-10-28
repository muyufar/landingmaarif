<?php

declare(strict_types=1);

/** @var array $row @var string $tandaTerimaError */
$kaos = (int) $row['jumlah_kaos'];
$kupon = (int) $row['jumlah_kupon'];
$qty = jalanSehatTotalKaosByJenis($row);
$sizes = array_keys(jalanSehatUkuranKaosByJenis()['pendek']['columns']);
$sudahDiambil = !empty($row['kaos_diambil_pada']);
$nomorSurat = nomorSuratTandaTerimaKaos($row);
$tanggalSurat = formatTanggalIndonesia($sudahDiambil ? (string) $row['kaos_diambil_pada'] : date('Y-m-d'));
$namaPenerima = trim((string) ($row['kaos_diambil_nama'] ?? ''));
if ($namaPenerima === '') {
    $namaPenerima = (string) ($row['nama_kepala'] ?? '');
}
$jabatanPenerima = trim((string) ($row['kaos_diambil_jabatan'] ?? ''));
$hpPenerima = trim((string) ($row['kaos_diambil_hp'] ?? ''));
if ($hpPenerima === '') {
    $hpPenerima = trim((string) (($row['nomor_hp_kepala'] ?? '') !== '' ? $row['nomor_hp_kepala'] : ($row['nomor_wa'] ?? '')));
}
$tanggalInput = $sudahDiambil ? date('Y-m-d', strtotime((string) $row['kaos_diambil_pada']) ?: time()) : date('Y-m-d');
$autoPrint = isset($_GET['print']) && $_GET['print'] === '1' && $sudahDiambil && ($tandaTerimaError ?? '') === '';
$wilayah = trim((string) ($row['nama_kelurahan'] ?? '') . (trim((string) ($row['nama_kecamatan'] ?? '')) !== '' ? ', Kec. ' . $row['nama_kecamatan'] : ''), ', ');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tanda Terima <?= sanitize($nomorSurat) ?> — <?= sanitize((string) $row['nama_madrasah']) ?></title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: "Times New Roman", Times, serif; color: #111; margin: 0; padding: 24px; background: #f3f4f6; font-size: 14px; line-height: 1.5; }
    .toolbar, .form-card { max-width: 780px; margin: 0 auto 16px; }
    .toolbar { display: flex; gap: 10px; flex-wrap: wrap; font-family: "Segoe UI", Tahoma, sans-serif; }
    .toolbar button, .toolbar a, .form-card button {
      padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; border: none; cursor: pointer;
    }
    .btn-print, .btn-save { background: #15803d; color: #fff; }
    .btn-back { background: #e5e7eb; color: #111; }
    .btn-batal { background: #fff; color: #b91c1c; border: 1px solid #fecaca !important; }
    .form-card { background: #fff; border: 1px solid #d1d5db; border-radius: 12px; padding: 16px 18px; font-family: "Segoe UI", Tahoma, sans-serif; }
    .form-card h2 { margin: 0 0 4px; font-size: 16px; color: #166534; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; }
    .form-grid label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; }
    .form-grid input { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 10px; font-size: 14px; }
    .form-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; align-items: center; }
    .error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 8px; padding: 8px 12px; margin-bottom: 10px; font-size: 13px; }
    .sheet { max-width: 780px; margin: 0 auto; background: #fff; padding: 28px 36px 36px; border: 1px solid #d1d5db; }
    .kop { display: flex; gap: 14px; align-items: center; border-bottom: 3px double #166534; padding-bottom: 12px; }
    .kop img { width: 72px; height: 72px; object-fit: contain; }
    .kop h1 { margin: 0; font-size: 16px; letter-spacing: 0.04em; text-align: center; }
    .kop p { margin: 2px 0 0; font-size: 12px; text-align: center; }
    .judul { text-align: center; margin: 22px 0 6px; }
    .judul h2 { margin: 0; font-size: 16px; text-decoration: underline; letter-spacing: 0.03em; }
    .judul p { margin: 4px 0 0; font-size: 13px; }
    .isi p { margin: 0 0 10px; text-align: justify; }
    table.ident { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
    table.ident td { padding: 2px 0; vertical-align: top; }
    table.ident td.k { width: 150px; }
    table.ident td.s { width: 14px; }
    table.rincian { width: 100%; border-collapse: collapse; margin: 8px 0 14px; font-size: 13px; }
    table.rincian th, table.rincian td { border: 1px solid #111; padding: 5px 6px; text-align: center; }
    table.rincian th { background: #f0fdf4; }
    table.rincian td.left, table.rincian th.left { text-align: left; }
    .sign { margin-top: 28px; display: grid; grid-template-columns: 1fr 1fr; gap: 32px; text-align: center; }
    .sign .space { height: 72px; }
    .sign .nama { font-weight: 700; text-decoration: underline; }
    @media print {
      body { background: #fff; padding: 0; }
      .toolbar, .form-card { display: none !important; }
      .sheet { border: none; max-width: none; padding: 8mm 12mm; }
    }
    @media (max-width: 640px) {
      .form-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <?php if ($sudahDiambil): ?>
      <button type="button" class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
    <?php endif; ?>
    <a class="btn-back" href="<?= sanitize(url('adminjalansehat/?page=detail&id=' . (int) $row['id'])) ?>">← Kembali ke detail</a>
  </div>

  <form class="form-card" method="post" action="<?= sanitize(url('adminjalansehat/?page=tanda-terima&id=' . (int) $row['id'])) ?>">
    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
    <h2>Data penerima kaos</h2>
    <p style="margin:0;font-size:13px;color:#4b5563;">Isi lalu simpan. Surat di bawah langsung terisi dan bisa dicetak. Status madrasah berubah menjadi sudah mengambil kaos.</p>
    <?php if (($tandaTerimaError ?? '') !== ''): ?>
      <div class="error" style="margin-top:10px;"><?= sanitize($tandaTerimaError) ?></div>
    <?php endif; ?>
    <?php if ($kaos < 1): ?>
      <div class="error" style="margin-top:10px;">Madrasah ini tidak memesan kaos. Surat tetap bisa dibuat jika barang lain ikut diserahkan, tetapi rincian kaos kosong.</div>
    <?php endif; ?>
    <div class="form-grid">
      <div>
        <label for="penerima_nama">Nama penerima</label>
        <input id="penerima_nama" name="penerima_nama" required maxlength="150" value="<?= sanitize($namaPenerima) ?>">
      </div>
      <div>
        <label for="penerima_jabatan">Jabatan</label>
        <input id="penerima_jabatan" name="penerima_jabatan" maxlength="120" placeholder="Contoh: Kepala Madrasah" value="<?= sanitize($jabatanPenerima) ?>">
      </div>
      <div>
        <label for="penerima_hp">Nomor HP</label>
        <input id="penerima_hp" name="penerima_hp" maxlength="20" value="<?= sanitize($hpPenerima) ?>">
      </div>
      <div>
        <label for="tanggal_ambil">Tanggal pengambilan</label>
        <input id="tanggal_ambil" name="tanggal_ambil" type="date" required value="<?= sanitize($tanggalInput) ?>">
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn-save" name="action" value="save_tanda_terima"><?= $sudahDiambil ? 'Perbarui & cetak' : 'Simpan & cetak' ?></button>
      <?php if ($sudahDiambil): ?>
        <button type="submit" class="btn-batal" formaction="<?= sanitize(url('adminjalansehat/?page=tanda-terima&id=' . (int) $row['id'])) ?>" formmethod="post"
                name="action" value="batal_tanda_terima"
                formnovalidate
                onclick="return confirm('Batalkan status sudah diambil? Data penerima pada surat akan dikosongkan.');">
          Batalkan pengambilan
        </button>
      <?php endif; ?>
    </div>
  </form>

  <article class="sheet">
    <div class="kop">
      <img src="<?= sanitize(url('image/logo.png')) ?>" alt="Logo">
      <div style="flex:1;">
        <h1>LEMBAGA PENDIDIKAN MA'ARIF NU</h1>
        <h1>PENGURUS CABANG NAHDLATUL ULAMA</h1>
        <h1>KABUPATEN MAGELANG</h1>
        <p>Jalan Sehat dalam rangka Hari Santri Nasional Tahun 2026</p>
      </div>
    </div>

    <div class="judul">
      <h2>SURAT TANDA TERIMA</h2>
      <h2 style="text-decoration:none;font-size:15px;margin-top:2px;">PENGAMBILAN KAOS</h2>
      <p>Nomor: <?= sanitize($nomorSurat) ?></p>
    </div>

    <div class="isi">
      <p>Yang bertanda tangan di bawah ini:</p>
      <table class="ident">
        <tr><td class="k">Nama</td><td class="s">:</td><td><?= sanitize($namaPenerima !== '' ? $namaPenerima : '______________________________') ?></td></tr>
        <tr><td class="k">Jabatan</td><td class="s">:</td><td><?= sanitize($jabatanPenerima !== '' ? $jabatanPenerima : '______________________________') ?></td></tr>
        <tr><td class="k">Nomor HP</td><td class="s">:</td><td><?= sanitize($hpPenerima !== '' ? $hpPenerima : '______________________________') ?></td></tr>
        <tr><td class="k">Mewakili</td><td class="s">:</td><td><strong><?= sanitize((string) $row['nama_madrasah']) ?></strong></td></tr>
        <tr><td class="k">Alamat</td><td class="s">:</td><td><?= sanitize($wilayah !== '' ? $wilayah : alamatDetailJalanSehat($row)) ?></td></tr>
      </table>

      <p>
        menyatakan telah menerima kaos kegiatan <strong>Jalan Sehat Hari Santri Nasional Tahun 2026</strong>
        dari panitia LP Ma'arif NU PCNU Kabupaten Magelang dalam keadaan baik dan sesuai rincian berikut:
      </p>

      <table class="rincian">
        <thead>
          <tr>
            <th class="left">Jenis kaos</th>
            <?php foreach ($sizes as $sz): ?>
              <th><?= sanitize($sz) ?></th>
            <?php endforeach; ?>
            <th>Jumlah</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (jalanSehatUkuranKaosByJenis() as $jenisKey => $jenisInfo): ?>
            <?php $subtotal = $jenisKey === 'pendek' ? (int) $qty['pendek'] : (int) $qty['panjang']; ?>
            <tr>
              <td class="left"><?= sanitize($jenisInfo['label']) ?></td>
              <?php foreach ($jenisInfo['columns'] as $label => $column): ?>
                <?php $n = (int) ($row[$column] ?? 0); ?>
                <td><?= $n > 0 ? (string) $n : '—' ?></td>
              <?php endforeach; ?>
              <td><strong><?= $subtotal ?></strong></td>
            </tr>
          <?php endforeach; ?>
          <tr>
            <td class="left" colspan="<?= count($sizes) + 1 ?>"><strong>Total kaos</strong></td>
            <td><strong><?= $kaos ?> pcs</strong></td>
          </tr>
        </tbody>
      </table>

      <?php if ($kupon > 0): ?>
        <p>Bersamaan dengan kaos tersebut, diserahkan pula kupon door prize sebanyak <strong><?= $kupon ?> lembar</strong>.</p>
      <?php endif; ?>

      <p>Demikian surat tanda terima ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>
    </div>

    <div class="sign">
      <div>
        <p>Yang menyerahkan,</p>
        <p>Panitia Jalan Sehat</p>
        <div class="space"></div>
        <p class="nama">_________________________</p>
        <p>LP Ma'arif NU Kab. Magelang</p>
      </div>
      <div>
        <p>Magelang, <?= sanitize($tanggalSurat) ?></p>
        <p>Yang menerima,</p>
        <div class="space"></div>
        <p class="nama"><?= sanitize($namaPenerima !== '' ? $namaPenerima : '_________________________') ?></p>
        <p><?= sanitize($jabatanPenerima !== '' ? $jabatanPenerima : 'Penerima') ?></p>
      </div>
    </div>
  </article>

  <?php if ($autoPrint): ?>
  <script>window.addEventListener('load', function () { window.print(); });</script>
  <?php endif; ?>
</body>
</html>
