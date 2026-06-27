-- Perlebar kolom jenis_lembaga untuk opsi "PENGURUS LP MAARIF MWC"
ALTER TABLE `peserta_rakerdinma`
  MODIFY COLUMN `jenis_lembaga` varchar(50) DEFAULT NULL;
