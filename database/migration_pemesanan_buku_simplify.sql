-- Migrasi: gabungkan MOPDIK + Buku Saku menjadi satu kolom jumlah
-- AMAN dijalankan ulang — melewati langkah yang sudah selesai

SET @db = DATABASE();

-- 1. Tambah kolom jumlah jika belum ada
SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `pemesanan_buku` ADD COLUMN `jumlah` int(10) unsigned NOT NULL DEFAULT 1 AFTER `jenjang`',
    'SELECT ''[OK] Kolom jumlah sudah ada'' AS status')
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan_buku' AND COLUMN_NAME = 'jumlah'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Salin data dari kolom lama (jika struktur lama masih ada)
SET @sql = (
  SELECT IF(COUNT(*) > 0,
    'UPDATE `pemesanan_buku` SET `jumlah` = GREATEST(
      IF(`pesan_mopdik` = 1, `jumlah_mopdik`, 0),
      IF(`pesan_buku_saku` = 1, `jumlah_buku_saku`, 0),
      IF(`pesan_mopdik` = 1 OR `pesan_buku_saku` = 1, 1, 0)
    )',
    'SELECT ''[OK] Kolom lama tidak ada, migrasi data dilewati'' AS status')
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan_buku' AND COLUMN_NAME = 'pesan_mopdik'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Hapus kolom lama satu per satu (jika masih ada)
SET @sql = (
  SELECT IF(COUNT(*) > 0,
    'ALTER TABLE `pemesanan_buku` DROP COLUMN `pesan_mopdik`',
    'SELECT ''[OK] pesan_mopdik sudah dihapus'' AS status')
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan_buku' AND COLUMN_NAME = 'pesan_mopdik'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) > 0,
    'ALTER TABLE `pemesanan_buku` DROP COLUMN `jumlah_mopdik`',
    'SELECT ''[OK] jumlah_mopdik sudah dihapus'' AS status')
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan_buku' AND COLUMN_NAME = 'jumlah_mopdik'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) > 0,
    'ALTER TABLE `pemesanan_buku` DROP COLUMN `pesan_buku_saku`',
    'SELECT ''[OK] pesan_buku_saku sudah dihapus'' AS status')
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan_buku' AND COLUMN_NAME = 'pesan_buku_saku'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(COUNT(*) > 0,
    'ALTER TABLE `pemesanan_buku` DROP COLUMN `jumlah_buku_saku`',
    'SELECT ''[OK] jumlah_buku_saku sudah dihapus'' AS status')
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pemesanan_buku' AND COLUMN_NAME = 'jumlah_buku_saku'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migrasi pemesanan_buku selesai.' AS status;
