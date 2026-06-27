-- Perlebar kolom jenis_batik untuk multi-pilihan (Siswa + Guru)
ALTER TABLE `pemesanan`
  MODIFY COLUMN `jenis_batik` varchar(150) DEFAULT NULL;
