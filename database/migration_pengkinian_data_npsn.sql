-- Tambah kolom NPSN pada tabel pengkinian_data_satuan
ALTER TABLE `pengkinian_data_satuan`
  ADD COLUMN `npsn` varchar(20) NOT NULL DEFAULT '' AFTER `id`;

UPDATE `pengkinian_data_satuan` SET `npsn` = CONCAT('TMP-', `id`) WHERE `npsn` = '' OR `npsn` IS NULL;

ALTER TABLE `pengkinian_data_satuan`
  ADD UNIQUE KEY `uniq_npsn` (`npsn`);
