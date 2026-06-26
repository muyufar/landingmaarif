-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 26, 2026 at 02:05 PM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u700125577_maarifnu`
--

-- --------------------------------------------------------

--
-- Table structure for table `peserta_rakerdinma`
--

CREATE TABLE `peserta_rakerdinma` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `nomor_wa` varchar(20) NOT NULL,
  `tempat_lahir` varchar(100) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jabatan` varchar(150) NOT NULL,
  `asal_lembaga` varchar(255) NOT NULL,
  `alamat_lembaga` text NOT NULL,
  `alat_transportasi` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `peserta_rakerdinma`
--

INSERT INTO `peserta_rakerdinma` (`id`, `nama`, `nip`, `nomor_wa`, `tempat_lahir`, `tanggal_lahir`, `jabatan`, `asal_lembaga`, `alamat_lembaga`, `alat_transportasi`, `created_at`) VALUES
(2, 'YUSUF', '6542145', '08542154785', 'SLEMAN', '1996-05-12', 'SEKERTARIS', 'LP MAARIF NU', 'MUNGKID, MAGELANG', 'MOBIL', '2026-06-24 09:10:54'),
(3, 'Maschuri', '3308142204700001', '085781425817', 'Magelang', '1970-04-22', 'Kepala', 'MI Nahdlotul Athfal Rejosari', 'Sidomulyo, Rejosari Bandongan Magelang', 'Motor', '2026-06-24 12:58:41'),
(4, 'Syarifudin Hidayat, S.Ag', '-', '085643712000', 'Magelang', '1976-07-02', 'Kepala Madrasah', 'MTs Abdussalam', 'Punduhsari Tempurejo Tempuran Magelang', 'Mobil', '2026-06-24 23:10:20'),
(5, 'MUHAMAD KAMDANI, S. Ag.', '-', '083103998923', 'Magelang', '1989-08-06', 'Kepala Madrasah', 'MTs. Ma\'arif Roudlotuddin Salamkanci', 'Kanci 2 Rt/Rw: 002/004 Salamkanci Bandongan Magelang', 'Mator', '2026-06-25 03:52:03'),
(6, 'Drs. Badari', '0', '081215593000', 'Magelang', '1965-04-21', 'Kepala Sekolah', 'SMK MA\'ARIF BOROBUDUR', 'Jl.Sudirman Kelon Borobudur', 'Motor', '2026-06-25 05:53:12'),
(7, 'ANWAR NAWAWI, S.Pd.I', NULL, '085743953515', 'Magelang', '1977-05-14', 'Kepala Madrasah', 'MI Ma\'arif Wuwuharjo 1', 'Mulwo RT 004 RW 004 Wuwuharjo Kajoran Magelang', 'Motor', '2026-06-25 07:34:45'),
(8, 'Bambang Priyanto', NULL, '081227502622', 'Magelang', '1986-03-24', 'GMP', 'MTs Al Islam Wonogiri', 'Dsn Tuanan Desa Wonogiri Kec Kajoran Kab Magelang', 'Motor', '2026-06-25 17:46:42'),
(9, 'FITRIYATI LAILI', '-', '088802474958', 'Magelang', '1983-01-14', 'Kepala Sekolah', 'SMP TRISULA SALAM', 'Citrogaten Lor Salam Magelang', 'Mobil', '2026-06-25 23:33:22'),
(10, 'Wahyu Mubarok', NULL, '085643712033', 'Magelang', '1979-08-05', 'Kepala Madrasah', 'MTs YAKTI Tegalrejo', 'Jl. Pahlawan 102 Tegalrejo Magelang', 'Motor', '2026-06-25 23:51:35'),
(11, 'AHMAD NUR ISLAKH, M.Pd', NULL, '085643739370', 'Kab.Semarang', '1993-01-28', 'Kepala Madrasah', 'MTs Nurul Ali', 'Sempu Ngadirojo Secang Magelang', 'Motor', '2026-06-26 00:53:01'),
(12, 'Arifin Ahmad, S.Ag.', '197107162005011001', '085292146316', 'Magelang', '1971-07-16', 'Kepala Madrasah', 'MA Ma\'arif NU Kaliangkrik', 'Kaliangkrik RT 01 RW 03 Kaliangkrik Magelang', 'Motor', '2026-06-26 01:15:27'),
(13, 'Erni Kholifaturrofiah, S.I.P, S.Pd', '_', '082327940930', 'Magelang', '1981-06-06', 'Kepala Sekolah', 'SMP Trisula Srumbung', 'Jl. Masjid An nur Srumbung Magelang', 'Motor', '2026-06-26 02:38:06'),
(14, 'MARIYAH', '-', '085326344703', 'KEBUMEN', '1969-09-22', 'KEPALA SEKOLAH', 'SMA MA\'ARIF SRUMBUNG', 'JL. MASJID AN NUR SRUMBUNG', 'MOTOR', '2026-06-26 03:16:05'),
(15, 'Eny Rohmawati', '-', '0895630580949', 'Magelang', '2026-06-29', 'Wakasek', 'SMP Terpadu Maarif Gngpg', 'Bintaro Gunungpring', 'Motor', '2026-06-26 03:26:21'),
(16, 'Nurul Fitri, S.Pd', '-', '083851028025', 'Jakarta', '1995-02-28', 'Waka Kurikulum', 'SMP Trisula Ngluwar', 'Dusun Jengkon, Ngluwar, Magelang', 'Motor', '2026-06-26 04:25:54'),
(17, 'FITRI RAHAYU', '0', '081328568456', 'MAGELANG', '1982-07-18', 'KEPALA SEKOLAH', 'SMP MA\'ARIF MUNTILAN', 'SOKORINI MUNTILAN MAGELANG', 'MOTOR', '2026-06-26 04:26:34'),
(18, 'Intihaul Atiq, S. Pd', '-', '089634091895', 'MAGELANG', '1986-08-06', 'Wakil Kepala Madrasah', 'MTs Ma\'arif Al Munir Bandongan', 'Jl. Raden Abdullah No 36 Bandongan', 'Sepeda motor', '2026-06-26 06:10:49'),
(19, 'SRI YUNIATI', '-', '085713112094', 'TEMANGGUNG', '1978-06-21', 'KAMAD', 'MTs ARROSYIDIN SECANG', 'JL.TEMANGGUNG NO.28', 'MOTOR', '2026-06-26 06:31:01'),
(20, 'SRI YUNIATI', '-', '085713112094', 'TEMANGGUNG', '1978-06-21', 'KAMAD', 'MTs ARROSYIDIN SECANG', 'JL.TEMANGGUNG NO.28', 'MOTOR', '2026-06-26 06:33:00'),
(21, 'Askia Widiyanti, S.Pd.', NULL, '083108344075', 'Magelang', '1999-08-29', 'Kepala Sekolah', 'SMP Maarif Grabag', 'Jl. Pagonan Sidogede, Sidogede, kecamatan Grabag, kabupaten Magelang', 'Motor', '2026-06-26 06:39:28'),
(22, 'YUDIKA ROMADHON, S.Pd.I', '-', '081226181093', 'Magelang', '1979-05-07', 'Kepala Madrasah', 'MI Bumiharjo', 'Jl. Sentanu km. 2 Bumiharjo Borobudur', 'Motor', '2026-06-26 10:17:48'),
(23, 'Maisaroh, S.Pd.I.', NULL, '085803066275', 'Magelang', '1982-10-21', 'Kepala Madrasah', 'MI Ma\'arif Sambeng', 'Sambeng 1, Sambeng, Borobudur, Magelang', 'Sepeda Motor', '2026-06-26 10:32:12'),
(24, 'Zainul Arifin,S.Pd.I.,M.Pd.', '197301052005011002', '081392218339', 'Purworejo', '1973-01-05', 'Kepala Madrasah', 'MI Ma\'arif Bulurejo', 'Jl. A. Syarbini Nepak RT 06 RW 01 Bulurejo Mertoyudan Magelang', 'Motor', '2026-06-26 10:53:43'),
(25, 'Nurul Huda', NULL, '08562929046', 'Magelang', '1977-04-02', 'Kepala Sekolah', 'SMP TRISULA MUNTILAN', 'Jl. Ngadiretno Raya Km. 05 Ponggol, Tamanagung, Muntilan', 'Motor', '2026-06-26 11:10:35'),
(26, 'Siti Khoeriyah, S.Ag.', '197205282005012001', '082122214300', 'Magelang', '1972-05-28', 'Kepala Madrasah', 'MI Nurul Huda 2', 'Gedongan Kidul Bondowoso Mertoyudan Magelang', 'Motor', '2026-06-26 11:14:18'),
(27, 'Susanti, S.Pd', '-', '083146852715', 'Magelang', '1984-03-01', 'Kepala Madrasah', 'MI Ma\'arif Srumbung', 'Jalan Masjid An Nur Srumbung', 'Motor', '2026-06-26 11:26:19'),
(28, 'MUH ADIB,S.Pd.I', '-', '083867229926', 'Magelang', '1981-12-14', 'Kepala Madrasah', 'MI Ma\'arif Banyuadem Srumbung', 'Genden RT 003 RW 006 Banyuadem Srumbung', 'Motor', '2026-06-26 11:27:11'),
(29, 'MUDI ASTUTIK, S.Pd', '-', '082138870670', 'Magelang', '1978-11-11', 'Kepada Madrasah', 'MI MA\'ARIF TEGALRANDU', 'Losari, Tegalrandu, Srumbung', 'Sepeda Motor', '2026-06-26 11:28:10'),
(30, 'Nur Salim, S.Pd.I', NULL, '085795589633', 'Magelang', '1977-05-28', 'Kepala Madrasah', 'MI Khoiriyah Kalirejo', 'Karang Wetan  RT 01 RW 02 Kalirejo Salaman  Magelang', 'Motor', '2026-06-26 11:41:49'),
(31, 'LUTFIATUL BANAT', '-', '083867993146', 'Magelang', '1983-10-20', 'Kepala Madrasah', 'MI MA\'ARIF KRADENAN', 'Jelehan, Kradenan, Srumbung', 'Motor', '2026-06-26 11:42:24'),
(32, 'Asmawi', '-', '083199106540', 'Magelang', '1981-01-13', 'Kepala Madrasah', 'Grabag', 'Kalipucang, Banyusari, Grabag, Magelang', 'Mobil', '2026-06-26 11:48:59'),
(33, 'Ngatiyem,S.Pd', '0', '085700126302', 'Magelang', '1981-03-07', 'Kepala Madrasah', 'MI Ma\'arif Bringin', 'Dermo II,Bringin ,Srumbung,Magelang', 'Motor', '2026-06-26 11:50:17'),
(34, 'Ngatiyem,S.Pd', '0', '085700126302', 'Magelang', '1981-03-07', 'Kepala Madrasah', 'MI Ma\'arif Bringin', 'Dermo II,Bringin ,Srumbung,Magelang', 'Motor', '2026-06-26 11:50:55'),
(35, 'Nasriyati, S.Pd', '196804152005012001', '082328177542', 'Magelang', '1968-04-15', 'Kepala Madrasah', 'MI Ma\'arif Ngadiharjo', 'Karangtengah Ngadiharjo Borobudur', 'Motor', '2026-06-26 11:51:10'),
(36, 'Siti Umayah,M.Pd.I', '197504281998032001', '081326121865', 'Magelang', '1975-04-28', 'Kepala MI', 'MI Ma\'arif Pernolo Seloprojo', 'Pernolo Seloprojo Ngablak Magelang', 'BUS', '2026-06-26 11:53:30'),
(37, 'Musta\'in', NULL, '081215727733', 'Semarang', '1985-11-17', 'Kepala Madrasah', 'MI Ma\'arif Sidomulyo', 'Drojogan, Sidomulyo, Salaman', 'Motor', '2026-06-26 11:57:36'),
(38, 'Indra Setiawan', NULL, '08562999071', 'Magelang', '1985-05-19', 'Kepala Madrasah', 'MIS Ma\'arif Nurul Huda Butuh', 'Seketi, Butuh, Sawangan', 'Motor', '2026-06-26 11:57:50'),
(39, 'Yatin Al Fatoni', NULL, '085641982591', 'Magelang', '1970-11-25', 'Kepala Madrasah', 'Mi Maarif Tanjunganom', 'Rejosari 02 rt 02 rw 02 Tanjunganom Salaman Magelang', 'Mobil', '2026-06-26 12:00:06'),
(40, 'Muh Zuhdi, S.Pd.I', '-', '+62881-8789-050', 'Magelang', '1972-03-08', 'Kepala Madrasah', 'MI TUHFATUL MUBTADIIN 1 KALINEGORO', 'Dusun Jetis, Desa Kalinegoro, Kecamatan Mertoyudan', 'Motor', '2026-06-26 12:06:45'),
(41, 'Nasrodin,S.Pd.I', '-', '081390308035', 'Magelang', '1980-08-06', 'Kepala Madrasah', 'MI MA\'ARIF BANDUNGREJO', 'Jl. Nemplak Kanigoro Km.01 Noyogaten Bandungrejo Ngablak', 'Sepeda Motor', '2026-06-26 12:07:18'),
(42, 'Nasrodin,S.Pd.I', '-', '081390308035', 'Magelang', '1980-08-06', 'Kepala Madrasah', 'MI MA\'ARIF BANDUNGREJO', 'Jl. Nemplak Kanigoro Km.01 Noyogaten Bandungrejo Ngablak', 'Sepeda Motor', '2026-06-26 12:07:30'),
(43, 'Ismiyati', '-', '081229249662', 'Magelang', '1971-12-07', 'Kepala Madrasah', 'Mi Maarif', 'Tuksongo Borobudur', 'Motor', '2026-06-26 12:11:19'),
(44, 'Muhammad Zubaidah S.Pd.I', '198001082007101002', '085290870008', 'Magelang', '1980-01-08', 'Kepala Madrasah', 'MI Ma\'arif Tejosari', 'Klimahan. Desa Tejosari Kec.Ngablak', 'Motor', '2026-06-26 12:13:56'),
(45, 'SITI MUFIDAH, S.Pd.I', '-', '085326886514', 'MAGELANG', '1987-02-21', 'KEPALA MADRASAH', 'MI MA\'ARIF PRANTEN SELOPROJO', 'DUSUN PRANTEN DESA SELOPROJO KEC. NGABLAK', 'MOTOR', '2026-06-26 12:14:07'),
(46, 'YUN KHOIRIYAH', '196612311990022001', '081227004355', 'Magelang', '1966-12-31', 'Kepala Madrasah', 'MI Ma\'arif Ngargogondo', 'Kujon Ngargogondo Borobudur', 'Sepeda motor', '2026-06-26 12:15:24'),
(47, 'YUN KHOIRIYAH', '196612311990022001', '081227004355', 'Magelang', '1966-12-31', 'Kepala Madrasah', 'MI Ma\'arif Ngargogondo', 'Kujon Ngargogondo Borobudur', 'Sepeda motor', '2026-06-26 12:15:24'),
(48, 'Tri Sulistyowati, S.Pd.I.,  M.Pd.', '197204092005012002', '081328565655', 'Magelang', '1972-04-09', 'Kepala Madrasah', 'MI Tarbiyatussibyan01 Sidosari', 'Kauman Sidosari Salaman Magelang', 'Motor', '2026-06-26 12:18:58'),
(49, 'Anna Dewi Wijiati', '-', '085743892624', 'Magelang', '1983-03-16', 'Kepala Madrasah', 'MI MA\'ARIF NGADIPURO', 'Ngadipuro 1,Ngadipuro, Dukun', 'Mobil', '2026-06-26 12:27:38'),
(50, 'Atik Zuliastutik', '3308015311710002', '081573641704', 'Magelang', '1971-11-13', 'Guru', 'MI Al Islam Banjarharjo', 'Jengkeling.Banjarharjo.Salaman.', 'Motor', '2026-06-26 12:31:55'),
(51, 'Titik Khoiriyah, SE', '-', '085727737478', 'Magelang', '1983-10-18', 'Kepala Madrasah', 'MI Ma\'arif Mranggen', 'Sumbersari Mranggen Srumbung', 'Motor', '2026-06-26 12:32:59'),
(52, 'FADHOIL,S.Ag,M PD.I', '197311152005011003', '081328841779', 'Magelang', '1973-11-15', 'Kepala Madrasah', 'MI AL ISLAM KRASAK SALAMAN', 'Gejiwan Krasak salaman Magelang', 'Mobil', '2026-06-26 12:36:08'),
(53, 'Sumartijah', NULL, '081953208788', 'Magelang', '1980-03-15', 'Kepala Madrasah', 'MI Ma\'arif Kenalan', 'Nalan II, Kenalan, Borobudur, Magelang', 'Motor', '2026-06-26 12:40:41'),
(54, 'SUYATI, S.Ag.,M.Pd.I.', NULL, '083195797893', 'Kebumen', '1970-11-15', 'Kepala Madrasah', 'MI Ma\'arif Ngargosoka', 'Gedangan Rt. 01 Rw. 05 Ngargosoka Srumbung Magelang Jawa Tengah', 'Sepeda motor', '2026-06-26 12:47:48'),
(55, 'Siti Windaryati, S. Pd. I.', '-', '082225404050', 'Magelang', '1972-06-12', 'Kepala Madrasah', 'MI Ma\'arif Bigaran', 'Dawung RT 003 RW 002   Bicaranya, Borobudur, Magelang', 'Motor', '2026-06-26 12:50:01'),
(56, 'Siti Windaryati, S. Pd. I.', '-', '082225404050', 'Magelang', '1972-06-12', 'Kepala Madrasah', 'MI Ma\'arif Bigaran', 'Dawung RT 003 RW 002   Bicaranya, Borobudur, Magelang', 'Motor', '2026-06-26 12:50:15'),
(57, 'Siti Windaryati, S. Pd. I.', '-', '082225404050', 'Magelang', '1972-06-12', 'Kepala Madrasah', 'MI Ma\'arif Bigaran', 'Dawung RT 003 RW 002   Bicaranya, Borobudur, Magelang', 'Motor', '2026-06-26 12:50:41'),
(58, 'Nurul Fitria, S. Pd. I', '197604052007102005', '085601790927', 'Magelang', '1976-04-05', 'Kepala Madrasah', 'MI Ma\'arif Sumberejo', 'Banaran, Sumberejo, Ngablak, Magelang', 'Motor', '2026-06-26 12:53:28'),
(59, 'MUNTOHA, S.Pd.', '197309272005011005', '082133068677', 'Magelang', '1973-09-27', 'Kepala Madrasah', 'MI Ma\'arif Karangrejo', 'Kurahan RT. 001/RT. 001, Karangrejo, Borobudur, 56553', 'Sepeda Motor', '2026-06-26 13:05:19'),
(60, 'Choirun Nisak', '0', '082338311363', 'Magelang', '1981-05-07', 'Kepala Madrasah', 'MI.Ma\'arif Wanurejo', 'Bejen Wanurejo', 'Motor', '2026-06-26 13:10:02'),
(61, 'ANIK SUPRIATI', NULL, '082226111935', 'Magelang', '1981-07-03', 'Kepala Madrasah', 'MIMA NGABLAK 1', 'Kedawung,Ngablak,Srumbung', 'Motor', '2026-06-26 13:11:35'),
(62, 'Ruayda Nazila', '-', '083897815235', 'Magelang', '1978-08-08', 'Kepala Madrasah', 'MI Ma\'arif Giritengah', 'Kalitengah, Giritengah, Borobudur', 'Motor', '2026-06-26 13:20:06'),
(63, 'Suhartinah,S.Pd', '-', '085743651076', 'Magelang', '1983-12-26', 'Kepala Madrasah', 'MI Tarbiyatul Muslim', 'Dsn Dalangan, Ds Candimulyo, Kec Candimulyo Kab Magelang', 'sepeda motor', '2026-06-26 13:23:32'),
(64, 'Siti Ilmi Shohiyah, S.Pd.I', '197206132005012009', '082134722225', 'Magelang', '1972-06-13', 'Kepala Madrasah', 'MI Al-Islah Kalegen', 'Karanglo Rt.003/ RW.001 Karanglo Kalegen Bandongan Magelang', 'Motor', '2026-06-26 13:32:08'),
(65, 'SITI MAIMUNAH', '197304152001122001', '085870176482', 'MAGELANG', '1973-04-15', 'KEPALA MADRASAH', 'MI RAUDLATUDDIN SALAMKANCI', 'KAMCI I SALAMKANCI BANDONGAN MAGELANG', 'sepeda motor', '2026-06-26 13:37:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `peserta_rakerdinma`
--
ALTER TABLE `peserta_rakerdinma`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_nama` (`nama`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `peserta_rakerdinma`
--
ALTER TABLE `peserta_rakerdinma`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
