-- Field deteksi masa aktif SK Kepala Sekolah/Madrasah
ALTER TABLE `pengkinian_data_satuan`
  ADD COLUMN `tempat_lahir` varchar(100) DEFAULT NULL AFTER `nama_kepala_sekolah`,
  ADD COLUMN `tanggal_lahir` date DEFAULT NULL AFTER `tempat_lahir`,
  ADD COLUMN `niy_nip` varchar(30) DEFAULT NULL AFTER `tanggal_lahir`,
  ADD COLUMN `jabatan` varchar(50) DEFAULT NULL AFTER `niy_nip`,
  ADD COLUMN `jenjang` varchar(30) DEFAULT NULL AFTER `jabatan`,
  ADD COLUMN `tgl_tmt_sk` date DEFAULT NULL AFTER `alamat_lengkap`,
  ADD COLUMN `tgl_akhir_tmt_sk` date DEFAULT NULL AFTER `tgl_tmt_sk`,
  ADD COLUMN `file_sk_kepala` varchar(255) DEFAULT NULL AFTER `tgl_akhir_tmt_sk`,
  ADD COLUMN `status_sk_kepala` varchar(10) DEFAULT NULL AFTER `file_sk_kepala`;
