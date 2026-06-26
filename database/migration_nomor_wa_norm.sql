-- Kolom nomor WA normalisasi (indeks unik dibuat setelah dedupe via migrate_nomor_wa_norm.php)
ALTER TABLE `peserta_rakerdinma`
  ADD COLUMN `nomor_wa_norm` varchar(20) DEFAULT NULL AFTER `nomor_wa`;
