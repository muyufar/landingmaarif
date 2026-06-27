-- Struktur unified pemesanan (MOPDIK, Batik, Buku Ke-NU-an, Buku Tulis Aswaja)
-- Aman dijalankan ulang (idempotent)

SET @db = DATABASE();

-- Buat tabel pemesanan jika belum ada
CREATE TABLE IF NOT EXISTS `pemesanan` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `jenis_layanan` varchar(30) NOT NULL DEFAULT 'mopdik',
  `nama_madrasah` varchar(200) NOT NULL,
  `nama_kepala` varchar(150) NOT NULL,
  `nomor_wa` varchar(30) NOT NULL,
  `nomor_wa_norm` varchar(20) DEFAULT NULL,
  `jenjang` varchar(30) DEFAULT NULL,
  `jumlah` int(10) unsigned DEFAULT NULL,
  `jenis_batik` varchar(150) DEFAULT NULL,
  `satuan_jenis_1` varchar(20) DEFAULT NULL,
  `satuan_jumlah_1` int(10) unsigned DEFAULT NULL,
  `satuan_jenis_2` varchar(20) DEFAULT NULL,
  `satuan_jumlah_2` int(10) unsigned DEFAULT NULL,
  `ukuran_s` int(10) unsigned NOT NULL DEFAULT 0,
  `ukuran_m` int(10) unsigned NOT NULL DEFAULT 0,
  `ukuran_l` int(10) unsigned NOT NULL DEFAULT 0,
  `ukuran_xl` int(10) unsigned NOT NULL DEFAULT 0,
  `ukuran_xxl` int(10) unsigned NOT NULL DEFAULT 0,
  `catatan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jenis_layanan` (`jenis_layanan`),
  KEY `idx_jenjang` (`jenjang`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_nomor_wa_norm` (`nomor_wa_norm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrasi data dari pemesanan_buku (jika masih ada)
SET @has_old = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan_buku'
);

SET @sql = IF(@has_old > 0,
  'INSERT INTO `pemesanan` (jenis_layanan, nama_madrasah, nama_kepala, nomor_wa, nomor_wa_norm, jenjang, jumlah, catatan, created_at)
   SELECT ''mopdik'', nama_madrasah, nama_kepala, nomor_wa, nomor_wa_norm, jenjang, jumlah, catatan, created_at
   FROM `pemesanan_buku`
   WHERE NOT EXISTS (SELECT 1 FROM `pemesanan` p WHERE p.nama_madrasah = pemesanan_buku.nama_madrasah AND p.created_at = pemesanan_buku.created_at)',
  'SELECT ''[OK] Tabel pemesanan_buku tidak ada'' AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@has_old > 0,
  'DROP TABLE `pemesanan_buku`',
  'SELECT ''[OK] Tidak perlu drop pemesanan_buku'' AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migrasi pemesanan unified selesai.' AS status;
