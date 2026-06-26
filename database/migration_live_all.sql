-- Jalankan sekali di phpMyAdmin (database u700125577_maarifnu) sebelum import data migrated
-- Abaikan error "Duplicate column" jika kolom sudah ada

-- 1. Alamat wilayah
ALTER TABLE `peserta_rakerdinma`
  ADD COLUMN `kode_provinsi` varchar(2) DEFAULT NULL AFTER `alamat_lembaga`,
  ADD COLUMN `nama_provinsi` varchar(100) DEFAULT NULL AFTER `kode_provinsi`,
  ADD COLUMN `kode_kabupaten` varchar(8) DEFAULT NULL AFTER `nama_provinsi`,
  ADD COLUMN `nama_kabupaten` varchar(150) DEFAULT NULL AFTER `kode_kabupaten`,
  ADD COLUMN `kode_kecamatan` varchar(12) DEFAULT NULL AFTER `nama_kabupaten`,
  ADD COLUMN `nama_kecamatan` varchar(150) DEFAULT NULL AFTER `kode_kecamatan`,
  ADD COLUMN `kode_kelurahan` varchar(16) DEFAULT NULL AFTER `nama_kecamatan`,
  ADD COLUMN `nama_kelurahan` varchar(150) DEFAULT NULL AFTER `kode_kelurahan`,
  ADD COLUMN `alamat_detail` varchar(500) DEFAULT NULL AFTER `nama_kelurahan`;

-- 2. Jenis lembaga (jalankan terpisah jika ALTER di atas sudah pernah dijalankan sebagian)
ALTER TABLE `peserta_rakerdinma`
  ADD COLUMN `jenis_lembaga` varchar(20) DEFAULT NULL AFTER `asal_lembaga`;

ALTER TABLE `peserta_rakerdinma`
  ADD KEY `idx_jenis_lembaga` (`jenis_lembaga`);

-- 3. Nomor WA normalisasi
ALTER TABLE `peserta_rakerdinma`
  ADD COLUMN `nomor_wa_norm` varchar(20) DEFAULT NULL AFTER `nomor_wa`;

-- 4. Indeks unik (setelah import peserta_rakerdinma_migrated.sql)
-- CREATE UNIQUE INDEX `idx_nomor_wa_norm` ON `peserta_rakerdinma` (`nomor_wa_norm`);
