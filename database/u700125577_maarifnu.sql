-- Import file untuk database: u700125577_maarifnu
-- Cara import: phpMyAdmin > pilih database u700125577_maarifnu > tab Import > pilih file ini

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `peserta_rakerdinma` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `nomor_wa` varchar(20) NOT NULL,
  `tempat_lahir` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jabatan` varchar(150) NOT NULL,
  `asal_lembaga` varchar(255) NOT NULL,
  `alamat_lembaga` text NOT NULL,
  `alat_transportasi` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
