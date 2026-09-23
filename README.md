# SITEMAN-KOS
### Sistem Informasi Manajemen Operasional Kos

Aplikasi web berbasis **PHP native** untuk mengelola operasional kos (kamar, penyewaan, tagihan, pembayaran, dan iuran bersama) dengan tiga hak akses: **Pemilik Kos**, **Penanggung Jawab (PJ)**, dan **Penghuni Kos**.

---

## 📌 Deskripsi Singkat

Selama ini pencatatan sewa, iuran, dan komunikasi penagihan di kos masih dilakukan manual (buku catatan & WhatsApp), sehingga rawan salah hitung dan sulit dipantau secara real-time. SITEMAN-KOS menyatukan seluruh proses tersebut ke dalam satu platform berbasis peran.

---

## 🚀 Fitur

### 👤 Pemilik Kos
- [ ] Manajemen & monitoring status kamar (kosong/terisi)
- [ ] Pendaftaran penghuni baru & pencatatan kontrak sewa
- [ ] Verifikasi pembayaran sewa
- [ ] Chat penghuni
- [ ] Penagihan sewa otomatis via WhatsApp
- [ ] Buku kas sewa digital
- [ ] Monitoring iuran kamar
- [ ] Penagihan iuran otomatis via WhatsApp
- [ ] Buku kas iuran digital operasional bersama

### 🏠 Penanggung Jawab (PJ) Kos
- [ ] Monitoring iuran kamar
- [ ] Penagihan iuran otomatis via WhatsApp
- [ ] Kartu rincian tagihan & jatuh tempo mandiri
- [ ] Pembayaran sewa kamar & unggah bukti transfer
- [ ] Pembayaran iuran bersama & unggah bukti transfer iuran
- [ ] Riwayat transaksi & unduh kwitansi digital
- [ ] Buku kas iuran digital operasional bersama

### 🧑‍🎓 Penghuni Kos
- [ ] Kartu rincian tagihan & jatuh tempo mandiri
- [ ] Pembayaran sewa kamar & unggah bukti transfer
- [ ] Pembayaran iuran bersama & unggah bukti transfer iuran
- [ ] Riwayat transaksi & unduh kwitansi digital
- [ ] Buku kas iuran digital operasional bersama


---

## ✅ Checklist Pengerjaan

### 1. Autentikasi & Peran
- [ ] Sistem login multi-role (Pemilik, PJ, Penghuni)
- [ ] Sistem login khusus Pemilik
- [ ] Sistem login khusus PJ dan Penghuni
- [ ] Middleware/pengecekan hak akses per role

### 2. Basis Data
- [ ] Membuat skema tabel sesuai ERD (Pemilik, Kamar, Penghuni, Penghuni_kamar, Pembayaran, Tagihan, Kategori_iuran, Buku_kas)
- [ ] Menyusun relasi antar tabel (foreign key)
- [ ] Uji query relasi (sewa, iuran, pembayaran)

### 3. Modul Kamar & Penghuni
- [ ] CRUD data kamar
- [ ] Pendaftaran penghuni & kontrak sewa
- [ ] Status ketersediaan kamar real-time

### 4. Modul Pembayaran & Tagihan
- [ ] Tagihan otomatis (sewa & iuran)
- [ ] Upload bukti pembayaran/transfer
- [ ] Verifikasi pembayaran oleh Pemilik/PJ
- [ ] Cetak kwitansi digital

### 5. Modul Buku Kas
- [ ] Pencatatan pemasukan/pengeluaran kas bersama
- [ ] Rekap transaksi keseluruhan
- [ ] Riwayat transaksi per penghuni

### 6. Notifikasi
- [ ] Integrasi pengingat/penagihan otomatis via WhatsApp

### 7. Frontend
- [ ] Tampilan mobile-friendly Pemilik
- [ ] Tampilan mobile-friendly Penghuni
- [ ] Tampilan mobile-friendly PJ
- [ ] Tampilan dashboard desktop Pemilik
- [ ] Tampilan dashboard desktop Penghuni
- [ ] Tampilan dashboard desktop PJ
- [ ] Halaman Dashboard
- [ ] Halaman Manajemen Kamar & Penghuni
- [ ] Halaman Chat penghuni
- [ ] Halaman verifikasi pembayaran
- [ ] Halaman analisis keuangan
- [  ] Halaman Login

### 8. Pengujian & Deployment
- [ ] Pengujian fungsional tiap modul
- [ ] Perbaikan bug
- [ ] Deployment ke server/hosting untuk uji coba

---

## 🛠️ Cara Menjalankan Project (PHP Native)

### Prasyarat
- [XAMPP](https://www.apachefriends.org/) / Laragon / server lokal lain dengan **PHP** dan **MySQL**
- PHP versi 8.0 ke atas (disarankan)
- Browser web

### Langkah-langkah

1. **Clone / salin project** ke dalam folder `htdocs` (XAMPP) atau `www` (Laragon):
   ```bash
   git clone https://github.com/Rappizr/PBL siteman-kos
   ```

2. **Konfigurasi koneksi database**, sesuaikan kredensial (host, user, password, nama database) pada file konfigurasi, misalnya `config/database.php`:
   ```php
   <?php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $dbname = "siteman_kos";

   $conn = new mysqli($host, $user, $pass, $dbname);
   if ($conn->connect_error) {
       die("Koneksi gagal: " . $conn->connect_error);
   }
   ```

3. **Memulai aplikasi** melalui CLI:
   ```
   php -S localhost:3000
   ```

4. **Akses aplikasi** melalui browser:
   ```
   http://localhost:3000/siteman-kos
   ```

5. Login sesuai role masing-masing (Pemilik, PJ, atau Penghuni) untuk mulai menggunakan sistem.

---

## 👥 Tim Pengembang

| Nama | NIM | Peran |
|---|---|---|
| Mukhammad Raffi Zabra | 254107020059 | Ketua Tim / Pengembang Utama |
| Muhammad Ridwan A. | 254107020187 | Desainer / Analisis / Pengembang Utama |
| Dandin Sesilia | 254107020142 | Sekretaris / Dokumentator / Penguji |
| Naura Fadhilla Aditya P. | 254107020007 | Sekretaris / Dokumentator / Desainer / Analisis |

**Program Studi Sarjana Terapan Teknik Informatika — Politeknik Negeri Malang**