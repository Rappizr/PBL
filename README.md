# SITEMAN-KOS

### Sistem Informasi Manajemen Operasional Kos

Aplikasi web berbasis **PHP native** untuk mengelola operasional kos (kamar, penyewaan, tagihan, pembayaran, dan iuran bersama) dengan tiga hak akses: **Pemilik Kos**, **Penanggung Jawab (PJ)**, dan **Penghuni Kos**.

---

## 📌 Deskripsi Singkat

Selama ini pencatatan sewa, iuran, dan komunikasi penagihan di kos masih dilakukan manual (buku catatan & WhatsApp), sehingga rawan salah hitung dan sulit dipantau secara real-time. SITEMAN-KOS menyatukan seluruh proses tersebut ke dalam satu platform berbasis peran.

---

## 🚀 Fitur

### 👤 Pemilik Kos

- [x] Manajemen & monitoring status kamar (kosong/terisi)
- [x] Pendaftaran penghuni baru & pencatatan kontrak sewa
- [x] Verifikasi pembayaran sewa
- [x] Chat penghuni
- [x] Buku kas sewa digital
- [x] Monitoring iuran kamar
- [x] Buku kas iuran digital operasional bersama

### 🏠 Penanggung Jawab (PJ) Kos

- [x] Monitoring iuran kamar
- [x] Kartu rincian tagihan & jatuh tempo mandiri
- [x] Pembayaran sewa kamar & unggah bukti transfer
- [x] Pembayaran iuran bersama & unggah bukti transfer iuran
- [x] Riwayat transaksi & unduh kwitansi digital
- [x] Buku kas iuran digital operasional bersama

### 🧑‍🎓 Penghuni Kos

- [x] Kartu rincian tagihan & jatuh tempo mandiri
- [x] Pembayaran sewa kamar & unggah bukti transfer
- [x] Pembayaran iuran bersama & unggah bukti transfer iuran
- [x] Riwayat transaksi & unduh kwitansi digital
- [x] Buku kas iuran digital operasional bersama

---

## ✅ Checklist Pengerjaan

### 1. Autentikasi & Peran

- [x] Sistem login multi-role (Pemilik, PJ, Penghuni)
- [x] Sistem login khusus Pemilik
- [x] Sistem login khusus PJ dan Penghuni
- [x] Middleware/pengecekan hak akses per role

### 2. Basis Data

- [x] Membuat skema tabel sesuai ERD (Pemilik, Kamar, Penghuni, Penghuni_kamar, Pembayaran, Tagihan, Kategori_iuran, Buku_kas)
- [x] Menyusun relasi antar tabel (foreign key)
- [x] Uji query relasi (sewa, iuran, pembayaran)

### 3. Modul Kamar & Penghuni

- [x] CRUD data kamar
- [x] Pendaftaran penghuni & kontrak sewa
- [x] Status ketersediaan kamar real-time

### 4. Modul Pembayaran & Tagihan

- [x] Tagihan otomatis (sewa & iuran)
- [x] Upload bukti pembayaran/transfer
- [x] Verifikasi pembayaran oleh Pemilik/PJ
- [x] Cetak kwitansi digital

### 5. Modul Buku Kas

- [x] Pencatatan pemasukan/pengeluaran kas bersama
- [x] Rekap transaksi keseluruhan
- [x] Riwayat transaksi per penghuni

### 6. Notifikasi

- Dibatalkan

### 7. Frontend

- [x] Tampilan mobile-friendly Pemilik
- [x] Tampilan mobile-friendly Penghuni
- [x] Tampilan mobile-friendly PJ
- [x] Tampilan dashboard desktop Pemilik
- [x] Tampilan dashboard desktop Penghuni
- [x] Tampilan dashboard desktop PJ
- [x] Halaman Manajemen Kamar & Penghuni
- [x] Halaman Chat penghuni
- [x] Halaman verifikasi pembayaran
- [x] Halaman analisis keuangan
- [x] Halaman Login

### 8. Pengujian & Deployment

- [x] Pengujian fungsional tiap modul
- [x] Perbaikan bug
- [x] Deployment ke server/hosting untuk uji coba

---

## 🛠️ Cara Menjalankan Project (PHP Native)

### Prasyarat

- [xAMPP](https://www.apachefriends.org/) / Laragon / server lokal lain dengan **PHP** dan **MySQL**
- PHP versi 8.0 ke atas (disarankan)
- Browser web
- Composer

### Langkah-langkah

1. **Clone / salin project** ke dalam folder `htdocs` (xAMPP) atau `www` (Laragon):

   ```bash
   git clone https://github.com/Rappizr/PBL siteman-kos
   cd siteman-kos
   composer install
   ```

2. **Konfigurasi koneksi database**, sesuaikan kredensial (host, user, password, nama database) pada file konfigurasi `.env`:

   ```env
   DB_HOST=<Your Database Postgres Host>
   DB_PORT=<You Database Port>
   DB_NAME=<You Database Name>
   DB_USER=<You Database Username>
   DB_PASSWORD=<You Database Password>
   EMAIL_USERNAME=
   EMAIL_PASSWORD=
   ```

3. **Memulai aplikasi** melalui CLI:

   ```
   php -S localhost:3000 router.php
   ```

4. **Akses aplikasi** melalui browser:

   ```
   PRODUCTION : https://pbl-blond.vercel.app/
   DEVELOPMENT : https://pbl-git-dev-rappizrs-projects.vercel.app/ (Hanya pemilik vercel yang bisa mengakses)
   ```

5. Login sesuai role masing-masing (Pemilik, PJ, atau Penghuni) untuk mulai menggunakan sistem.

---

## 👥 Tim Pengembang

| Nama                     | NIM          | Peran                                           |
| ------------------------ | ------------ | ----------------------------------------------- |
| Mukhammad Raffi Zabra    | 254107020059 | Ketua Tim / Pengembang Utama                    |
| Muhammad Ridwan A.       | 254107020187 | Desainer / Analisis / Pengembang Utama          |
| Dandin Sesilia           | 254107020142 | Sekretaris / Dokumentator / Penguji             |
| Naura Fadhilla Aditya P. | 254107020007 | Sekretaris / Dokumentator / Desainer / Analisis |

**Program Studi Sarjana Terapan Teknik Informatika — Politeknik Negeri Malang**
