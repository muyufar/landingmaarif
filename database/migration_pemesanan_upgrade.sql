-- Upgrade struktur pemesanan (pemesanan_buku ATAU pemesanan)
SET @db = DATABASE();

SET @table = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan') > 0,
  'pemesanan',
  IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan_buku') > 0,
    'pemesanan_buku',
    NULL
  )
);

-- jenis_layanan
SET @sql = IF(@table IS NOT NULL AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = 'jenis_layanan') = 0,
  CONCAT('ALTER TABLE `', @table, '` ADD COLUMN `jenis_layanan` varchar(30) NOT NULL DEFAULT ''mopdik'' AFTER `id`'),
  'SELECT ''[OK] jenis_layanan'' AS status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- jumlah (jika belum ada)
SET @sql = IF(@table IS NOT NULL AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = 'jumlah') = 0,
  CONCAT('ALTER TABLE `', @table, '` ADD COLUMN `jumlah` int(10) unsigned DEFAULT NULL'),
  'SELECT ''[OK] jumlah'' AS status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- jumlah boleh NULL (penting untuk pemesanan batik)
SET @sql = IF(@table IS NOT NULL AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = 'jumlah') > 0,
  CONCAT('ALTER TABLE `', @table, '` MODIFY COLUMN `jumlah` int(10) unsigned DEFAULT NULL'),
  'SELECT ''[OK] skip modify jumlah'' AS status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- jenis_batik
SET @sql = IF(@table IS NOT NULL AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = 'jenis_batik') = 0,
  CONCAT('ALTER TABLE `', @table, '` ADD COLUMN `jenis_batik` varchar(150) DEFAULT NULL AFTER `jumlah`'),
  'SELECT ''[OK] jenis_batik'' AS status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- kolom batik
SET @sql = IF(@table IS NOT NULL AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = 'satuan_jenis_1') = 0,
  CONCAT('ALTER TABLE `', @table, '`
    ADD COLUMN `satuan_jenis_1` varchar(20) DEFAULT NULL AFTER `jenis_batik`,
    ADD COLUMN `satuan_jumlah_1` int(10) unsigned DEFAULT NULL AFTER `satuan_jenis_1`,
    ADD COLUMN `satuan_jenis_2` varchar(20) DEFAULT NULL AFTER `satuan_jumlah_1`,
    ADD COLUMN `satuan_jumlah_2` int(10) unsigned DEFAULT NULL AFTER `satuan_jenis_2`,
    ADD COLUMN `ukuran_s` int(10) unsigned NOT NULL DEFAULT 0 AFTER `satuan_jumlah_2`,
    ADD COLUMN `ukuran_m` int(10) unsigned NOT NULL DEFAULT 0 AFTER `ukuran_s`,
    ADD COLUMN `ukuran_l` int(10) unsigned NOT NULL DEFAULT 0 AFTER `ukuran_m`,
    ADD COLUMN `ukuran_xl` int(10) unsigned NOT NULL DEFAULT 0 AFTER `ukuran_l`,
    ADD COLUMN `ukuran_xxl` int(10) unsigned NOT NULL DEFAULT 0 AFTER `ukuran_xl`'),
  'SELECT ''[OK] kolom batik'' AS status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- perlebar jenis_batik
SET @sql = IF(@table IS NOT NULL AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = 'jenis_batik') > 0,
  CONCAT('ALTER TABLE `', @table, '` MODIFY COLUMN `jenis_batik` varchar(150) DEFAULT NULL'),
  'SELECT ''[OK] skip modify jenis_batik'' AS status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- kolom kelas Buku Ke-NU-an
SET @sql = IF(@table IS NOT NULL AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @table AND COLUMN_NAME = 'kelas_iv_mi') = 0,
  CONCAT('ALTER TABLE `', @table, '`
    ADD COLUMN `kelas_iv_mi` int(10) unsigned NOT NULL DEFAULT 0,
    ADD COLUMN `kelas_v_mi` int(10) unsigned NOT NULL DEFAULT 0,
    ADD COLUMN `kelas_vi_mi` int(10) unsigned NOT NULL DEFAULT 0,
    ADD COLUMN `kelas_vii_mts` int(10) unsigned NOT NULL DEFAULT 0,
    ADD COLUMN `kelas_viii_mts` int(10) unsigned NOT NULL DEFAULT 0,
    ADD COLUMN `kelas_ix_mts` int(10) unsigned NOT NULL DEFAULT 0,
    ADD COLUMN `kelas_x_ma` int(10) unsigned NOT NULL DEFAULT 0,
    ADD COLUMN `kelas_xi_ma` int(10) unsigned NOT NULL DEFAULT 0,
    ADD COLUMN `kelas_xii_ma` int(10) unsigned NOT NULL DEFAULT 0'),
  'SELECT ''[OK] kolom kelas kenuan'' AS status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- rename jika masih pemesanan_buku
SET @has_new = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan');
SET @has_old = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan_buku');
SET @sql = IF(@has_new = 0 AND @has_old > 0,
  'RENAME TABLE `pemesanan_buku` TO `pemesanan`',
  'SELECT ''[OK] rename tidak diperlukan'' AS status');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Upgrade struktur pemesanan selesai.' AS status;
