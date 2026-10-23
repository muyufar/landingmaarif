<?php

declare(strict_types=1);

/** @var array $row @var array $settings */
$kupon = (int) $row['jumlah_kupon'];
$kaos = (int) $row['jumlah_kaos'];
$rincianBiaya = jalanSehatAdaHarga($settings) ? jalanSehatRincianBiayaFromRow($row, $settings) : null;
$sudahLunas = !empty($row['pembayaran_lunas']);
$notaNo = 'JS-' . str_pad((string) (int) $row['id'], 5, '0', STR_PAD_LEFT);
$tanggalNota = date('d/m/Y', strtotime((string) ($row['created_at'] ?? 'now')));
$autoPrint = isset($_GET['print']) && $_GET['print'] === '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nota <?= sanitize($notaNo) ?> — <?= sanitize((string) $row['nama_madrasah']) ?></title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: "Segoe UI", Tahoma, sans-serif; color: #111; margin: 0; padding: 24px; background: #f3f4f6; font-size: 13px; line-height: 1.45; }
    .sheet { max-width: 720px; margin: 0 auto; background: #fff; padding: 28px 32px; border: 1px solid #d1d5db; }
    .toolbar { max-width: 720px; margin: 0 auto 16px; display: flex; gap: 10px; flex-wrap: wrap; }
    .toolbar button, .toolbar a {
      padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; border: none; cursor: pointer;
    }
    .btn-print { background: #15803d; color: #fff; }
    .btn-back { background: #e5e7eb; color: #111; }
    header { display: flex; gap: 16px; align-items: flex-start; border-bottom: 2px solid #166534; padding-bottom: 16px; margin-bottom: 20px; }
    header img { width: 64px; height: 64px; object-fit: contain; }
    h1 { margin: 0; font-size: 18px; color: #166534; }
    .sub { color: #4b5563; font-size: 12px; margin-top: 4px; }
    .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; margin-bottom: 20px; }
    .meta dt { color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; }
    .meta dd { margin: 2px 0 0; font-weight: 600; }
    table.items { width: 100%; border-collapse: collapse; margin: 16px 0; }
    table.items th, table.items td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
    table.items th { background: #f0fdf4; color: #166534; font-size: 11px; text-transform: uppercase; }
    table.items td.num, table.items th.num { text-align: right; white-space: nowrap; }
    .total-row td { font-weight: 700; background: #f9fafb; font-size: 15px; }
    .status { display: inline-block; padding: 4px 12px; border-radius: 999px; font-weight: 700; font-size: 12px; }
    .status-lunas { background: #d1fae5; color: #065f46; }
    .status-belum { background: #fef3c7; color: #92400e; }
    .foot { margin-top: 24px; padding-top: 16px; border-top: 1px dashed #9ca3af; font-size: 11px; color: #4b5563; }
    .sign { margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; text-align: center; font-size: 12px; }
    .sign .line { margin-top: 56px; border-top: 1px solid #111; padding-top: 6px; }
    @media print {
      body { background: #fff; padding: 0; }
      .toolbar { display: none !important; }
      .sheet { border: none; max-width: none; padding: 12mm 15mm; }
    }
  </style>
</head>
<body>
  <div class="toolbar no-print">
    <button type="button" class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
    <a class="btn-back" href="<?= sanitize(url('adminjalansehat/?page=detail&id=' . (int) $row['id'])) ?>">← Kembali</a>
  </div>

  <div class="sheet">
    <header>
      <img src="<?= sanitize(url('image/logo.png')) ?>" alt="Logo">
      <div>
        <h1>NOTA PEMESANAN JALAN SEHAT HSN 2026</h1>
        <p class="sub">Lembaga Pendidikan Ma'arif NU — PCNU Kabupaten Magelang</p>
        <p class="sub">No. <?= sanitize($notaNo) ?> · Tanggal pendaftaran: <?= sanitize($tanggalNota) ?></p>
      </div>
    </header>

    <dl class="meta">
      <div>
        <dt>Madrasah / Sekolah</dt>
        <dd><?= sanitize((string) $row['nama_madrasah']) ?></dd>
      </div>
      <div>
        <dt>Kepala madrasah</dt>
        <dd><?= sanitize((string) $row['nama_kepala']) ?></dd>
      </div>
      <div>
        <dt>Kecamatan / Desa</dt>
        <dd><?= sanitize(trim((string) ($row['nama_kecamatan'] ?? '') . ' · ' . (string) ($row['nama_kelurahan'] ?? ''), ' ·')) ?: '—' ?></dd>
      </div>
      <div>
        <dt>WA / HP</dt>
        <dd><?= sanitize((string) ($row['nomor_wa'] ?? '')) ?: '—' ?><?php if (!empty($row['nomor_hp_kepala'])): ?> / <?= sanitize((string) $row['nomor_hp_kepala']) ?><?php endif; ?></dd>
      </div>
      <div class="col-span-2" style="grid-column: 1 / -1;">
        <dt>Alamat</dt>
        <dd><?= sanitize(alamatDetailJalanSehat($row)) ?></dd>
      </div>
      <div>
        <dt>Paket / cara pesan</dt>
        <dd><?= sanitize(!empty($row['paket_nama']) ? (string) $row['paket_nama'] : 'Jumlah sendiri') ?></dd>
      </div>
      <div>
        <dt>Status pembayaran</dt>
        <dd>
          <span class="status <?= $sudahLunas ? 'status-lunas' : 'status-belum' ?>">
            <?= $sudahLunas ? 'LUNAS' : 'BELUM LUNAS' ?>
          </span>
        </dd>
      </div>
    </dl>

    <p><strong>Ringkasan pesanan:</strong> <?= (int) $kupon ?> kupon door prize · <?= (int) $kaos ?> kaos
      <?php $rincianUk = formatRincianUkuranKaos($row); ?>
      <?php if ($rincianUk !== ''): ?> (<?= sanitize($rincianUk) ?>)<?php endif; ?>
    </p>

    <?php if ($rincianBiaya !== null && $rincianBiaya['total'] > 0): ?>
      <table class="items">
        <thead>
          <tr>
            <th>Uraian</th>
            <th class="num">Qty</th>
            <th class="num">Harga</th>
            <th class="num">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rincianBiaya['subtotal_kupon'] > 0): ?>
            <tr>
              <td>Kupon door prize / pendaftaran (di luar bonus kaos)</td>
              <td class="num"><?= (int) $rincianBiaya['qty_kupon_bayar'] ?></td>
              <td class="num"><?= sanitize(formatRupiahJalanSehat($rincianBiaya['harga_kupon'])) ?></td>
              <td class="num"><?= sanitize(formatRupiahJalanSehat($rincianBiaya['subtotal_kupon'])) ?></td>
            </tr>
          <?php endif; ?>
          <?php if ($rincianBiaya['subtotal_kaos_pendek'] > 0): ?>
            <tr>
              <td>Kaos lengan pendek (termasuk daftar + bonus kupon)</td>
              <td class="num"><?= (int) $rincianBiaya['qty_pendek'] ?></td>
              <td class="num"><?= sanitize(formatRupiahJalanSehat($rincianBiaya['harga_kaos_pendek'])) ?></td>
              <td class="num"><?= sanitize(formatRupiahJalanSehat($rincianBiaya['subtotal_kaos_pendek'])) ?></td>
            </tr>
          <?php endif; ?>
          <?php if ($rincianBiaya['subtotal_kaos_panjang'] > 0): ?>
            <tr>
              <td>Kaos lengan panjang (termasuk daftar + bonus kupon)</td>
              <td class="num"><?= (int) $rincianBiaya['qty_panjang'] ?></td>
              <td class="num"><?= sanitize(formatRupiahJalanSehat($rincianBiaya['harga_kaos_panjang'])) ?></td>
              <td class="num"><?= sanitize(formatRupiahJalanSehat($rincianBiaya['subtotal_kaos_panjang'])) ?></td>
            </tr>
          <?php endif; ?>
          <?php if ($rincianBiaya['bonus_kupon_kaos'] > 0): ?>
            <tr>
              <td colspan="4" style="font-size: 11px; color: #6b7280;">
                Bonus <?= (int) $rincianBiaya['bonus_kupon_kaos'] ?> kupon door prize dari pemesanan kaos — sudah termasuk harga kaos (tidak ditagih terpisah).
              </td>
            </tr>
          <?php endif; ?>
          <tr class="total-row">
            <td colspan="3" class="num">TOTAL TAGIHAN</td>
            <td class="num"><?= sanitize(formatRupiahJalanSehat($rincianBiaya['total'])) ?></td>
          </tr>
        </tbody>
      </table>
    <?php else: ?>
      <p><em>Tagihan belum dihitung (harga belum diatur di pengaturan).</em></p>
    <?php endif; ?>

    <div class="foot">
      <p><strong>Pembayaran</strong> (sesuai surat edaran): transfer BNU Syariah / Bank Mandiri / BRI a.n. PC LP Ma'arif NU / SITI MARZUQOH,
        atau tunai saat pengambilan kupon &amp; kaos di kantor LP Ma'arif NU PCNU Kab. Magelang.</p>
      <p>Dokumen ini dicetak dari sistem pendaftaran Jalan Sehat — <?= sanitize(date('d/m/Y H:i')) ?> WIB.</p>
    </div>

    <div class="sign">
      <div>
        <p>Penerima / Panitia</p>
        <div class="line">( _________________________ )</div>
      </div>
      <div>
        <p>Pemesan</p>
        <div class="line"><?= sanitize((string) $row['nama_kepala']) ?></div>
      </div>
    </div>
  </div>

  <?php if ($autoPrint): ?>
  <script>window.addEventListener('load', function () { window.print(); });</script>
  <?php endif; ?>
</body>
</html>
