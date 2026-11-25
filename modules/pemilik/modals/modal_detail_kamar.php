<?php
$modal_aktif         = $modal_aktif ?? ($_GET['modal'] ?? '');
$current_page        = $current_page ?? ($_GET['page'] ?? 'manajemen_kamar');
$semua_kamar         = $semua_kamar ?? [];
$kamar_terpilih      = $kamar_terpilih ?? null;
$daftar_kamar_kosong = $daftar_kamar_kosong ?? [];
$list_skema          = $list_skema ?? [];
$ketuaAktif          = $ketuaAktif ?? null;

$is_modal_detail_open = ($modal_aktif === 'detail_kamar' && !empty($kamar_terpilih));
$is_kosong  = (!$is_modal_detail_open || ($kamar_terpilih['status_kamar'] ?? 'kosong') === 'kosong' || empty($kamar_terpilih['nama']));
$statusAkun = strtolower(trim($kamar_terpilih['status_akun'] ?? 'aktif'));
$is_freeze  = (!$is_kosong && $statusAkun === 'freeze');
$infoSewa   = $kamar_terpilih['info_sewa'] ?? null;
$catatanRaw = trim((string)($kamar_terpilih['catatan_tunggakan'] ?? ''));
$isDitolak  = str_starts_with($catatanRaw, '[Ditolak]');
$catatanInputVal = $isDitolak ? '' : $catatanRaw;

$ktp_url_awal = $kamar_terpilih['ktp_url'] ?? '';
$ada_ktp      = ($ktp_url_awal !== '');
?>

<!-- MODAL UTAMA DETAIL KAMAR (SELALU SIAP DI DOM UNTUK BUKA INSTAN 0 MS) -->
<div id="modalDetailKamar" class="modal-overlay <?= $is_modal_detail_open ? 'active' : ''; ?>" onclick="tutupSemuaModalInstan(event)">
    <div class="modal-card-detail-kamar" onclick="event.stopPropagation()">
        
        <div class="detail-kamar-header">
            <h3 class="detail-kamar-title" id="detailModalTitle">Detail Kamar <?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?></h3>
            <?php if ($is_kosong): ?>
                <span class="badge-detail-kamar badge-kosong-detail" id="detailModalBadge">Kosong</span>
            <?php elseif ($is_freeze): ?>
                <span class="badge-detail-kamar badge-detail-freeze" id="detailModalBadge">Akun Freeze</span>
            <?php else: ?>
                <span class="badge-detail-kamar badge-terisi" id="detailModalBadge">Terisi</span>
            <?php endif; ?>
        </div>

        <!-- PANEL FREEZE / PERINGATAN TUNGGAKAN (DINAMIS) -->
        <div id="detailFreezeWrapper">
            <?php if (!$is_kosong && ($is_freeze || !empty($infoSewa))): ?>
                <div class="detail-freeze-panel <?= $is_freeze ? 'panel-freeze-danger' : 'panel-freeze-warning'; ?>">
                    <div class="detail-freeze-head">
                        <i class="fa-solid <?= $is_freeze ? 'fa-lock' : 'fa-clock'; ?>"></i>
                        <span><?= htmlspecialchars($infoSewa['label_status'] ?? 'Status Akun Dibekukan (Freeze)'); ?></span>
                    </div>
                    <?php if (!empty($infoSewa)): ?>
                        <p class="detail-freeze-summary">
                            <?php if (($infoSewa['jenis_tunggakan'] ?? 'sewa') === 'iuran'): ?>
                                Tunggakan: <strong><?= htmlspecialchars($infoSewa['nama_iuran'] ?? 'Iuran'); ?></strong>
                            <?php else: ?>
                                Tunggakan: <strong><?= (int)$infoSewa['jumlah_bulan_nunggak']; ?> bulan</strong>
                            <?php endif; ?>
                            (Total <strong>Rp <?= number_format((float)$infoSewa['total_tunggakan'], 0, ',', '.'); ?></strong>) &bull;
                            Batas akhir: <strong><?= htmlspecialchars($infoSewa['batas_freeze_fmt']); ?></strong>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($kamar_terpilih['tanggal_janji_bayar'])): ?>
                        <p class="detail-janji-note">
                            <i class="fa-solid fa-handshake"></i> <strong>Pengajuan Janji Bayar:</strong> <?= date('d/m/Y', strtotime($kamar_terpilih['tanggal_janji_bayar'])); ?>
                            <?= $catatanInputVal !== '' ? ' — ' . htmlspecialchars($catatanInputVal) : ''; ?>
                        </p>
                    <?php elseif ($isDitolak): ?>
                        <p class="detail-janji-note">
                            <i class="fa-solid fa-circle-xmark"></i> <strong>Status Sebelumnya:</strong> <?= htmlspecialchars($catatanRaw); ?>
                        </p>
                    <?php endif; ?>

                    <form action="?page=<?= htmlspecialchars($current_page); ?>" method="POST" class="detail-freeze-form">
                        <input type="hidden" name="nomor_kamar" class="sync-nomor-kamar" value="<?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?>">
                        <input type="hidden" name="penghuni_id" value="<?= (int)($kamar_terpilih['penghuni_id'] ?? 0); ?>">

                        <div class="row-input-dual">
                            <div class="detail-form-group flex-1">
                                <label class="detail-freeze-label">Tanggal Janji Bayar</label>
                                <div class="modal-input-icon-wrapper">
                                    <i class="fa-regular fa-calendar modal-input-icon"></i>
                                    <input type="text" name="tanggal_janji_bayar"
                                           id="detailFreezeTglJanji"
                                           value="<?= htmlspecialchars($kamar_terpilih['tanggal_janji_bayar'] ?? ''); ?>"
                                           placeholder="Pilih tanggal..."
                                           class="detail-freeze-date-input modal-input-with-icon" required readonly>
                                </div>
                            </div>
                            <div class="detail-form-group flex-1">
                                <label class="detail-freeze-label">Keterangan Tunggakan</label>
                                <input type="text" name="catatan_tunggakan"
                                       value="<?= htmlspecialchars($catatanInputVal); ?>"
                                       placeholder="Misal: Disetujui bayar tgl 21"
                                       class="detail-freeze-text-input">
                            </div>
                        </div>

                        <button type="submit" name="buka_freeze_penghuni" value="1"
                                onclick="ubahIkonKunciDetail(this, 'Memproses...')"
                                class="btn-detail-unfreeze">
                            <i class="fa-solid <?= $is_freeze ? 'fa-lock-open' : 'fa-calendar-check'; ?>"></i>
                            <span><?= $is_freeze ? 'Setujui & Aktifkan Kembali Akun (Unfreeze)' : 'Simpan Perpanjangan Janji Bayar'; ?></span>
                        </button>

                        <?php if ($is_freeze && !empty($kamar_terpilih['tanggal_janji_bayar'])): ?>
                            <input type="text" name="alasan_tolak_janji"
                                   placeholder="Alasan penolakan (contoh: Maksimal tanggal 19 Nov)..."
                                   class="detail-freeze-text-input">
                            <button type="submit" name="tolak_janji_bayar" value="1"
                                    onclick="ubahIkonKunciDetail(this, 'Menolak...')"
                                    class="btn-detail-pill btn-detail-hapus">
                                <i class="fa-solid fa-xmark"></i>
                                <span>Tolak Pengajuan Janji Bayar</span>
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- BLOK 1: TAMPILAN JIKA KAMAR KOSONG -->
        <div id="detailBlokKosong" class="detail-kamar-form" style="display: <?= $is_kosong ? 'flex' : 'none'; ?> !important;">
            <div class="kamar-empty-box">
                <i class="fa-solid fa-door-open kamar-empty-icon"></i>
                <p class="kamar-empty-title">Kamar ini saat ini belum memiliki penghuni.</p>
                <p class="kamar-empty-rate">
                    Tarif disesuaikan dengan skema sewa yang dipilih saat pendaftaran.
                </p>
            </div>
            <div class="detail-actions-vertical">
                <button type="button" id="btnIsiKamarKosong"
                        onclick="isiKamarKosongInstan('<?= htmlspecialchars($kamar_terpilih['nomor'] ?? '', ENT_QUOTES); ?>')"
                        class="btn-detail-pill btn-detail-simpan">
                    <i class="fa-solid fa-user-plus"></i> Isi Penghuni Kamar Ini
                </button>

                <button type="button" class="btn-detail-pill btn-detail-hapus" onclick="bukaModalKonfirmasiHapusKamar()">
                    <i class="fa-solid fa-trash-can"></i> Hapus Kamar
                </button>
                
                <button type="button" onclick="tutupSemuaModalInstan(event)" class="btn-detail-pill btn-detail-batal">
                    Tutup
                </button>
            </div>
        </div>

        <!-- BLOK 2: FORM DETAIL JIKA KAMAR TERISI -->
        <form action="?page=<?= htmlspecialchars($current_page); ?>" method="POST" enctype="multipart/form-data"
              class="detail-kamar-form" id="formDetailPenghuni" style="display: <?= !$is_kosong ? 'flex' : 'none'; ?> !important;">
            <input type="hidden" name="nomor_kamar" class="sync-nomor-kamar" value="<?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?>">
            <input type="hidden" name="simpan_detail" value="1">

            <!-- Nama Penghuni -->
            <div class="detail-form-group">
                <label class="detail-label" for="detailNama">Nama Penghuni</label>
                <input type="text" id="detailNama" name="nama" class="detail-input-pill" 
                       value="<?= htmlspecialchars($kamar_terpilih['nama'] ?? ''); ?>" placeholder="Nama Penghuni" required>
            </div>

            <!-- No WA & Email -->
            <div class="row-input-dual">
                <div class="detail-form-group flex-1">
                    <label class="detail-label" for="detailWa">No. WhatsApp / HP</label>
                    <input type="tel" id="detailWa" name="no_wa" class="detail-input-pill" 
                           value="<?= htmlspecialchars($kamar_terpilih['no_wa'] ?? ''); ?>" placeholder="08xxxxxxxxxx" required>
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
                    <label class="detail-label" for="detailWali">No. Kontak Wali (Opsional)</label>
                    <input type="tel" id="detailWali" name="kontak_wali" class="detail-input-pill" 
                           value="<?= htmlspecialchars($kamar_terpilih['kontak_wali'] ?? ''); ?>" placeholder="08xxxxxxxxxx (Opsional)">
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

            <!-- Dokumen Foto KTP -->
            <div class="detail-form-group">
                <label class="detail-label">Dokumen Identitas (KTP)</label>
                <div class="ktp-preview-box <?= $ada_ktp ? '' : 'ktp-empty-state'; ?>" id="btnPreviewKtpTrigger"
                     <?php if ($ada_ktp): ?>onclick="bukaPopupKtpDirect()"<?php endif; ?>>
                    <?php if ($ada_ktp): ?>
                        <div class="ktp-thumbnail-wrapper">
                            <img src="<?= htmlspecialchars($ktp_url_awal); ?>" alt="KTP Thumbnail" class="ktp-thumbnail-img" id="detailKtpThumb">
                        </div>
                        <div class="ktp-preview-info">
                            <span class="ktp-preview-title"><i class="fa-solid fa-id-card"></i> Foto KTP Tersedia</span>
                            <span class="ktp-preview-hint">Ketuk untuk memperbesar foto</span>
                        </div>
                        <i class="fa-solid fa-magnifying-glass-plus ktp-zoom-icon"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-id-card ktp-empty-icon"></i>
                        <span>Belum ada foto KTP yang diunggah.</span>
                    <?php endif; ?>
                </div>

                <label class="detail-ktp-upload-btn" for="detailFotoKtpInput">
                    <i class="fa-solid fa-camera"></i>
                    <span id="detailKtpFileName"><?= $ada_ktp ? 'Ganti Foto KTP' : 'Unggah Foto KTP'; ?></span>
                    <input type="file" id="detailFotoKtpInput" name="foto_ktp_baru" accept="image/jpeg,image/png,image/webp" onchange="previewDetailKtp(this)">
                </label>
            </div>

            <!-- Skema Sewa Terpilih -->
            <div class="detail-form-group">
                <label class="detail-label" for="detailSkemaId">Skema Sewa Terpilih</label>
                <select id="detailSkemaId" name="skema_id" class="detail-select-pill" onchange="hitungTanggalSelesaiDetail()" required>
                    <?php foreach ($list_skema as $skm): ?>
                        <?php 
                            $selected = ($skm['id'] == ($kamar_terpilih['skema_id'] ?? 0) || $skm['nama_skema'] === ($kamar_terpilih['durasi_sewa'] ?? '')) ? 'selected' : ''; 
                        ?>
                        <option value="<?= (int)$skm['id']; ?>" 
                                data-tipe="<?= htmlspecialchars($skm['tipe_periode']); ?>" 
                                data-nilai="<?= (int)$skm['durasi_nilai']; ?>" 
                                data-harga="<?= (float)($skm['nominal'] ?? 0); ?>" 
                                <?= $selected; ?>>
                            <?= htmlspecialchars($skm['nama_skema']); ?> (<?= (int)$skm['durasi_nilai']; ?> <?= htmlspecialchars($skm['tipe_periode']); ?> - Rp <?= number_format((float)($skm['nominal'] ?? 0), 0, ',', '.'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tanggal Masuk & Selesai -->
            <div class="detail-row-dates">
                <div class="detail-form-group detail-col-half">
                    <label class="detail-label" for="detailTglMulai">Tanggal Mulai</label>
                    <div class="modal-input-icon-wrapper">
                        <i class="fa-regular fa-calendar modal-input-icon"></i>
                        <input type="text" id="detailTglMulai" name="tgl_mulai"
                               class="detail-input-pill date-box modal-input-with-icon"
                               value="<?= htmlspecialchars($kamar_terpilih['tgl_mulai'] ?? date('Y-m-d')); ?>"
                               placeholder="Pilih tanggal..." required readonly>
                    </div>
                </div>
                <div class="detail-form-group detail-col-half">
                    <label class="detail-label" for="detailTglSelesai">Tanggal Selesai</label>
                    <input type="text" id="detailTglSelesai" name="tgl_selesai" class="detail-input-pill date-box dashed-border" 
                           value="<?= htmlspecialchars($kamar_terpilih['tgl_selesai_format'] ?? '-'); ?>" readonly>
                </div>
            </div>

            <!-- Status Pembayaran -->
            <?php 
                $status_current = strtolower($kamar_terpilih['status_bayar'] ?? 'belum_bayar');
                $is_lunas = ($status_current === 'lunas');
            ?>
            <div class="detail-form-group">
                <label class="detail-label" for="detailStatusBayar">Status Pembayaran</label>
                <select id="detailStatusBayar" name="status_bayar" class="detail-select-pill">
                    <option value="lunas" <?= $is_lunas ? 'selected' : ''; ?>>Lunas</option>
                    <option value="belum_bayar" <?= !$is_lunas ? 'selected' : ''; ?>>Belum Bayar</option>
                </select>
            </div>

            <!-- Tombol Aksi Detail Kamar -->
            <div class="detail-actions-vertical">
                <button type="submit" id="btnSimpanDetailPenghuni" class="btn-detail-pill btn-detail-simpan">
                    <i class="fa-solid fa-floppy-disk" id="iconSimpanDetail"></i>
                    <span id="textSimpanDetail">Simpan Perubahan</span>
                </button>

                <button type="button" class="btn-detail-pill btn-detail-pindah" onclick="bukaModalPindahKamar()">
                    <i class="fa-solid fa-arrow-right-arrow-left"></i> Pindah Kamar
                </button>
                
                <button type="button" class="btn-detail-pill btn-detail-hapus" onclick="bukaModalKonfirmasiHapus()">
                    <i class="fa-solid fa-user-xmark"></i> Hapus Penghuni
                </button>
                
                <button type="button" onclick="tutupSemuaModalInstan(event)" class="btn-detail-pill btn-detail-batal">
                    Batal
                </button>
            </div>
        </form>

    </div>
</div>

<!-- MODAL POPUP LIHAT FOTO KTP FULLSCREEN -->
<div id="modalLightboxKtp" class="modal-overlay modal-lightbox-overlay" onclick="tutupPopupKtp()">
    <div class="modal-card-lightbox" onclick="event.stopPropagation()">
        <div class="lightbox-header">
            <span class="lightbox-title"><i class="fa-solid fa-id-card"></i> Foto KTP - <span id="lightboxKtpNama"><?= htmlspecialchars($kamar_terpilih['nama'] ?? ''); ?></span></span>
            <button type="button" class="btn-close-lightbox" onclick="tutupPopupKtp()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="lightbox-body" id="lightboxKtpBody">
            <?php if ($ada_ktp): ?>
                <img id="imgKtpFull" src="<?= htmlspecialchars($ktp_url_awal); ?>" alt="Foto KTP Full" class="lightbox-img">
            <?php else: ?>
                <p class="lightbox-empty-msg" id="lightboxEmptyMsg">File foto tidak ditemukan.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL POPUP PINDAH KAMAR -->
<div id="modalPindahKamar" class="modal-overlay" onclick="tutupModalPindahKamar()">
    <div class="modal-card-konfirmasi-kamar" onclick="event.stopPropagation()">
        <div class="pindah-icon-wrapper">
            <i class="fa-solid fa-door-open"></i>
        </div>
        <h3 class="konfirmasi-title">Pindah Kamar</h3>
        <p class="konfirmasi-desc">
            Pindahkan penghuni <strong id="namaPenghuniPindahText"><?= htmlspecialchars($kamar_terpilih['nama'] ?? ''); ?></strong> dari <strong id="nomorKamarAsalText">Kamar <?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?></strong> ke kamar kosong lain:
        </p>

        <form action="?page=<?= htmlspecialchars($current_page); ?>" method="POST" onsubmit="handleSubmitPindahKamar(this)">
            <input type="hidden" name="nomor_kamar_asal" class="sync-nomor-kamar" value="<?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?>">
            <input type="hidden" name="proses_pindah_kamar" value="1">
            
            <div class="field-group-custom p-bottom-sm">
                <label class="label-custom text-left">Pilih Kamar Tujuan</label>
                <select name="nomor_kamar_tujuan" class="select-custom-pill" required>
                    <option value="" disabled selected>-- Pilih Kamar Kosong --</option>
                    <?php 
                    $ada_kosong = !empty($daftar_kamar_kosong);
                    foreach ($daftar_kamar_kosong as $kmr): 
                    ?>
                        <option value="<?= htmlspecialchars($kmr['no_kamar']); ?>">Kamar <?= htmlspecialchars($kmr['no_kamar']); ?> (Kosong)</option>
                    <?php endforeach; ?>
                    <?php if (!$ada_kosong): ?>
                        <option value="" disabled>Tidak ada kamar kosong yang tersedia</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="konfirmasi-actions">
                <button type="button" id="btnBatalPindahKamar" class="btn-konfirmasi-batal" onclick="tutupModalPindahKamar()">Batal</button>
                <button type="submit" id="btnYaPindahKamar" class="btn-konfirmasi-pindah" <?= !$ada_kosong ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-arrow-right-arrow-left" id="iconYaPindahKamar"></i>
                    <span id="textYaPindahKamar">Pindahkan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL KONFIRMASI TRANSFER KETUA KOS -->
<div id="modalKonfirmasiKetua" class="modal-overlay modal-z-high" onclick="batalTransferKetua()">
    <div class="modal-card-konfirmasi-kamar" onclick="event.stopPropagation()">
        <div class="konfirmasi-icon-wrapper icon-ketua-warning">
            <i class="fa-solid fa-user-shield"></i>
        </div>
        <h3 class="konfirmasi-title">Ganti Ketua Kost?</h3>
        <p class="konfirmasi-desc" id="descKonfirmasiKetua">
            Ketua Kost saat ini adalah <strong id="namaKetuaLamaText"></strong>.<br><br>Apakah Anda yakin ingin memindahkan jabatan Ketua Kost kepada penghuni ini?
        </p>
        <div class="konfirmasi-actions">
            <button type="button" id="btnBatalTransferKetua" class="btn-konfirmasi-batal" onclick="batalTransferKetua()">Batal</button>
            <button type="button" class="btn-konfirmasi-pindah" id="btnSetujuTransferKetua">
                <i class="fa-solid fa-check" id="iconSetujuKetua"></i>
                <span id="textSetujuKetua">Ya, Pindahkan</span>
            </button>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS PENGHUNI -->
<div id="modalKonfirmasiHapus" class="modal-overlay" onclick="tutupModalKonfirmasiHapus()">
    <div class="modal-card-konfirmasi-kamar" onclick="event.stopPropagation()">
        <div class="konfirmasi-icon-wrapper">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="konfirmasi-title">Konfirmasi Hapus</h3>
        <p class="konfirmasi-desc">
            Apakah Anda yakin ingin mengeluarkan dan <strong id="namaPenghuniHapusText">menghapus akun <?= htmlspecialchars($kamar_terpilih['nama'] ?? 'penghuni ini'); ?></strong> dari sistem? Tindakan ini tidak dapat dibatalkan.
        </p>

        <form action="?page=<?= htmlspecialchars($current_page); ?>" method="POST" onsubmit="handleSubmitHapusPenghuni(this)">
            <input type="hidden" name="nomor_kamar" class="sync-nomor-kamar" value="<?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?>">
            <input type="hidden" name="hapus_penghuni" value="1">
            <div class="konfirmasi-actions">
                <button type="button" id="btnBatalHapusPenghuni" class="btn-konfirmasi-batal" onclick="tutupModalKonfirmasiHapus()">Batal</button>
                <button type="submit" id="btnYaHapusPenghuni" class="btn-konfirmasi-hapus">
                    <i class="fa-solid fa-trash-can" id="iconYaHapusPenghuni"></i>
                    <span id="textYaHapusPenghuni">Ya, Hapus</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS KAMAR -->
<div id="modalKonfirmasiHapusKamar" class="modal-overlay" onclick="tutupModalKonfirmasiHapusKamar()">
    <div class="modal-card-konfirmasi-kamar" onclick="event.stopPropagation()">
        <div class="konfirmasi-icon-wrapper">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="konfirmasi-title">Hapus Kamar?</h3>
        <p class="konfirmasi-desc">
            Apakah Anda yakin ingin menghapus <strong id="teksKonfirmasiHapusKamar">Kamar <?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?></strong> dari daftar? Tindakan ini tidak dapat dibatalkan.
        </p>

        <form action="?page=<?= htmlspecialchars($current_page); ?>" method="POST" onsubmit="handleSubmitHapusKamar(this)">
            <input type="hidden" name="nomor_kamar" class="sync-nomor-kamar" value="<?= htmlspecialchars($kamar_terpilih['nomor'] ?? ''); ?>">
            <input type="hidden" name="hapus_kamar" value="1">
            <div class="konfirmasi-actions">
                <button type="button" id="btnBatalHapusKamar" class="btn-konfirmasi-batal" onclick="tutupModalKonfirmasiHapusKamar()">Batal</button>
                <button type="submit" id="btnYaHapusKamar" class="btn-konfirmasi-hapus">
                    <i class="fa-solid fa-trash-can" id="iconYaHapusKamar"></i>
                    <span id="textYaHapusKamar">Ya, Hapus</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const DATA_SEMUA_KAMAR = <?= json_encode(array_column($semua_kamar, null, 'nomor'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const CURRENT_PAGE_KAMAR = <?= json_encode($current_page); ?>;
    const MIN_DATE_HARI_INI = <?= json_encode(date('Y-m-d')); ?>;
    window.idPenghuniAktifDetail = <?= json_encode((string)($kamar_terpilih['penghuni_id'] ?? '')); ?>;

    let fpDetailTglMulai = null;
    let fpDetailFreezeJanji = null;

    function initFlatpickrDetailTanggalMulai(defaultVal = null) {
        const el = document.getElementById('detailTglMulai');
        if (!el || typeof flatpickr === 'undefined') return;

        if (fpDetailTglMulai) {
            if (defaultVal !== null) {
                fpDetailTglMulai.setDate(defaultVal || null, false);
            }
            return;
        }

        fpDetailTglMulai = flatpickr(el, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d / m / Y",
            altInputClass: "detail-input-pill date-box modal-input-with-icon",
            disableMobile: true,
            onReady: function (selectedDates, dateStr, instance) {
                if (instance.altInput) {
                    instance.altInput.placeholder = "Pilih tanggal...";
                    instance.altInput.readOnly = true;
                }
            },
            onChange: function () {
                hitungTanggalSelesaiDetail();
            }
        });
    }

    function initFlatpickrDetailFreezeJanji(defaultVal = null) {
        const el = document.getElementById('detailFreezeTglJanji');
        if (!el || typeof flatpickr === 'undefined') return;

        if (fpDetailFreezeJanji) {
            fpDetailFreezeJanji.destroy();
            fpDetailFreezeJanji = null;
        }

        fpDetailFreezeJanji = flatpickr(el, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d / m / Y",
            altInputClass: "detail-freeze-date-input modal-input-with-icon",
            minDate: MIN_DATE_HARI_INI,
            defaultDate: defaultVal !== null ? (defaultVal || null) : (el.value || null),
            disableMobile: true,
            onReady: function (selectedDates, dateStr, instance) {
                if (instance.altInput) {
                    instance.altInput.placeholder = "Pilih tanggal...";
                    instance.altInput.readOnly = true;
                }
            }
        });
    }

    function escapeHtmlDetail(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatTanggalIndoShort(dateStr) {
        if (!dateStr) return '-';
        const parts = String(dateStr).split('-');
        if (parts.length !== 3) return dateStr;
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    function renderPanelFreezeDetail(kmr, isFreeze) {
        const freezeWrap = document.getElementById('detailFreezeWrapper');
        if (!freezeWrap) return;

        if (fpDetailFreezeJanji) {
            fpDetailFreezeJanji.destroy();
            fpDetailFreezeJanji = null;
        }

        const infoSewa = kmr.info_sewa || null;
        if (!isFreeze && !infoSewa) {
            freezeWrap.innerHTML = '';
            return;
        }

        const panelClass   = isFreeze ? 'panel-freeze-danger' : 'panel-freeze-warning';
        const iconClass    = isFreeze ? 'fa-lock' : 'fa-clock';
        const labelStatus  = (infoSewa && infoSewa.label_status) ? infoSewa.label_status : 'Status Akun Dibekukan (Freeze)';
        const catatanRaw   = String(kmr.catatan_tunggakan || '').trim();
        const isDitolak    = catatanRaw.startsWith('[Ditolak]');
        const catatanInput = isDitolak ? '' : catatanRaw;

        let summaryHtml = '';
        if (infoSewa) {
            const jenisText = (infoSewa.jenis_tunggakan === 'iuran')
                ? `Tunggakan: <strong>${escapeHtmlDetail(infoSewa.nama_iuran || 'Iuran')}</strong>`
                : `Tunggakan: <strong>${parseInt(infoSewa.jumlah_bulan_nunggak || 0, 10)} bulan</strong>`;
            const nominalFmt = new Intl.NumberFormat('id-ID').format(Number(infoSewa.total_tunggakan || 0));
            summaryHtml = `
                <p class="detail-freeze-summary">
                    ${jenisText}
                    (Total <strong>Rp ${nominalFmt}</strong>) &bull;
                    Batas akhir: <strong>${escapeHtmlDetail(infoSewa.batas_freeze_fmt || '-')}</strong>
                </p>
            `;
        }

        let janjiHtml = '';
        if (kmr.tanggal_janji_bayar) {
            const ketTambahan = catatanInput !== '' ? ` &mdash; ${escapeHtmlDetail(catatanInput)}` : '';
            janjiHtml = `
                <p class="detail-janji-note">
                    <i class="fa-solid fa-handshake"></i> <strong>Pengajuan Janji Bayar:</strong> ${escapeHtmlDetail(formatTanggalIndoShort(kmr.tanggal_janji_bayar))}${ketTambahan}
                </p>
            `;
        } else if (isDitolak) {
            janjiHtml = `
                <p class="detail-janji-note">
                    <i class="fa-solid fa-circle-xmark"></i> <strong>Status Sebelumnya:</strong> ${escapeHtmlDetail(catatanRaw)}
                </p>
            `;
        }

        let tolakHtml = '';
        if (isFreeze && kmr.tanggal_janji_bayar) {
            tolakHtml = `
                <input type="text" name="alasan_tolak_janji"
                       placeholder="Alasan penolakan (contoh: Maksimal tanggal 19 Nov)..."
                       class="detail-freeze-text-input">
                <button type="submit" name="tolak_janji_bayar" value="1"
                        onclick="ubahIkonKunciDetail(this, 'Menolak...')"
                        class="btn-detail-pill btn-detail-hapus">
                    <i class="fa-solid fa-xmark"></i>
                    <span>Tolak Pengajuan Janji Bayar</span>
                </button>
            `;
        }

        freezeWrap.innerHTML = `
            <div class="detail-freeze-panel ${panelClass}">
                <div class="detail-freeze-head">
                    <i class="fa-solid ${iconClass}"></i>
                    <span>${escapeHtmlDetail(labelStatus)}</span>
                </div>
                ${summaryHtml}
                ${janjiHtml}
                <form action="?page=${encodeURIComponent(CURRENT_PAGE_KAMAR)}" method="POST" class="detail-freeze-form">
                    <input type="hidden" name="nomor_kamar" class="sync-nomor-kamar" value="${escapeHtmlDetail(kmr.nomor)}">
                    <input type="hidden" name="penghuni_id" value="${parseInt(kmr.penghuni_id || 0, 10)}">

                    <div class="row-input-dual">
                        <div class="detail-form-group flex-1">
                            <label class="detail-freeze-label">Tanggal Janji Bayar</label>
                            <div class="modal-input-icon-wrapper">
                                <i class="fa-regular fa-calendar modal-input-icon"></i>
                                <input type="text" name="tanggal_janji_bayar"
                                       id="detailFreezeTglJanji"
                                       value="${escapeHtmlDetail(kmr.tanggal_janji_bayar || '')}"
                                       placeholder="Pilih tanggal..."
                                       class="detail-freeze-date-input modal-input-with-icon" required readonly>
                            </div>
                        </div>
                        <div class="detail-form-group flex-1">
                            <label class="detail-freeze-label">Keterangan Tunggakan</label>
                            <input type="text" name="catatan_tunggakan"
                                   value="${escapeHtmlDetail(catatanInput)}"
                                   placeholder="Misal: Disetujui bayar tgl 21"
                                   class="detail-freeze-text-input">
                        </div>
                    </div>

                    <button type="submit" name="buka_freeze_penghuni" value="1"
                            onclick="ubahIkonKunciDetail(this, 'Memproses...')"
                            class="btn-detail-unfreeze">
                        <i class="fa-solid ${isFreeze ? 'fa-lock-open' : 'fa-calendar-check'}"></i>
                        <span>${isFreeze ? 'Setujui & Aktifkan Kembali Akun (Unfreeze)' : 'Simpan Perpanjangan Janji Bayar'}</span>
                    </button>
                    ${tolakHtml}
                </form>
            </div>
        `;

        initFlatpickrDetailFreezeJanji(kmr.tanggal_janji_bayar || '');
    }

    function bukaDetailKamarInstan(e, noKamar) {
        if (e) e.preventDefault();
        const keyKamar = String(noKamar ?? '').trim();
        const kmr = DATA_SEMUA_KAMAR[keyKamar];
        if (!kmr) return;

        const isKosong = (kmr.status_kamar === 'kosong' || !kmr.nama);
        const isFreeze = (!isKosong && (kmr.status_akun || '').toLowerCase() === 'freeze');

        // 1. Judul & Badge Status
        const titleEl = document.getElementById('detailModalTitle');
        const badgeEl = document.getElementById('detailModalBadge');
        if (titleEl) titleEl.textContent = 'Detail Kamar ' + kmr.nomor;
        if (badgeEl) {
            badgeEl.className = 'badge-detail-kamar ' + (isKosong ? 'badge-kosong-detail' : (isFreeze ? 'badge-detail-freeze' : 'badge-terisi'));
            badgeEl.textContent = isKosong ? 'Kosong' : (isFreeze ? 'Akun Freeze' : 'Terisi');
        }

        // 2. Sinkronisasi nomor kamar pada seluruh hidden input & modal konfirmasi
        document.querySelectorAll('.sync-nomor-kamar').forEach(inp => inp.value = kmr.nomor);
        const btnIsiKosong = document.getElementById('btnIsiKamarKosong');
        if (btnIsiKosong) {
            btnIsiKosong.setAttribute('onclick', `isiKamarKosongInstan('${String(kmr.nomor).replace(/'/g, "\\'")}')`);
        }

        const blokKosong = document.getElementById('detailBlokKosong');
        const blokTerisi = document.getElementById('formDetailPenghuni');

        if (isKosong) {
            renderPanelFreezeDetail(kmr, false);
            if (blokKosong) blokKosong.style.setProperty('display', 'flex', 'important');
            if (blokTerisi) blokTerisi.style.setProperty('display', 'none', 'important');

            const teksHapusKamar = document.getElementById('teksKonfirmasiHapusKamar');
            if (teksHapusKamar) teksHapusKamar.textContent = 'Kamar ' + kmr.nomor;
            window.idPenghuniAktifDetail = '';
        } else {
            renderPanelFreezeDetail(kmr, isFreeze);
            if (blokKosong) blokKosong.style.setProperty('display', 'none', 'important');
            if (blokTerisi) blokTerisi.style.setProperty('display', 'flex', 'important');

            document.getElementById('detailNama').value        = kmr.nama || '';
            document.getElementById('detailWa').value          = kmr.no_wa || '';
            document.getElementById('detailEmail').value       = kmr.email || '';
            document.getElementById('detailWali').value        = kmr.kontak_wali || '';
            document.getElementById('detailPin').value         = kmr.pin || '123456';
            document.getElementById('detailIsKetuaKos').value  = kmr.is_ketua_kos ? '1' : '0';
            document.getElementById('detailTglMulai').value    = kmr.tgl_mulai || '';
            initFlatpickrDetailTanggalMulai(kmr.tgl_mulai || '');
            document.getElementById('detailTglSelesai').value  = kmr.tgl_selesai_format || '-';
            document.getElementById('detailStatusBayar').value = (kmr.status_bayar === 'lunas') ? 'lunas' : 'belum_bayar';

            const selSkema = document.getElementById('detailSkemaId');
            if (selSkema && kmr.skema_id) {
                selSkema.value = String(kmr.skema_id);
            }

            // Reset file input KTP & perbarui pratinjau
            const fileKtpInput = document.getElementById('detailFotoKtpInput');
            if (fileKtpInput) fileKtpInput.value = '';

            const triggerKtp = document.getElementById('btnPreviewKtpTrigger');
            const labelKtp   = document.getElementById('detailKtpFileName');
            const lbBody     = document.getElementById('lightboxKtpBody');
            const lbTitle    = document.getElementById('lightboxKtpNama');
            if (lbTitle) lbTitle.textContent = kmr.nama || '';

            if (kmr.ktp_url) {
                if (labelKtp) labelKtp.textContent = 'Ganti Foto KTP';
                if (triggerKtp) {
                    triggerKtp.classList.remove('ktp-empty-state');
                    triggerKtp.setAttribute('onclick', 'bukaPopupKtpDirect()');
                    triggerKtp.innerHTML =
                        `<div class="ktp-thumbnail-wrapper"><img src="${escapeHtmlDetail(kmr.ktp_url)}" alt="KTP Thumbnail" class="ktp-thumbnail-img" id="detailKtpThumb"></div>` +
                        `<div class="ktp-preview-info"><span class="ktp-preview-title"><i class="fa-solid fa-id-card"></i> Foto KTP Tersedia</span><span class="ktp-preview-hint">Ketuk untuk memperbesar foto</span></div>` +
                        `<i class="fa-solid fa-magnifying-glass-plus ktp-zoom-icon"></i>`;
                }
                if (lbBody) lbBody.innerHTML = `<img id="imgKtpFull" src="${escapeHtmlDetail(kmr.ktp_url)}" alt="Foto KTP Full" class="lightbox-img">`;
            } else {
                if (labelKtp) labelKtp.textContent = 'Unggah Foto KTP';
                if (triggerKtp) {
                    triggerKtp.classList.add('ktp-empty-state');
                    triggerKtp.removeAttribute('onclick');
                    triggerKtp.innerHTML = `<i class="fa-solid fa-id-card ktp-empty-icon"></i><span>Belum ada foto KTP yang diunggah.</span>`;
                }
                if (lbBody) lbBody.innerHTML = `<p class="lightbox-empty-msg">File foto tidak ditemukan.</p>`;
            }

            // Update teks pada modal Hapus Penghuni & Pindah Kamar
            const namaHapusEl  = document.getElementById('namaPenghuniHapusText');
            const namaPindahEl = document.getElementById('namaPenghuniPindahText');
            const kamarAsalEl  = document.getElementById('nomorKamarAsalText');
            if (namaHapusEl)  namaHapusEl.textContent  = 'menghapus akun ' + (kmr.nama || 'penghuni ini');
            if (namaPindahEl) namaPindahEl.textContent = kmr.nama || '';
            if (kamarAsalEl)  kamarAsalEl.textContent  = 'Kamar ' + kmr.nomor;

            window.idPenghuniAktifDetail = String(kmr.penghuni_id || '');
        }

        // 3. Tampilkan modal secara instan (0 ms) & perbarui URL tanpa reload
        const modal = document.getElementById('modalDetailKamar');
        const card  = modal ? modal.querySelector('.modal-card-detail-kamar') : null;
        if (modal) modal.classList.add('active');
        if (card) card.scrollTop = 0;

        if (window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('modal', 'detail_kamar');
            url.searchParams.set('nomor', kmr.nomor);
            window.history.replaceState({}, '', url);
        }
    }

    function aktifkanLoadingTombol(btnUtama, btnBatal, iconEl, textEl, teksLoading) {
        if (btnUtama) {
            btnUtama.disabled = true;
            btnUtama.style.opacity = '0.8';
            btnUtama.style.cursor = 'not-allowed';
        }
        if (btnBatal) {
            btnBatal.disabled = true;
            btnBatal.style.opacity = '0.5';
            btnBatal.style.cursor = 'not-allowed';
        }
        if (iconEl) {
            iconEl.className = 'fa-solid fa-spinner fa-spin';
        }
        if (textEl) {
            textEl.textContent = teksLoading;
        }
    }

    let sedangHapusPenghuni = false;
    function handleSubmitHapusPenghuni(form) {
        if (sedangHapusPenghuni) return false;
        sedangHapusPenghuni = true;
        aktifkanLoadingTombol(
            document.getElementById('btnYaHapusPenghuni'),
            document.getElementById('btnBatalHapusPenghuni'),
            document.getElementById('iconYaHapusPenghuni'),
            document.getElementById('textYaHapusPenghuni'),
            'Menghapus...'
        );
        return true;
    }

    let sedangHapusKamar = false;
    function handleSubmitHapusKamar(form) {
        if (sedangHapusKamar) return false;
        sedangHapusKamar = true;
        aktifkanLoadingTombol(
            document.getElementById('btnYaHapusKamar'),
            document.getElementById('btnBatalHapusKamar'),
            document.getElementById('iconYaHapusKamar'),
            document.getElementById('textYaHapusKamar'),
            'Menghapus...'
        );
        return true;
    }

    let sedangPindahKamar = false;
    function handleSubmitPindahKamar(form) {
        if (sedangPindahKamar) return false;
        sedangPindahKamar = true;
        aktifkanLoadingTombol(
            document.getElementById('btnYaPindahKamar'),
            document.getElementById('btnBatalPindahKamar'),
            document.getElementById('iconYaPindahKamar'),
            document.getElementById('textYaPindahKamar'),
            'Memindahkan...'
        );
        return true;
    }

    function ubahIkonKunciDetail(btn, teksLoading = 'Memproses...') {
        const form = btn.closest('form');
        if (form && !form.checkValidity()) return;

        const icon = btn.querySelector('i');
        const textSpan = btn.querySelector('span');

        if (icon) {
            icon.className = 'fa-solid fa-spinner fa-spin';
        }
        if (textSpan) {
            textSpan.textContent = teksLoading;
        }

        btn.style.opacity = '0.85';
        btn.style.pointerEvents = 'none';
    }

    function isiKamarKosongInstan(noKamar) {
        tutupSemuaModalInstan();
        const selectKamar = document.getElementById('pilihKamar');
        if (selectKamar) {
            selectKamar.value = noKamar;
        }
        bukaModalInstan(null, 'modalTambahPenghuni', 'tambah_penghuni');
    }

    function bukaModalKonfirmasiHapus() {
        const m = document.getElementById('modalKonfirmasiHapus');
        if (m) m.classList.add('active');
    }
    function tutupModalKonfirmasiHapus() {
        if (sedangHapusPenghuni) return;
        const m = document.getElementById('modalKonfirmasiHapus');
        if (m) m.classList.remove('active');
    }
    function bukaModalKonfirmasiHapusKamar() {
        const m = document.getElementById('modalKonfirmasiHapusKamar');
        if (m) m.classList.add('active');
    }
    function tutupModalKonfirmasiHapusKamar() {
        if (sedangHapusKamar) return;
        const m = document.getElementById('modalKonfirmasiHapusKamar');
        if (m) m.classList.remove('active');
    }
    function bukaModalPindahKamar() {
        const m = document.getElementById('modalPindahKamar');
        if (m) m.classList.add('active');
    }
    function tutupModalPindahKamar() {
        if (sedangPindahKamar) return;
        const m = document.getElementById('modalPindahKamar');
        if (m) m.classList.remove('active');
    }

    function bukaPopupKtpDirect() {
        const modal = document.getElementById('modalLightboxKtp');
        if (modal) modal.classList.add('active');
    }
    function tutupPopupKtp() {
        const modal = document.getElementById('modalLightboxKtp');
        if (modal) modal.classList.remove('active');
    }

    function batalTransferKetua() {
        const modal = document.getElementById('modalKonfirmasiKetua');
        if (modal) modal.classList.remove('active');
    }

    function hitungTanggalSelesaiDetail() {
        const sel = document.getElementById('detailSkemaId');
        if (!sel || sel.selectedIndex === -1) return;

        const opt = sel.options[sel.selectedIndex];
        const tipe = opt.getAttribute('data-tipe') || 'bulan';
        const nilai = parseInt(opt.getAttribute('data-nilai') || '1', 10);
        const tglMulaiVal = document.getElementById('detailTglMulai').value;
        const tglSelesaiInput = document.getElementById('detailTglSelesai');

        if (!tglMulaiVal || !tglSelesaiInput) return;

        const parts = tglMulaiVal.split('-');
        if (parts.length !== 3) return;
        const startDate = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));

        if (tipe === 'hari') {
            startDate.setDate(startDate.getDate() + nilai);
        } else {
            startDate.setMonth(startDate.getMonth() + nilai);
        }

        const d = String(startDate.getDate()).padStart(2, '0');
        const m = String(startDate.getMonth() + 1).padStart(2, '0');
        const y = startDate.getFullYear();

        tglSelesaiInput.value = `${d} / ${m} / ${y}`;
    }

    function previewDetailKtp(input) {
        const labelText  = document.getElementById('detailKtpFileName');
        const previewBox = document.getElementById('btnPreviewKtpTrigger');
        const lbBody     = document.getElementById('lightboxKtpBody');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (!file.type.startsWith('image/')) {
                alert('File harus berupa gambar (JPG, PNG, atau WEBP).');
                input.value = '';
                return;
            }

            if (labelText) labelText.textContent = 'File dipilih: ' + file.name;

            const reader = new FileReader();
            reader.onload = function (e) {
                if (previewBox) {
                    previewBox.classList.remove('ktp-empty-state');
                    previewBox.setAttribute('onclick', 'bukaPopupKtpDirect()');
                    previewBox.innerHTML =
                        '<div class="ktp-thumbnail-wrapper">' +
                            '<img src="' + e.target.result + '" alt="KTP Thumbnail" class="ktp-thumbnail-img" id="detailKtpThumb">' +
                        '</div>' +
                        '<div class="ktp-preview-info">' +
                            '<span class="ktp-preview-title"><i class="fa-solid fa-id-card"></i> Pratinjau KTP Baru</span>' +
                            '<span class="ktp-preview-hint">Klik Simpan Perubahan untuk menyimpan</span>' +
                        '</div>' +
                        '<i class="fa-solid fa-magnifying-glass-plus ktp-zoom-icon"></i>';
                }

                if (lbBody) {
                    lbBody.innerHTML = '<img id="imgKtpFull" src="' + e.target.result + '" alt="Foto KTP Full" class="lightbox-img">';
                }
            };
            reader.readAsDataURL(file);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initFlatpickrDetailTanggalMulai();
        initFlatpickrDetailFreezeJanji();

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('modal') === 'detail_kamar' && urlParams.get('nomor')) {
            bukaDetailKamarInstan(null, urlParams.get('nomor'));
        }

        const formDetail = document.getElementById('formDetailPenghuni');
        const detailIsKetuaKos = document.getElementById('detailIsKetuaKos');
        const modalKetua = document.getElementById('modalKonfirmasiKetua');
        const btnSetujuKetua = document.getElementById('btnSetujuTransferKetua');
        const namaKetuaLamaText = document.getElementById('namaKetuaLamaText');

        const namaKetuaLama = "<?= htmlspecialchars($ketuaAktif['nama'] ?? '') ?>";
        const idKetuaLama = "<?= htmlspecialchars((string)($ketuaAktif['id'] ?? '')) ?>";

        let disetujuiTransfer = false;
        let sedangSimpanDetail = false;

        if (formDetail) {
            formDetail.addEventListener('submit', function(e) {
                if (detailIsKetuaKos && detailIsKetuaKos.value === '1') {
                    if (namaKetuaLama !== '' && idKetuaLama !== window.idPenghuniAktifDetail && !disetujuiTransfer) {
                        e.preventDefault();
                        if (namaKetuaLamaText) namaKetuaLamaText.textContent = namaKetuaLama;
                        if (modalKetua) modalKetua.classList.add('active');
                        return;
                    }
                }

                if (sedangSimpanDetail) {
                    e.preventDefault();
                    return;
                }
                sedangSimpanDetail = true;

                aktifkanLoadingTombol(
                    document.getElementById('btnSimpanDetailPenghuni'),
                    null,
                    document.getElementById('iconSimpanDetail'),
                    document.getElementById('textSimpanDetail'),
                    'Menyimpan...'
                );
            });
        }

        if (btnSetujuKetua && formDetail) {
            btnSetujuKetua.addEventListener('click', function() {
                if (sedangSimpanDetail) return;
                disetujuiTransfer = true;
                sedangSimpanDetail = true;

                aktifkanLoadingTombol(
                    btnSetujuKetua,
                    document.getElementById('btnBatalTransferKetua'),
                    document.getElementById('iconSetujuKetua'),
                    document.getElementById('textSetujuKetua'),
                    'Memindahkan...'
                );
                aktifkanLoadingTombol(
                    document.getElementById('btnSimpanDetailPenghuni'),
                    null,
                    document.getElementById('iconSimpanDetail'),
                    document.getElementById('textSimpanDetail'),
                    'Menyimpan...'
                );

                formDetail.submit();
            });
        }

        const waDetail = document.getElementById('detailWa');
        const waliDetail = document.getElementById('detailWali');
        const pinDetail = document.getElementById('detailPin');

        function filterHanyaAngka() {
            this.value = this.value.replace(/[^0-9]/g, '');
        }

        if (waDetail) waDetail.addEventListener('input', filterHanyaAngka);
        if (waliDetail) waliDetail.addEventListener('input', filterHanyaAngka);
        if (pinDetail) pinDetail.addEventListener('input', filterHanyaAngka);
    });
</script>