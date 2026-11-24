<?php
$modal_aktif         = $modal_aktif ?? ($_GET['modal'] ?? '');
$current_page        = $current_page ?? ($_GET['page'] ?? 'manajemen_kamar');
$list_skema          = $list_skema ?? [];
$daftar_kamar_kosong = $daftar_kamar_kosong ?? [];
$ketuaAktif          = $ketuaAktif ?? null;
?>
<div id="modalTambahPenghuni" class="modal-overlay <?= $modal_aktif === 'tambah_penghuni' ? 'active' : ''; ?>" onclick="tutupSemuaModalInstan(event)">
    <div class="modal-card-penghuni-custom modal-size-md" onclick="event.stopPropagation()">
        <h3 class="modal-penghuni-title">Tambah Penghuni</h3>

        <!-- POP-UP ALERT ERROR DINAMIS AJAX -->
        <div id="modalAlertError" class="modal-ajax-error-box">
            <i class="fa-solid fa-circle-exclamation modal-ajax-error-icon"></i>
            <span id="modalAlertText"></span>
        </div>

<form action="?page=<?= htmlspecialchars($current_page) ?>" method="POST" enctype="multipart/form-data"
            class="form-penghuni-custom" id="formTambahPenghuni" novalidate>

            <!-- Nama Lengkap -->
            <div class="field-group-custom">
                <label class="label-custom" for="namaLengkap">Nama Lengkap</label>
                <input type="text" id="namaLengkap" name="nama" class="input-custom-pill input-capitalize"
                    placeholder="Masukkan nama lengkap" pattern="[a-zA-Z\s'\.]+"
                    title="Nama hanya boleh berupa huruf dan spasi" required>
            </div>

            <!-- Pilih Kamar -->
            <div class="field-group-custom">
                <label class="label-custom" for="pilihKamar">Pilih Kamar</label>
                <select id="pilihKamar" name="kamar_nomor" class="select-custom-pill" required>
                    <option value="">-- Pilih Kamar Kosong --</option>
                    <?php foreach ($daftar_kamar_kosong as $kamar): ?>
                    <option value="<?= htmlspecialchars($kamar['no_kamar']) ?>"
                        <?= (isset($_GET['kamar']) && $_GET['kamar'] === $kamar['no_kamar']) ? 'selected' : ''; ?>>
                        Kamar <?= htmlspecialchars($kamar['no_kamar']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Nomor HP & Email -->
            <div class="row-input-dual">
                <div class="field-group-custom flex-1">
                    <label class="label-custom" for="nomorHp">No. HP / WhatsApp</label>
                    <input type="tel" id="nomorHp" name="no_wa" class="input-custom-pill" placeholder="08xxxxxxxxxx"
                        pattern="08[0-9]{8,13}" maxlength="15"
                        title="Nomor WA harus diawali 08 dan berjumlah 10-15 digit" required>
                </div>
                <div class="field-group-custom flex-1">
                    <label class="label-custom" for="emailPenghuni">Email</label>
                    <input type="email" id="emailPenghuni" name="email" class="input-custom-pill"
                        placeholder="nama@email.com">
                </div>
            </div>

            <!-- Kontak Wali & PIN Akun -->
            <div class="row-input-dual">
                <div class="field-group-custom flex-1">
                    <label class="label-custom" for="kontakWali">No. Kontak Wali (Opsional)</label>
                    <input type="tel" id="kontakWali" name="kontak_wali" class="input-custom-pill"
                        placeholder="08xxxxxxxxxx (Opsional)" pattern="08[0-9]{8,13}" maxlength="15"
                        title="Jika diisi, kontak wali harus diawali 08 dan berjumlah 10-15 digit">
                </div>
                <div class="field-group-custom flex-1">
                    <label class="label-custom" for="pinAkun">PIN Akun (Login)</label>
                    <input type="text" id="pinAkun" name="pin" class="input-custom-pill" value="123456" maxlength="6"
                        pattern="[0-9]{6}" title="PIN harus tepat 6 digit angka" placeholder="Default: 123456" required>
                </div>
            </div>

            <!-- Peran Penghuni -->
            <div class="field-group-custom">
                <label class="label-custom" for="isKetuaKos">Peran Penghuni</label>
                <select id="isKetuaKos" name="is_ketua_kos" class="select-custom-pill">
                    <option value="0" selected>Penghuni Biasa</option>
                    <?php if ($ketuaAktif): ?>
                    <option value="1" disabled class="option-disabled-muted">
                        Ketua Kost (Dijabat oleh: <?= htmlspecialchars($ketuaAktif['nama']); ?>)
                    </option>
                    <?php else: ?>
                    <option value="1">Ketua Kost (Akses Tambahan)</option>
                    <?php endif; ?>
                </select>
                <?php if ($ketuaAktif): ?>
                <small class="ketua-hint-muted">
                    * Pergantian Ketua Kost dilakukan lewat menu edit detail penghuni.
                </small>
                <?php endif; ?>
            </div>

            <!-- Pilihan Skema Sewa -->
            <div class="field-group-custom">
                <label class="label-custom" for="durasiSewaDinamis">Pilihan Durasi Sewa</label>
                <select id="durasiSewaDinamis" name="skema_id" class="select-custom-pill" required
                    onchange="hitungTanggalSelesaiDinamis()">
                    <option value="" disabled selected>-- Pilih Skema Sewa --</option>
                    <?php foreach ($list_skema as $skm): ?>
                    <option value="<?= $skm['id']; ?>" data-tipe="<?= htmlspecialchars($skm['tipe_periode']); ?>"
                        data-nilai="<?= (int)$skm['durasi_nilai']; ?>">
                        <?= htmlspecialchars($skm['nama_skema']); ?> (<?= (int)$skm['durasi_nilai']; ?>
                        <?= htmlspecialchars($skm['tipe_periode']); ?> - Rp
                        <?= number_format((float)($skm['nominal'] ?? 0), 0, ',', '.'); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tanggal Mulai & Tanggal Selesai -->
            <div class="row-tanggal-custom">
                <div class="field-group-custom flex-1">
                    <label class="label-custom" for="tglMulai">Tanggal Mulai</label>
                    <div class="input-date-wrapper">
                        <input type="date" id="tglMulai" name="tgl_mulai" class="input-custom-pill date-input"
                            value="<?= date('Y-m-d'); ?>" required onchange="hitungTanggalSelesaiDinamis()">
                    </div>
                </div>

                <div class="field-group-custom flex-1">
                    <label class="label-custom" for="tglSelesai">Tanggal Selesai</label>
                    <div class="input-date-wrapper">
                        <input type="text" id="tglSelesai" name="tgl_selesai"
                            class="input-custom-pill date-input input-readonly-dashed" placeholder="dd / mm / yyyy"
                            readonly required>
                    </div>
                </div>
            </div>

            <!-- Foto KTP (Wajib) -->
            <div class="field-group-custom">
                <label class="label-custom" for="fotoKtpInput">Foto KTP (Wajib)</label>
                <label class="upload-area-custom" id="uploadAreaKtp">
                    <input type="file" name="foto_ktp" id="fotoKtpInput" accept="image/jpeg,image/png,image/webp" required
                        onchange="previewUpload(this)">
                    <div id="placeholderKtpContent" class="placeholder-bukti-content">
                        <div class="upload-icon-circle">
                            <i class="fa-solid fa-arrow-up-from-bracket"></i>
                        </div>
                        <p class="upload-text-custom" id="uploadStatusText">Ketuk untuk pilih foto KTP</p>
                    </div>
                    <img id="previewKtpImg" src="" alt="Preview KTP" class="preview-bukti-img">
                </label>
            </div>

            <div class="actions-custom-row">
                <button type="button" onclick="tutupSemuaModalInstan(event)" class="btn-custom-pill btn-batal-custom" id="btnBatalModal">Batal</button>
                <button type="submit" name="simpan_penghuni" class="btn-custom-pill btn-simpan-custom" id="btnSimpanModal">
                    <span id="btnSimpanText">Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function hitungTanggalSelesaiDinamis() {
    const sel = document.getElementById('durasiSewaDinamis');
    if (!sel || sel.selectedIndex === -1) return;

    const selectedOpt = sel.options[sel.selectedIndex];
    const tipe = selectedOpt.getAttribute('data-tipe') || 'bulan';
    const nilai = parseInt(selectedOpt.getAttribute('data-nilai') || '1', 10);
    const tglMulaiVal = document.getElementById('tglMulai').value;
    const tglSelesaiInput = document.getElementById('tglSelesai');

    if (!tglMulaiVal || !tglSelesaiInput) {
        if (tglSelesaiInput) tglSelesaiInput.value = '';
        return;
    }

    const startDate = new Date(tglMulaiVal);
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

function previewUpload(input) {
    const placeholder = document.getElementById('placeholderKtpContent');
    const previewImg  = document.getElementById('previewKtpImg');
    const uploadArea  = document.getElementById('uploadAreaKtp');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (!file.type.startsWith('image/')) {
            alert('File harus berupa gambar (JPG, PNG, atau WEBP).');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';
            if (uploadArea) uploadArea.classList.add('has-preview');
        };
        reader.readAsDataURL(file);
    } else {
        if (previewImg) {
            previewImg.src = '';
            previewImg.style.display = 'none';
        }
        if (placeholder) placeholder.style.display = 'flex';
        if (uploadArea) uploadArea.classList.remove('has-preview');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const formTambah = document.getElementById('formTambahPenghuni');
    const btnSimpan = document.getElementById('btnSimpanModal');
    const btnSimpanText = document.getElementById('btnSimpanText');
    const btnBatal = document.getElementById('btnBatalModal');
    const alertBox = document.getElementById('modalAlertError');
    const alertText = document.getElementById('modalAlertText');

    const namaInput = document.getElementById('namaLengkap');
    const waInput = document.getElementById('nomorHp');
    const waliInput = document.getElementById('kontakWali');
    const pinInput = document.getElementById('pinAkun');

    hitungTanggalSelesaiDinamis();

function tampilkanErrorModalPenghuni(pesan, fokusEl = null) {
        if (alertText && alertBox) {
            alertText.textContent = pesan;
            alertBox.classList.add('show-flex');
            const modalCard = alertBox.closest('.modal-card-penghuni-custom');
            if (modalCard) {
                modalCard.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
        if (fokusEl && typeof fokusEl.focus === 'function') {
            fokusEl.focus();
        }
    }

    if (formTambah) {
        formTambah.addEventListener('submit', function(e) {
            e.preventDefault();

            const kamarEl    = document.getElementById('pilihKamar');
            const emailEl    = document.getElementById('emailPenghuni');
            const skemaEl    = document.getElementById('durasiSewaDinamis');
            const tglMulaiEl = document.getElementById('tglMulai');
            const fotoKtpEl  = document.getElementById('fotoKtpInput');

            const namaVal    = (namaInput?.value || '').trim();
            const kamarVal   = (kamarEl?.value || '').trim();
            const waVal      = (waInput?.value || '').trim();
            const emailVal   = (emailEl?.value || '').trim();
            const waliVal    = (waliInput?.value || '').trim();
            const pinVal     = (pinInput?.value || '').trim();
            const skemaVal   = (skemaEl?.value || '').trim();
            const tglVal     = (tglMulaiEl?.value || '').trim();

            if (!namaVal) {
                tampilkanErrorModalPenghuni('Nama lengkap wajib diisi.', namaInput);
                return;
            }
            if (!kamarVal) {
                tampilkanErrorModalPenghuni('Nomor kamar wajib dipilih.', kamarEl);
                return;
            }
            if (!waVal) {
                tampilkanErrorModalPenghuni('No. HP / WhatsApp wajib diisi.', waInput);
                return;
            }
            if (!/^08[0-9]{8,13}$/.test(waVal)) {
                tampilkanErrorModalPenghuni('Nomor WA harus diawali 08 dan berjumlah 10–15 digit.', waInput);
                return;
            }
            if (emailVal !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
                tampilkanErrorModalPenghuni('Format alamat email tidak valid.', emailEl);
                return;
            }
            if (waliVal !== '' && !/^08[0-9]{8,13}$/.test(waliVal)) {
                tampilkanErrorModalPenghuni('Nomor Kontak Wali harus diawali 08 dan berjumlah 10–15 digit.', waliInput);
                return;
            }
            if (!/^[0-9]{6}$/.test(pinVal)) {
                tampilkanErrorModalPenghuni('PIN Akun wajib diisi 6 digit angka.', pinInput);
                return;
            }
            if (!skemaVal) {
                tampilkanErrorModalPenghuni('Pilihan durasi sewa wajib dipilih.', skemaEl);
                return;
            }
            if (!tglVal) {
                tampilkanErrorModalPenghuni('Tanggal mulai sewa wajib diisi.', tglMulaiEl);
                return;
            }
            if (!fotoKtpEl || !fotoKtpEl.files || fotoKtpEl.files.length === 0) {
                tampilkanErrorModalPenghuni('Foto KTP wajib diunggah terlebih dahulu.');
                return;
            }

            if (alertBox) alertBox.classList.remove('show-flex');

            if (btnSimpan) {
                btnSimpan.disabled = true;
                btnSimpan.classList.add('kamar-btn-disabled');
            }
            if (btnBatal) {
                btnBatal.disabled = true;
                btnBatal.classList.add('kamar-btn-disabled');
            }
            if (btnSimpanText) {
                btnSimpanText.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin spinner-gap-right"></i>Menyimpan...';
            }

            const formData = new FormData(formTambah);
            formData.append('simpan_penghuni', '1');

            fetch(formTambah.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = '?page=<?= urlencode($current_page) ?>';
                    } else {
                        tampilkanErrorModalPenghuni(data.message || 'Terjadi kesalahan sistem.');
                        if (btnSimpan) {
                            btnSimpan.disabled = false;
                            btnSimpan.classList.remove('kamar-btn-disabled');
                        }
                        if (btnBatal) {
                            btnBatal.disabled = false;
                            btnBatal.classList.remove('kamar-btn-disabled');
                        }
                        if (btnSimpanText) btnSimpanText.textContent = 'Simpan';
                    }
                })
                .catch(() => {
                    tampilkanErrorModalPenghuni('Gagal terhubung ke server. Silakan coba lagi.');
                    if (btnSimpan) {
                        btnSimpan.disabled = false;
                        btnSimpan.classList.remove('kamar-btn-disabled');
                    }
                    if (btnBatal) {
                        btnBatal.disabled = false;
                        btnBatal.classList.remove('kamar-btn-disabled');
                    }
                    if (btnSimpanText) btnSimpanText.textContent = 'Simpan';
                });
        });
    }

    if (namaInput) {
        namaInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z\s'\.]/g, '');
        });
    }

    function filterNomorTelepon() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length >= 1 && !this.value.startsWith('0')) {
            this.value = '0' + this.value.replace(/^0+/, '');
        }
    }

    if (waInput) waInput.addEventListener('input', filterNomorTelepon);
    if (waliInput) waliInput.addEventListener('input', filterNomorTelepon);

    if (pinInput) {
        pinInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    }
});
</script>