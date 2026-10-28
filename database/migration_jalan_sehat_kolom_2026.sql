-- Jalankan sekali di phpMyAdmin (Hostinger) jika migrasi otomatis dari PHP gagal.
-- Setelah itu, search / input manual / kaos lengan panjang berjalan normal.

ALTER TABLE `jalan_sehat_pendaftaran`
  ADD COLUMN IF NOT EXISTS `nomor_wa_norm` varchar(20) DEFAULT NULL AFTER `nomor_wa`,
  ADD COLUMN IF NOT EXISTS `nomor_hp_kepala` varchar(30) DEFAULT NULL AFTER `nomor_wa_norm`,
  ADD COLUMN IF NOT EXISTS `nomor_hp_kepala_norm` varchar(20) DEFAULT NULL AFTER `nomor_hp_kepala`,
  ADD COLUMN IF NOT EXISTS `kaos_panjang_s` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kaos_xxxl`,
  ADD COLUMN IF NOT EXISTS `kaos_panjang_m` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kaos_panjang_s`,
  ADD COLUMN IF NOT EXISTS `kaos_panjang_l` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kaos_panjang_m`,
  ADD COLUMN IF NOT EXISTS `kaos_panjang_xl` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kaos_panjang_l`,
  ADD COLUMN IF NOT EXISTS `kaos_panjang_xxl` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kaos_panjang_xl`,
  ADD COLUMN IF NOT EXISTS `kaos_panjang_xxxl` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kaos_panjang_xxl`,
  ADD COLUMN IF NOT EXISTS `kaos_konveksi_selesai` tinyint(1) NOT NULL DEFAULT 0 AFTER `kaos_panjang_xxxl`,
  ADD COLUMN IF NOT EXISTS `pembayaran_lunas` tinyint(1) NOT NULL DEFAULT 0 AFTER `kaos_konveksi_selesai`;

ALTER TABLE `jalan_sehat_pendaftaran`
  ADD COLUMN IF NOT EXISTS `kaos_xxxxl` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kaos_xxxl`,
  ADD COLUMN IF NOT EXISTS `kaos_panjang_xxxxl` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `kaos_panjang_xxxl`;

ALTER TABLE `jalan_sehat_pendaftaran`
  ADD COLUMN IF NOT EXISTS `kaos_diambil_pada` datetime DEFAULT NULL AFTER `pembayaran_lunas`,
  ADD COLUMN IF NOT EXISTS `kaos_diambil_nama` varchar(150) DEFAULT NULL AFTER `kaos_diambil_pada`,
  ADD COLUMN IF NOT EXISTS `kaos_diambil_jabatan` varchar(120) DEFAULT NULL AFTER `kaos_diambil_nama`,
  ADD COLUMN IF NOT EXISTS `kaos_diambil_hp` varchar(30) DEFAULT NULL AFTER `kaos_diambil_jabatan`;
