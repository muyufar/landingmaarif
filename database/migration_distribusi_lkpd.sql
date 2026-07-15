-- Tracking distribusi buku LKPD MI Ma'arif Magelang
CREATE TABLE IF NOT EXISTS `distribusi_petugas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `role` enum('super_admin','petugas') NOT NULL DEFAULT 'petugas',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `distribusi_lkpd_satuan` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `npsn` varchar(20) NOT NULL,
  `nama_lembaga` varchar(200) NOT NULL,
  `alamat` text NOT NULL,
  `kebutuhan_kelas_1` int(10) unsigned NOT NULL DEFAULT 0,
  `kebutuhan_kelas_2` int(10) unsigned NOT NULL DEFAULT 0,
  `kebutuhan_kelas_3` int(10) unsigned NOT NULL DEFAULT 0,
  `kebutuhan_kelas_4` int(10) unsigned NOT NULL DEFAULT 0,
  `kebutuhan_kelas_5` int(10) unsigned NOT NULL DEFAULT 0,
  `kebutuhan_kelas_6` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('packing','delivery','receive','done') NOT NULL DEFAULT 'packing',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_npsn` (`npsn`),
  KEY `idx_status` (`status`),
  KEY `idx_nama` (`nama_lembaga`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `distribusi_lkpd_pengiriman` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `satuan_id` int(10) unsigned NOT NULL,
  `petugas_id` int(10) unsigned NOT NULL,
  `status` enum('delivery','received_partial','received_complete') NOT NULL DEFAULT 'delivery',
  `terima_kelas_1` int(10) unsigned NOT NULL DEFAULT 0,
  `terima_kelas_2` int(10) unsigned NOT NULL DEFAULT 0,
  `terima_kelas_3` int(10) unsigned NOT NULL DEFAULT 0,
  `terima_kelas_4` int(10) unsigned NOT NULL DEFAULT 0,
  `terima_kelas_5` int(10) unsigned NOT NULL DEFAULT 0,
  `terima_kelas_6` int(10) unsigned NOT NULL DEFAULT 0,
  `file_surat_jalan_distributor` varchar(255) DEFAULT NULL,
  `file_surat_jalan_sekolah` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `wa_sent_at` datetime DEFAULT NULL,
  `wa_sent_to` varchar(100) DEFAULT NULL,
  `dispatched_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `received_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_satuan` (`satuan_id`),
  KEY `idx_petugas` (`petugas_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
