-- Migration: kolom alamat wilayah untuk peserta_rakerdinma
-- Jalankan setelah import u700125577_maarifnu.sql jika tabel sudah ada

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
