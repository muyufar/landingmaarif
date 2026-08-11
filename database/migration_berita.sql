-- Import di phpMyAdmin (database u700125577_maarifnu)

CREATE TABLE IF NOT EXISTS `berita` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `kode_singkat` varchar(12) DEFAULT NULL,
  `ringkasan` text DEFAULT NULL,
  `konten` mediumtext NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_berita_slug` (`slug`),
  UNIQUE KEY `uq_berita_kode_singkat` (`kode_singkat`),
  KEY `idx_berita_status` (`status`),
  KEY `idx_berita_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `berita_gambar` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `berita_id` int(10) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `urutan` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_berita_gambar_berita` (`berita_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jika tabel berita sudah ada tanpa kode_singkat, jalankan:
-- ALTER TABLE berita ADD COLUMN `kode_singkat` varchar(12) DEFAULT NULL AFTER `slug`;
-- ALTER TABLE berita ADD UNIQUE KEY `uq_berita_kode_singkat` (`kode_singkat`);
