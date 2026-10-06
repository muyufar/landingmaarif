-- Pendaftaran Jalan Sehat Hari Santri Nasional 2026 PCNU Kab. Magelang
-- Tabel juga dibuat otomatis oleh aplikasi saat pertama kali diakses.

CREATE TABLE IF NOT EXISTS `jalan_sehat_pendaftaran` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_madrasah` varchar(200) NOT NULL,
  `alamat` text NOT NULL,
  `kode_kecamatan` varchar(20) DEFAULT NULL,
  `nama_kecamatan` varchar(150) DEFAULT NULL,
  `kode_kelurahan` varchar(20) DEFAULT NULL,
  `nama_kelurahan` varchar(150) DEFAULT NULL,
  `alamat_detail` text DEFAULT NULL,
  `nama_kepala` varchar(150) NOT NULL,
  `nomor_wa` varchar(30) DEFAULT NULL,
  `paket_id` int(10) UNSIGNED DEFAULT NULL,
  `paket_nama` varchar(150) DEFAULT NULL,
  `jumlah_kupon` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `jumlah_kaos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `kaos_s` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `kaos_m` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `kaos_l` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `kaos_xl` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `kaos_xxl` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `kaos_xxxl` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `catatan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_js_paket` (`paket_id`),
  KEY `idx_js_kecamatan` (`nama_kecamatan`),
  KEY `idx_js_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jalan_sehat_paket` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) NOT NULL,
  `jumlah_kupon` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `jumlah_kaos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `keterangan` varchar(255) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `urutan` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jalan_sehat_pengaturan` (
  `kunci` varchar(50) NOT NULL,
  `nilai` text DEFAULT NULL,
  PRIMARY KEY (`kunci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `jalan_sehat_pengaturan` (`kunci`, `nilai`) VALUES
  ('pendaftaran_dibuka', '1'),
  ('izinkan_jumlah_bebas', '1'),
  ('harga_kupon', '0'),
  ('harga_kaos', '0'),
  ('info_surat_edaran', '');
