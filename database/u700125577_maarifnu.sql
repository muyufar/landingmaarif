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
  `nomor_wa_norm` varchar(20) DEFAULT NULL,
  `tempat_lahir` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jabatan` varchar(150) NOT NULL,
  `asal_lembaga` varchar(255) NOT NULL,
  `jenis_lembaga` varchar(20) DEFAULT NULL,
  `alamat_lembaga` text NOT NULL,
  `kode_provinsi` varchar(2) DEFAULT NULL,
  `nama_provinsi` varchar(100) DEFAULT NULL,
  `kode_kabupaten` varchar(8) DEFAULT NULL,
  `nama_kabupaten` varchar(150) DEFAULT NULL,
  `kode_kecamatan` varchar(12) DEFAULT NULL,
  `nama_kecamatan` varchar(150) DEFAULT NULL,
  `kode_kelurahan` varchar(16) DEFAULT NULL,
  `nama_kelurahan` varchar(150) DEFAULT NULL,
  `alamat_detail` varchar(500) DEFAULT NULL,
  `alat_transportasi` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_nama` (`nama`),
  KEY `idx_jenis_lembaga` (`jenis_lembaga`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
