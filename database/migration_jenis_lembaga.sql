-- Migration: kolom jenis_lembaga
ALTER TABLE `peserta_rakerdinma`
  ADD COLUMN `jenis_lembaga` varchar(20) DEFAULT NULL AFTER `asal_lembaga`,
  ADD KEY `idx_jenis_lembaga` (`jenis_lembaga`);
