#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build PDF tutorial from screenshots + structured content."""

import json
import os
import sys
from datetime import datetime
from pathlib import Path

try:
    from fpdf import FPDF
except ImportError:
    print("Installing fpdf2...")
    os.system(f"{sys.executable} -m pip install fpdf2 -q")
    from fpdf import FPDF

try:
    from PIL import Image
except ImportError:
    print("Installing pillow...")
    os.system(f"{sys.executable} -m pip install pillow -q")
    from PIL import Image

ROOT = Path(__file__).resolve().parent
SHOTS = ROOT / "screenshots"
META = ROOT / "screenshots.json"
OUT = ROOT / "TUTORIAL-DISTRIBUSI-LKPD-MI-MAARIF.pdf"
VERSION = "Juni 2026"


def ascii_safe(text: str) -> str:
    replacements = {
        "\u2013": "-", "\u2014": "-", "\u2018": "'", "\u2019": "'",
        "\u2022": "-", "\u2192": "->", "\u201c": '"', "\u201d": '"',
    }
    for src, dst in replacements.items():
        text = text.replace(src, dst)
    return text.encode("ascii", "replace").decode("ascii")


def find_shot(shots, shot_id):
    for s in shots:
        if s.get("id") == shot_id:
            return SHOTS / s["file"]
    return None


class TutorialPDF(FPDF):
    def footer(self):
        self.set_y(-15)
        self.set_font("Helvetica", "I", 8)
        self.set_text_color(120, 120, 120)
        self.cell(
            0, 10,
            f"Tutorial Distribusi LKPD MI Ma'arif NU ({VERSION}) - Halaman {self.page_no()}",
            align="C",
        )


def section_title(pdf, title, level=1):
    pdf.set_x(pdf.l_margin)
    pdf.ln(4)
    if level == 1:
        pdf.set_font("Helvetica", "B", 16)
        pdf.set_text_color(22, 101, 52)
    else:
        pdf.set_font("Helvetica", "B", 13)
        pdf.set_text_color(30, 64, 45)
    pdf.multi_cell(0, 8, ascii_safe(title))
    pdf.set_text_color(0, 0, 0)
    pdf.ln(2)


def body(pdf, text):
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Helvetica", "", 10)
    pdf.multi_cell(0, 5.5, ascii_safe(text))
    pdf.ln(1)


def bullet(pdf, text):
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Helvetica", "", 10)
    pdf.multi_cell(0, 5.5, ascii_safe(f"- {text}"))


def numbered(pdf, n, text):
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Helvetica", "", 10)
    pdf.multi_cell(0, 5.5, ascii_safe(f"{n}. {text}"))


def add_image_page(pdf, img_path, caption):
    if not img_path or not img_path.exists():
        body(pdf, f"[Screenshot tidak tersedia: {caption}]")
        return

    img = Image.open(img_path)
    px_w, px_h = img.size
    display_w_mm = 180.0
    display_h_mm = (px_h / px_w) * display_w_mm if px_w else display_w_mm

    first_page_cap_mm = 12
    next_page_cap_mm = 8
    footer_mm = 18
    usable_first = pdf.h - pdf.t_margin - footer_mm - first_page_cap_mm - 8
    usable_next = pdf.h - pdf.t_margin - footer_mm - next_page_cap_mm

    chunks = []
    if display_h_mm <= usable_first:
        chunks.append((0, px_h, caption, usable_first))
    else:
        px_per_mm = px_w / display_w_mm
        y = 0
        part = 1
        while y < px_h:
            usable = usable_first if part == 1 else usable_next
            strip_px = min(int(usable * px_per_mm), px_h - y)
            if strip_px <= 0:
                break
            cap = caption if part == 1 else f"{caption} (lanjutan {part})"
            chunks.append((y, strip_px, cap, usable))
            y += strip_px
            part += 1

    tmp_dir = ROOT / "_pdf_chunks"
    tmp_dir.mkdir(exist_ok=True)

    for idx, (y0, strip_h, cap, max_h_mm) in enumerate(chunks):
        pdf.add_page()
        pdf.set_x(pdf.l_margin)
        pdf.set_font("Helvetica", "B", 11)
        pdf.set_text_color(22, 101, 52)
        pdf.multi_cell(0, 6, ascii_safe(cap))
        pdf.set_text_color(0, 0, 0)
        pdf.ln(2)

        if len(chunks) == 1:
            chunk_path = img_path
        else:
            crop = img.crop((0, y0, px_w, y0 + strip_h))
            chunk_path = tmp_dir / f"{img_path.stem}_p{idx + 1}.png"
            crop.save(chunk_path)

        strip_h_mm = (strip_h / px_w) * display_w_mm
        draw_h = min(strip_h_mm, max_h_mm)
        pdf.image(str(chunk_path), x=pdf.l_margin, w=display_w_mm, h=draw_h)

        if idx == len(chunks) - 1:
            pdf.ln(2)
            pdf.set_x(pdf.l_margin)
            pdf.set_font("Helvetica", "I", 9)
            pdf.set_text_color(100, 100, 100)
            pdf.multi_cell(0, 4, f"Gambar: {img_path.name}")


def build():
    shots = []
    if META.exists():
        shots = json.loads(META.read_text(encoding="utf-8")).get("shots", [])

    pdf = TutorialPDF()
    pdf.set_margins(15, 15, 15)
    pdf.set_auto_page_break(auto=True, margin=18)
    pdf.add_page()

    # Cover
    pdf.set_font("Helvetica", "B", 22)
    pdf.set_text_color(22, 101, 52)
    pdf.multi_cell(0, 10, "PANDUAN LENGKAP")
    pdf.ln(2)
    pdf.set_font("Helvetica", "B", 18)
    pdf.multi_cell(0, 9, "SISTEM TRACKING DISTRIBUSI\nBUKU LKPD MI MA'ARIF NU\nKABUPATEN MAGELANG")
    pdf.ln(6)
    pdf.set_font("Helvetica", "", 11)
    pdf.set_text_color(60, 60, 60)
    body(
        pdf,
        "Dokumen ini menjelaskan langkah demi langkah penggunaan sistem distribusi LKPD "
        "untuk Super Admin dan Petugas Pengiriman. Termasuk fitur terbaru: dashboard infografis, "
        "CRUD satuan pendidikan, tracking Buku Guru, surat jalan Excel otomatis, dan OCR surat jalan.",
    )
    body(pdf, f"Versi: {VERSION}")
    body(pdf, f"Dibuat otomatis: {datetime.now().strftime('%d/%m/%Y %H:%M')} WIB")
    pdf.ln(4)
    pdf.set_font("Helvetica", "B", 11)
    pdf.set_text_color(0, 0, 0)
    body(pdf, "Isi dokumen:")
    for item in [
        "1. Pengenalan & alur status",
        "2. Panduan Super Admin (dashboard infografis, import, CRUD satuan, monitoring, petugas)",
        "3. Panduan Petugas (dashboard infografis, kirim buku, surat jalan, terima buku + Buku Guru, OCR)",
        "4. Tips & troubleshooting",
    ]:
        bullet(pdf, item)

    # --- Pengenalan ---
    pdf.add_page()
    section_title(pdf, "1. Pengenalan Sistem")
    body(
        pdf,
        "Sistem Tracking Distribusi Buku LKPD MI Ma'arif NU digunakan untuk "
        "mencatat pengiriman buku LKPD dari gudang/distributor ke setiap MI Ma'arif, "
        "memantau status per satuan pendidikan, dan mendokumentasikan penerimaan "
        "dengan upload surat jalan.",
    )
    section_title(pdf, "Alamat portal", 2)
    bullet(pdf, "Portal Petugas: /distribusi/ (bisa diakses dari Dashboard Layanan)")
    bullet(pdf, "Portal Super Admin: /admindistribusi/ (URL khusus admin)")
    section_title(pdf, "Peran pengguna", 2)
    bullet(pdf, "Super Admin: import data, kelola satuan, pantau progres, kelola akun petugas")
    bullet(pdf, "Petugas: kirim buku, unduh surat jalan, catat penerimaan, upload dokumen")
    section_title(pdf, "Alur status satuan pendidikan", 2)
    body(
        pdf,
        "Setiap MI melewati siklus status berikut:\n"
        "Packing -> Delivery -> Receive (jika kurang) atau Done (jika lengkap).\n\n"
        "Packing = data sudah diimport, buku siap dikirim.\n"
        "Delivery = petugas sudah mencatat pengiriman, menunggu konfirmasi sekolah.\n"
        "Receive = sekolah menerima sebagian; masih ada kekurangan (bisa dikirim ulang).\n"
        "Done = semua LKPD siswa dan Buku Guru sudah diterima lengkap.",
    )
    section_title(pdf, "Istilah penting", 2)
    bullet(pdf, "K1-K6 = jumlah siswa per kelas (bukan total buku)")
    bullet(pdf, "Buku Guru = kebutuhan buku guru per kelas, dicatat terpisah dari LKPD siswa")
    bullet(pdf, "Total buku = (siswa x jumlah mapel per kelas) + buku guru per kelas")
    bullet(pdf, "Surat Jalan (SJ) = dokumen Excel otomatis berisi daftar mapel + BUKU GURU")

    add_image_page(
        pdf,
        find_shot(shots, "portal-entry"),
        "Gambar 1 - Akses fitur distribusi dari Dashboard Layanan publik",
    )

    # --- ADMIN ---
    pdf.add_page()
    section_title(pdf, "2. Panduan Super Admin")
    body(
        pdf,
        "Super Admin bertugas menyiapkan data sekolah, membuat akun petugas, "
        "mengelola data satuan pendidikan, dan memantau progres distribusi seluruh MI Ma'arif.",
    )

    section_title(pdf, "2.1 Login Super Admin", 2)
    body(pdf, "Buka /admindistribusi/ lalu masukkan password super admin. Tidak ada username.")
    bullet(pdf, "Password default awal: rakerdinma2026 (segera ganti di production)")
    add_image_page(pdf, find_shot(shots, "admin-login"), "Gambar 2 - Halaman login Super Admin")

    section_title(pdf, "2.2 Dashboard Admin (Infografis)", 2)
    body(
        pdf,
        "Dashboard admin menampilkan ringkasan visual distribusi:\n"
        "- Hero dengan total satuan, total buku, total siswa, dan progress bar\n"
        "- Kartu KPI per status (Packing, Delivery, Receive, Done)\n"
        "- Grafik donut: proporsi satuan per status\n"
        "- Grafik batang: volume buku per status\n"
        "- Grafik siswa per kelas (K1-K6)\n"
        "- Grafik pie: komposisi buku LKPD siswa vs Buku Guru\n\n"
        "Gunakan pintasan Import Data, Tambah Satuan, Monitoring, atau Kelola Petugas.",
    )
    add_image_page(pdf, find_shot(shots, "admin-dashboard"), "Gambar 3 - Dashboard Super Admin dengan infografis")

    section_title(pdf, "2.3 Import Data Excel", 2)
    body(
        pdf,
        "Langkah import data kebutuhan buku:",
    )
    numbered(pdf, 1, "Siapkan file REKAP SISWA DAN KEBUTUHAN BUKU LKS MI MAARIF MGL.xlsx")
    numbered(pdf, 2, "Buka menu Import Data")
    numbered(pdf, 3, "Pilih file (.xlsx / .csv)")
    numbered(pdf, 4, "Klik Import Data")
    body(
        pdf,
        "Sistem membaca sheet DATA BUKU PERSEKOLAH (prioritas) atau DATA SEKOLAH DAN SISWA. "
        "Data meliputi jumlah siswa per kelas (K1-K6), buku guru per kelas, dan total buku. "
        "Import ulang akan memperbarui data NPSN yang sama.\n\n"
        "Total buku dihitung otomatis dari siswa x mapel + buku guru. "
        "Jika kolom total Excel (BB) berbeda, sistem menampilkan peringatan saat import.",
    )
    add_image_page(pdf, find_shot(shots, "admin-import"), "Gambar 4 - Halaman Import Data Excel")

    section_title(pdf, "2.4 Tambah & Edit Satuan Manual", 2)
    body(
        pdf,
        "Selain import Excel, admin dapat menambah atau mengubah satuan secara manual:",
    )
    numbered(pdf, 1, "Klik + Tambah Satuan di Monitoring atau dashboard")
    numbered(pdf, 2, "Isi NPSN, nama lembaga, alamat, kecamatan")
    numbered(pdf, 3, "Isi jumlah siswa K1-K6 dan kebutuhan Buku Guru per kelas")
    numbered(pdf, 4, "Sistem menampilkan preview total buku otomatis")
    numbered(pdf, 5, "Klik Simpan")
    body(
        pdf,
        "Edit satuan: klik Edit di daftar Monitoring atau dari halaman Detail. "
        "Hati-hati mengubah status manual jika ada proses pengiriman aktif.\n\n"
        "Hapus satuan: klik Hapus di daftar Monitoring. "
        "Penghapusan diblokir jika status masih Delivery (pengiriman aktif). "
        "Jika dihapus, riwayat pengiriman dan file surat jalan ikut terhapus.",
    )
    add_image_page(pdf, find_shot(shots, "admin-create"), "Gambar 5 - Form Tambah Satuan Pendidikan")
    add_image_page(pdf, find_shot(shots, "admin-edit"), "Gambar 6 - Form Edit Satuan Pendidikan")

    section_title(pdf, "2.5 Monitoring & Export", 2)
    body(
        pdf,
        "Menu Monitoring menampilkan seluruh satuan pendidikan. Filter berdasarkan:",
    )
    bullet(pdf, "Kata kunci (NPSN, nama, alamat)")
    bullet(pdf, "Kecamatan")
    bullet(pdf, "Status (Packing / Delivery / Receive / Done)")
    body(
        pdf,
        "Klik Detail untuk melihat riwayat pengiriman. "
        "Klik Edit/Hapus untuk kelola data. Klik Export CSV untuk unduh laporan.",
    )
    add_image_page(pdf, find_shot(shots, "admin-list"), "Gambar 7 - Halaman Monitoring Satuan")

    section_title(pdf, "2.6 Detail Satuan (Admin)", 2)
    body(
        pdf,
        "Halaman detail menampilkan kebutuhan vs penerimaan per kelas (LKPD Siswa dan Buku Guru), "
        "serta riwayat setiap pengiriman beserta file surat jalan yang diupload petugas. "
        "Admin dapat langsung Edit dari halaman ini.",
    )
    add_image_page(pdf, find_shot(shots, "admin-detail"), "Gambar 8 - Detail Satuan (Admin)")

    section_title(pdf, "2.7 Kelola Akun Petugas", 2)
    body(
        pdf,
        "Buat akun untuk setiap petugas pengiriman:",
    )
    numbered(pdf, 1, "Isi Nama Lengkap, Username, Password (min. 6 karakter)")
    numbered(pdf, 2, "Klik Simpan Akun")
    numbered(pdf, 3, "Bagikan username & password ke petugas")
    body(pdf, "Admin dapat menonaktifkan/mengaktifkan kembali akun petugas dari daftar.")
    add_image_page(pdf, find_shot(shots, "admin-petugas"), "Gambar 9 - Kelola Akun Petugas Pengiriman")

    # --- PETUGAS ---
    pdf.add_page()
    section_title(pdf, "3. Panduan Petugas Pengiriman")
    body(
        pdf,
        "Petugas distributor menggunakan portal /distribusi/ untuk mencatat pengiriman buku, "
        "mengunduh surat jalan Excel, mengirim notifikasi WhatsApp, dan mencatat penerimaan di sekolah.",
    )

    section_title(pdf, "3.1 Login Petugas", 2)
    body(pdf, "Masuk dengan username dan password yang diberikan admin.")
    add_image_page(pdf, find_shot(shots, "petugas-login"), "Gambar 10 - Login Petugas Distribusi")

    section_title(pdf, "3.2 Dashboard Petugas (Infografis)", 2)
    body(
        pdf,
        "Dashboard petugas dirancang untuk tugas operasional harian:\n"
        "- Hero: siap dikirim, menunggu terima, buku siap kirim, % distribusi selesai\n"
        "- Progress bar: Done vs sedang proses (Delivery + Receive)\n"
        "- Kartu KPI per status dengan jumlah satuan dan buku\n"
        "- Grafik donut: status semua satuan\n"
        "- Grafik batang: fokus tugas (Siap Kirim / Sedang Delivery / Selesai)\n"
        "- Grafik volume buku per tahap\n"
        "- Daftar satuan Sedang Delivery dengan tombol Proses Penerimaan\n\n"
        "Kartu aksi cepat Kirim Buku dan Terima Buku menampilkan badge jumlah tugas aktif.",
    )
    add_image_page(pdf, find_shot(shots, "petugas-dashboard"), "Gambar 11 - Dashboard Petugas dengan infografis")

    section_title(pdf, "3.3 Kirim Buku (Packing -> Delivery)", 2)
    body(
        pdf,
        "Langkah pengiriman buku ke sekolah:",
    )
    numbered(pdf, 1, "Buka menu Kirim Buku")
    numbered(pdf, 2, "Cari/filter satuan (NPSN, nama, kecamatan)")
    numbered(pdf, 3, "Klik Surat Jalan - unduh Excel SEBELUM berangkat")
    numbered(pdf, 4, "Bawa buku + surat jalan ke sekolah")
    numbered(pdf, 5, "Klik Kirim Buku - konfirmasi untuk mencatat status Delivery")
    body(
        pdf,
        "Setelah Kirim Buku, sistem mengirim notifikasi WhatsApp ke kepsek/operator "
        "(jika nomor HP ada di data pengkinian). "
        "Satuan dengan status Receive (kurang) juga bisa dikirim ulang.",
    )
    add_image_page(pdf, find_shot(shots, "petugas-kirim"), "Gambar 12 - Halaman Kirim Buku")

    section_title(pdf, "3.4 Surat Jalan Excel", 2)
    body(
        pdf,
        "Surat jalan berisi:\n"
        "- Identitas sekolah (NPSN, nama, alamat)\n"
        "- Daftar mapel LKPD per kelas dengan jumlah buku\n"
        "- Baris BUKU GURU per kelas (setelah blok mapel tiap kelas)\n"
        "- Total buku keseluruhan\n\n"
        "File digenerate otomatis dari data import/admin. "
        "Cetak atau bawa di HP/tablet saat pengiriman. "
        "Format mengikuti template resmi LP Ma'arif NU.",
    )

    section_title(pdf, "3.5 Terima Buku (Delivery -> Receive/Done)", 2)
    body(
        pdf,
        "Setelah sekolah menerima buku:",
    )
    numbered(pdf, 1, "Buka Terima Buku, pilih satuan berstatus Delivery (atau klik dari dashboard)")
    numbered(pdf, 2, "Upload foto Surat Jalan Distributor (boleh dari kamera HP)")
    numbered(pdf, 3, "Upload Surat Jalan Sekolah yang sudah ditandatangani & dicap")
    numbered(pdf, 4, "Isi jumlah LKPD siswa diterima per kelas (angka sesuai kolom Jumlah di SJ)")
    numbered(pdf, 5, "Isi Buku Guru per kelas jika ada kebutuhan guru (baris BUKU GURU di SJ)")
    numbered(pdf, 6, "Klik Simpan Penerimaan")
    body(
        pdf,
        "OCR otomatis: sistem membaca foto surat jalan dan mengisi angka LKPD siswa "
        "serta Buku Guru. Selalu periksa ulang sebelum simpan.\n\n"
        "Jika semua kelas lengkap (LKPD siswa + Buku Guru) -> status Done.\n"
        "Jika masih kurang -> status Receive, satuan muncul kembali di Kirim Buku untuk pengiriman lanjutan.",
    )
    add_image_page(
        pdf,
        find_shot(shots, "petugas-terima"),
        "Gambar 13 - Halaman Terima Buku, Upload Surat Jalan & Input Buku Guru",
    )

    section_title(pdf, "3.6 List & Detail Satuan", 2)
    body(
        pdf,
        "Petugas dapat melihat seluruh satuan dan riwayat pengiriman dari menu List Satuan -> Detail. "
        "Detail menampilkan perbandingan kebutuhan vs diterima per kelas.",
    )
    add_image_page(pdf, find_shot(shots, "petugas-list"), "Gambar 14 - List Satuan (Petugas)")
    add_image_page(pdf, find_shot(shots, "petugas-detail"), "Gambar 15 - Detail Satuan (Petugas)")

    # --- Tips ---
    pdf.add_page()
    section_title(pdf, "4. Tips & Troubleshooting")
    tips = [
        "Total buku di sistem dihitung dari siswa x jumlah mapel + buku guru per kelas. "
        "Harus sama dengan total di surat jalan Excel.",
        "Kolom K1-K6 di tabel = jumlah siswa per kelas, bukan total buku.",
        "Buku Guru dicatat terpisah di form penerimaan (baris BUKU GURU di surat jalan).",
        "Status Done hanya tercapai jika LKPD siswa DAN buku guru semua kelas sudah lengkap.",
        "Jika OCR gagal atau angka salah, isi jumlah buku secara manual.",
        "Foto surat jalan yang jelas dan tidak blur meningkatkan akurasi OCR.",
        "Re-import Excel admin jika data kebutuhan buku berubah di awal tahun.",
        "Satuan baru bisa ditambah manual admin tanpa import ulang seluruh file.",
        "Jangan hapus satuan yang statusnya Delivery; selesaikan penerimaan dulu.",
        "Password admin default harus diganti untuk keamanan production.",
        "Reset password petugas lewat Admin > Akun Petugas jika lupa.",
    ]
    for t in tips:
        bullet(pdf, t)

    section_title(pdf, "Ringkasan alur kerja harian petugas", 2)
    body(
        pdf,
        "Login -> lihat dashboard (prioritas tugas) -> Kirim Buku (unduh SJ + catat kirim) -> "
        "antar buku ke sekolah -> Terima Buku (upload SJ + isi jumlah LKPD & Buku Guru) -> "
        "ulangi jika masih kurang hingga Done.",
    )

    section_title(pdf, "Ringkasan alur kerja admin", 2)
    body(
        pdf,
        "Login -> pantau dashboard infografis -> import/perbarui data Excel -> "
        "kelola satuan manual jika perlu -> buat akun petugas -> monitoring & export laporan.",
    )

    pdf.output(str(OUT))
    print(f"PDF saved: {OUT}")


if __name__ == "__main__":
    build()
