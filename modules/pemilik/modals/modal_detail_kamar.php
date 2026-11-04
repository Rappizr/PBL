<?php
$modal_aktif    = $modal_aktif ?? ($_GET['modal'] ?? '');
$current_page   = $current_page ?? ($_GET['page'] ?? 'manajemen_kamar');
$kamar_terpilih = $kamar_terpilih ?? null;
$daftar_kamar   = $daftar_kamar ?? [];

// Baca file fisik langsung dari disk menggunakan jalur absolut project
$foto_ktp_file = trim($kamar_terpilih['foto_ktp'] ?? '');
$ktp_src_data  = '';
$ada_ktp       = false;

if ($foto_ktp_file !== '' && $foto_ktp_file !== 'ktp_default.jpg' && strtolower($foto_ktp_file) !== 'null') {
    // Cari lokasi fisik file di server
    $lokasi_file = __DIR__ . '/../../../public/uploads/ktp/' . $foto_ktp_file;
    
    if (file_exists($lokasi_file)) {
        // Konversi ke Base64 agar pasti tampil tanpa bergantung pada routing web server
        $ext = strtolower(pathinfo($lokasi_file, PATHINFO_EXTENSION));
        $mime_type = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
        $file_content = file_get_contents($lokasi_file);
        $ktp_src_data = 'data:' . $mime_type . ';base64,' . base64_encode($file_content);
        $ada_ktp = true;
    }
}
?>
<?php if ($modal_aktif === 'detail_kamar' && $kamar_terpilih): ?>
    <?php $is_kosong = ($kamar_terpilih['status_kamar'] === 'kosong' || empty($kamar_terpilih['nama'])); ?>
    <div class="modal-overlay active">
        <div class="modal-card-detail-kamar">
            
            <div class="detail-kamar-header">
                <h3 class="detail-kamar-title">Detail Kamar <?= htmlspecialchars($kamar_terpilih['nomor']); ?></h3>
                <span class="badge-detail-kamar <?= $is_kosong ? 'badge-kosong-detail' : 'badge-terisi'; ?>">
                    <?= $is_kosong ? 'Kosong' : 'Terisi'; ?>
                </span>
            </div>

            <form action="?page=<?= htmlspecialchars($current_page); ?>" method="POST" class="detail-kamar-form">
                <input type="hidden" name="nomor_kamar" value="<?= htmlspecialchars($kamar_terpilih['nomor']); ?>">

                <?php if ($is_kosong): ?>
                    <div class="kamar-empty-box">
                        <i class="fa-solid fa-door-open kamar-empty-icon"></i>
                        <p class="kamar-empty-title">Kamar ini saat ini belum memiliki penghuni.</p>
                        <p class="kamar-empty-rate">
                            Tarif disesuaikan dengan skema sewa yang dipilih saat pendaftaran.
                        </p>
                    </div>
                    <div class="detail-actions-vertical">
                        <a href="?page=<?= htmlspecialchars($current_page); ?>&modal=tambah_penghuni&kamar=<?= urlencode($kamar_terpilih['nomor']); ?>" class="btn-detail-pill btn-detail-simpan">
                            <i class="fa-solid fa-user-plus"></i> Isi Penghuni Kamar Ini
                        </a>

                        <button type="button" class="btn-detail-pill btn-detail-hapus" onclick="bukaModalKonfirmasiHapusKamar()">
                            <i class="fa-solid fa-trash-can"></i> Hapus Kamar
                        </button>
                        
                        <a href="/?page=<?= htmlspecialchars($current_page); ?>" class="btn-detail-pill btn-detail-batal">
                            Tutup
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Nama Penghuni -->
                    <div class="detail-form-group">
                        <label class="detail-label" for="detailNama">Nama Penghuni</label>
                        <input type="text" id="detailNama" name="nama" class="detail-input-pill" 
                               value="<?= htmlspecialchars($kamar_terpilih['nama']); ?>" placeholder="Nama Penghuni" required>
                    </div>

                    <!-- No WA & Email -->
                    <div class="row-input-dual">
                        <div class="detail-form-group flex-1">
                            <label class="detail-label" for="detailWa">No. WhatsApp / HP</label>
                            <input type="tel" id="detailWa" name="no_wa" class="detail-input-pill" 
                                   value="<?= htmlspecialchars($kamar_terpilih['no_wa']); ?>" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="detail-form-group flex-1">
                            <label class="detail-label" for="detailEmail">Email</label>
                            <input type="email" id="detailEmail" name="email" class="detail-input-pill" 
                                   value="<?= htmlspecialchars($kamar_terpilih['email'] ?? ''); ?>" placeholder="nama@email.com">
                        </div>
                    </div>

                    <!-- Kontak Wali & PIN Akun -->
                    <div class="row-input-dual">
                        <div class="detail-form-group flex-1">
                            <label class="detail-label" for="detailWali">No. Kontak Wali</label>
                            <input type="tel" id="detailWali" name="kontak_wali" class="detail-input-pill" 
                                   value="<?= htmlspecialchars($kamar_terpilih['kontak_wali'] ?? ''); ?>" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="detail-form-group flex-1">
                            <label class="detail-label" for="detailPin">PIN Akun (Login)</label>
                            <input type="text" id="detailPin" name="pin" class="detail-input-pill" 
                                   value="<?= htmlspecialchars($kamar_terpilih['pin'] ?? '123456'); ?>" maxlength="6" placeholder="PIN 6 Digit" required>
                        </div>
                    </div>

                    <!-- Status Peran (Ketua Kost) -->
                    <div class="detail-form-group">
                        <label class="detail-label" for="detailIsKetuaKos">Peran Penghuni</label>
                        <select id="detailIsKetuaKos" name="is_ketua_kos" class="detail-select-pill">
                            <option value="0" <?= empty($kamar_terpilih['is_ketua_kos']) ? 'selected' : ''; ?>>Penghuni Biasa</option>
                            <option value="1" <?= !empty($kamar_terpilih['is_ketua_kos']) ? 'selected' : ''; ?>>Ketua Kost (Akses Tambahan)</option>
                        </select>
                    </div>

                    <!-- Dokumen Foto KTP (Thumbnail & Klik Pop-up) -->
                    <div class="detail-form-group">
                        <label class="detail-label">Dokumen Identitas (KTP)</label>
                        <?php if ($ada_ktp): ?>
                            <div class="ktp-preview-box" id="btnPreviewKtpTrigger" onclick="bukaPopupKtpDirect()">
                                <div class="ktp-thumbnail-wrapper">
                                    <img src="<?= $ktp_src_data; ?>" alt="KTP Thumbnail" class="ktp-thumbnail-img">
                                </div>
                                <div class="ktp-preview-info">
                                    <span class="ktp-preview-title"><i class="fa-solid fa-id-card"></i> Foto KTP Tersedia</span>
                                    <span class="ktp-preview-hint">Ketuk untuk memperbesar foto</span>
                                </div>
                                <i class="fa-solid fa-magnifying-glass-plus ktp-zoom-icon"></i>
                            </div>
                        <?php else: ?>
                            <div class="ktp-preview-box ktp-empty-state">
                                <i class="fa-solid fa-id-card ktp-empty-icon"></i>
                                <span>Belum ada foto KTP yang diunggah.</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Skema Sewa Terpilih -->
                    <div class="detail-form-group">
                        <label class="detail-label" for="detailDurasi">Skema Sewa Terpilih</label>
                        <input type="text" id="detailDurasi" name="durasi_sewa" class="detail-input-pill detail-input-readonly" 
                               value="<?= htmlspecialchars($kamar_terpilih['durasi_sewa']); ?>" readonly>
                    </div>

                    <!-- Tanggal Masuk & Selesai -->
                    <div class="detail-row-dates">
                        <div class="detail-form-group detail-col-half">
                            <label class="detail-label" for="detailTglMulai">Tanggal Mulai</label>
                            <input type="date" id="detailTglMulai" name="tgl_mulai" class="detail-input-pill date-box" 
                                   value="<?= htmlspecialchars($kamar_terpilih['tgl_mulai']); ?>" required>
                        </div>
                        <div class="detail-form-group detail-col-half">
                            <label class="detail-label" for="detailTglSelesai">Tanggal Selesai</label>
                            <input type="text" id="detailTglSelesai" name="tgl_selesai" class="detail-input-pill date-box dashed-border" 
                                   value="<?= htmlspecialchars($kamar_terpilih['tgl_selesai_format']); ?>" readonly>
                        </div>
                    </div>

                    <!-- Status Pembayaran -->
                    <div class="detail-form-group">
                        <label class="detail-label" for="detailStatusBayar">Status Pembayaran</label>
                        <select id="detailStatusBayar" name="status_bayar" class="detail-select-pill">
                            <option value="lunas" <?= strtolower($kamar_terpilih['status_bayar']) === 'lunas' ? 'selected' : ''; ?>>Lunas</option>
                            <option value="belum" <?= strtolower($kamar_terpilih['status_bayar']) === 'belum' ? 'selected' : ''; ?>>Belum Bayar</option>
                        </select>
                    </div>

                    <!-- Tombol Aksi Detail Kamar -->
                    <div class="detail-actions-vertical">
                        <button type="submit" name="simpan_detail" class="btn-detail-pill btn-detail-simpan">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                        </button>

                        <button type="button" class="btn-detail-pill btn-detail-pindah" onclick="bukaModalPindahKamar()">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i> Pindah Kamar
                        </button>
                        
                        <button type="button" class="btn-detail-pill btn-detail-hapus" onclick="bukaModalKonfirmasiHapus()">
                            <i class="fa-solid fa-user-xmark"></i> Hapus Penghuni
                        </button>
                        
                        <a href="/?page=<?= htmlspecialchars($current_page); ?>" class="btn-detail-pill btn-detail-batal">
                            Batal
                        </a>
                    </div>
                <?php endif; ?>
            </form>

        </div>
    </div>

    <!-- MODAL POPUP LIHAT FOTO KTP FULLSCREEN -->
    <div id="modalLightboxKtp" class="modal-overlay modal-lightbox-overlay" onclick="tutupPopupKtp()">
        <div class="modal-card-lightbox" onclick="event.stopPropagation()">
            <div class="lightbox-header">
                <span class="lightbox-title"><i class="fa-solid fa-id-card"></i> Foto KTP - <?= htmlspecialchars($kamar_terpilih['nama']); ?></span>
                <button type="button" class="btn-close-lightbox" onclick="tutupPopupKtp()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="lightbox-body">
                <?php if ($ada_ktp): ?>
                    <img id="imgKtpFull" src="<?= $ktp_src_data; ?>" alt="Foto KTP Full" class="lightbox-img">
                <?php else: ?>
                    <p style="color: #ffffff; font-weight: 700; margin: 0;">File foto tidak ditemukan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL POPUP PINDAH KAMAR -->
    <div id="modalPindahKamar" class="modal-overlay">
        <div class="modal-card-konfirmasi-kamar">
            <div class="pindah-icon-wrapper">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <h3 class="konfirmasi-title">Pindah Kamar</h3>
            <p class="konfirmasi-desc">
                Pindahkan penghuni <strong><?= htmlspecialchars($kamar_terpilih['nama']); ?></strong> dari <strong>Kamar <?= htmlspecialchars($kamar_terpilih['nomor']); ?></strong> ke kamar kosong lain:
            </p>

            <form action="?page=<?= htmlspecialchars($current_page); ?>" method="POST">
                <input type="hidden" name="nomor_kamar_asal" value="<?= htmlspecialchars($kamar_terpilih['nomor']); ?>">
                
                <div class="field-group-custom p-bottom-sm">
                    <label class="label-custom text-left">Pilih Kamar Tujuan</label>
                    <select name="nomor_kamar_tujuan" class="select-custom-pill" required>
                        <option value="" disabled selected>-- Pilih Kamar Kosong --</option>
                        <?php 
                        $ada_kosong = false;
                        foreach ($daftar_kamar as $kmr): 
                            if ($kmr['no_kamar'] !== $kamar_terpilih['nomor'] && ($kmr['status_kamar'] === 'kosong' || empty($kmr['nama_penghuni']))): 
                                $ada_kosong = true;
                        ?>
                                <option value="<?= htmlspecialchars($kmr['no_kamar']); ?>">Kamar <?= htmlspecialchars($kmr['no_kamar']); ?> (Kosong)</option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                        <?php if (!$ada_kosong): ?>
                            <option value="" disabled>Tidak ada kamar kosong yang tersedia</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="konfirmasi-actions">
                    <button type="button" class="btn-konfirmasi-batal" onclick="tutupModalPindahKamar()">Batal</button>
                    <button type="submit" name="proses_pindah_kamar" class="btn-konfirmasi-pindah" <?= !$ada_kosong ? 'disabled' : ''; ?>>
                        Pindahkan
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL KONFIRMASI HAPUS PENGHUNI -->
<div id="modalKonfirmasiHapus" class="modal-overlay">
    <div class="modal-card-konfirmasi-kamar">
        <div class="konfirmasi-icon-wrapper">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="konfirmasi-title">Konfirmasi Hapus</h3>
        <p class="konfirmasi-desc">
            Apakah Anda yakin ingin mengeluarkan dan <strong>menghapus akun <?= htmlspecialchars($kamar_terpilih['nama'] ?? 'penghuni ini'); ?></strong> dari sistem? Tindakan ini tidak dapat dibatalkan.
        </p>

        <form action="?page=<?= htmlspecialchars($current_page); ?>" method="POST">
            <input type="hidden" name="nomor_kamar" value="<?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?>">
            <div class="konfirmasi-actions">
                <button type="button" class="btn-konfirmasi-batal" onclick="tutupModalKonfirmasiHapus()">Batal</button>
                <button type="submit" name="hapus_penghuni" class="btn-konfirmasi-hapus">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS KAMAR -->
<div id="modalKonfirmasiHapusKamar" class="modal-overlay">
    <div class="modal-card-konfirmasi-kamar">
        <div class="konfirmasi-icon-wrapper">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="konfirmasi-title">Hapus Kamar?</h3>
        <p class="konfirmasi-desc">
            Apakah Anda yakin ingin menghapus <strong>Kamar <?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?></strong> dari daftar? Tindakan ini tidak dapat dibatalkan.
        </p>

        <form action="?page=<?= htmlspecialchars($current_page); ?>" method="POST">
            <input type="hidden" name="nomor_kamar" value="<?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?>">
            <div class="konfirmasi-actions">
                <button type="button" class="btn-konfirmasi-batal" onclick="tutupModalKonfirmasiHapusKamar()">Batal</button>
                <button type="submit" name="hapus_kamar" class="btn-konfirmasi-hapus">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    function bukaModalKonfirmasiHapus() {
        const m = document.getElementById('modalKonfirmasiHapus');
        if (m) m.classList.add('active');
    }
    function tutupModalKonfirmasiHapus() {
        const m = document.getElementById('modalKonfirmasiHapus');
        if (m) m.classList.remove('active');
    }
    function bukaModalKonfirmasiHapusKamar() {
        const m = document.getElementById('modalKonfirmasiHapusKamar');
        if (m) m.classList.add('active');
    }
    function tutupModalKonfirmasiHapusKamar() {
        const m = document.getElementById('modalKonfirmasiHapusKamar');
        if (m) m.classList.remove('active');
    }
    function bukaModalPindahKamar() {
        const m = document.getElementById('modalPindahKamar');
        if (m) m.classList.add('active');
    }
    function tutupModalPindahKamar() {
        const m = document.getElementById('modalPindahKamar');
        if (m) m.classList.remove('active');
    }

    // Fungsi Buka Pop-up Gambar KTP Langsung
    function bukaPopupKtpDirect() {
        const modal = document.getElementById('modalLightboxKtp');
        if (modal) {
            modal.classList.add('active');
        }
    }
    function tutupPopupKtp() {
        const modal = document.getElementById('modalLightboxKtp');
        if (modal) {
            modal.classList.remove('active');
        }
    }
</script>