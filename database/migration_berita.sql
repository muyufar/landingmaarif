-- Import di phpMyAdmin (database u700125577_maarifnu)

CREATE TABLE IF NOT EXISTS `berita` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `ringkasan` text DEFAULT NULL,
  `konten` mediumtext NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_berita_slug` (`slug`),
  KEY `idx_berita_status` (`status`),
  KEY `idx_berita_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
