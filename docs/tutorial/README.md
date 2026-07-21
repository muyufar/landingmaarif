# Tutorial Distribusi LKPD MI Ma'arif NU

Panduan lengkap penggunaan sistem tracking distribusi buku LKPD (Admin + Petugas), dengan **screenshot live** dari aplikasi.

## File utama

| File | Keterangan |
|------|------------|
| `TUTORIAL-DISTRIBUSI-LKPD-MI-MAARIF.pdf` | **Dokumen tutorial siap pakai** |
| `screenshots/` | Tangkapan layar live (13 halaman) |
| `generate.php` | Script generator otomatis |
| `capture-screenshots.mjs` | Playwright: login & screenshot |
| `build-pdf.py` | Susun teks + gambar jadi PDF |

## Regenerasi tutorial (jika UI berubah)

Pastikan Laragon/Apache jalan dan aplikasi bisa diakses di:

`http://localhost/maarifnu/`

Jalankan dari root project:

```bash
php docs/tutorial/generate.php
```

### Prasyarat

- PHP CLI + database terkoneksi
- Node.js + npm (`C:\Program Files\nodejs\`)
- Python 3 + fpdf2 (terinstall otomatis saat build)
- Playwright Chromium (terinstall otomatis saat generate)

### Environment (opsional)

```bash
set TUTORIAL_BASE_URL=http://localhost/maarifnu
set TUTORIAL_PETUGAS_USER=panji
set TUTORIAL_PETUGAS_PASS=tutorial2026
```

## Catatan penting

- Script generate **sementara mengatur password** akun petugas demo untuk login saat screenshot.
- Setelah generate, **reset password petugas** lewat menu Admin > Akun Petugas jika perlu.
- Password admin default sistem: `rakerdinma2026` (ganti di production).

## Isi tutorial PDF

1. Pengenalan & alur status (Packing -> Delivery -> Receive -> Done)
2. **Super Admin**: login, dashboard, import Excel, monitoring, detail, kelola petugas
3. **Petugas**: login, kirim buku, surat jalan, terima buku, OCR, list/detail
4. Tips & troubleshooting
