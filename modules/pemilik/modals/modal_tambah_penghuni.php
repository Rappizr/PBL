<?php
/** @var PDO $pdo */
$modal_aktif  = $modal_aktif ?? ($_GET['modal'] ?? '');
$current_page = $current_page ?? ($_GET['page'] ?? 'manajemen_kamar');
$daftar_kamar = $daftar_kamar ?? [];
$list_skema   = $list_skema ?? [];

// Ambil data Ketua Kost aktif dari database jika ada
$ketuaAktif = null;
$daftar_kamar_kosong = [];
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmtCekKetua = $pdo->query("SELECT id, nama FROM penghuni WHERE is_ketua_kos = true LIMIT 1");
        $ketuaAktif = $stmtCekKetua ? $stmtCekKetua->fetch(PDO::FETCH_ASSOC) : null;

        $stmtKosong = $pdo->query("
            SELECT k.no_kamar
            FROM kamar k
            LEFT JOIN penghuni_kamar pk ON k.id = pk.kamar_id
            WHERE k.status_kamar = 'kosong' OR pk.id IS NULL
            ORDER BY k.no_kamar::INTEGER ASC
        ");
        $daftar_kamar_kosong = $stmtKosong ? $stmtKosong->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Exception $e) {}
}
?>

<?php if ($modal_aktif === 'tambah_penghuni'): ?>
<div class="modal-overlay active">
    <div class="modal-card-penghuni-custom modal-size-md">
        <h3 class="modal-penghuni-title">Tambah Penghuni</h3>

        <!-- POP-UP ALERT ERROR DINAMIS AJAX -->
        <div id="modalAlertError"
            style="display: none; background-color: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 14px; font-weight: 500; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(248, 113, 113, 0.15);">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 18px; color: #dc2626;"></i>
            <span id="modalAlertText"></span>
        </div>

        <form action="?page=<?= htmlspecialchars($current_page) ?>" method="POST" enctype="multipart/form-data"
            class="form-penghuni-custom" id="formTambahPenghuni">

            <!-- Nama Lengkap -->
            <div class="field-group-custom">
                <label class="label-custom" for="namaLengkap">Nama Lengkap</label>
                <input type="text" id="namaLengkap" name="nama" class="input-custom-pill"
                    placeholder="Masukkan nama lengkap" style="text-transform: capitalize;" pattern="[a-zA-Z\s'\.]+"
                    title="Nama hanya boleh berupa huruf dan spasi" required>
            </div>

            <!-- Pilih Kamar -->
            <div class="field-group-custom">
                <label class="label-custom" for="pilihKamar">Pilih Kamar</label>
                <select id="pilihKamar" name="kamar_nomor" class="select-custom-pill" required>
                    <option value="">-- Pilih Kamar Kosong --</option>
                    <?php foreach ($daftar_kamar_kosong as $kamar): ?>
                    <?php if ($kamar['status_kamar'] === 'kosong' || empty($kamar['nama_penghuni'])): ?>
                    <option value="<?= htmlspecialchars($kamar['no_kamar']) ?>"
                        <?= (isset($_GET['kamar']) && $_GET['kamar'] === $kamar['no_kamar']) ? 'selected' : ''; ?>>
                        Kamar <?= htmlspecialchars($kamar['no_kamar']) ?>
                    </option>
                    <?php endif; ?>
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
                    <label class="label-custom" for="kontakWali">No. Kontak Wali</label>
                    <input type="tel" id="kontakWali" name="kontak_wali" class="input-custom-pill"
                        placeholder="08xxxxxxxxxx" pattern="08[0-9]{8,13}" maxlength="15"
                        title="Kontak wali harus diawali 08 dan berjumlah 10-15 digit" required>
                </div>
                <div class="field-group-custom flex-1">
                    <label class="label-custom" for="pinAkun">PIN Akun (Login)</label>
                    <input type="text" id="pinAkun" name="pin" class="input-custom-pill" value="123456" maxlength="6"
                        pattern="[0-9]{6}" title="PIN harus tepat 6 digit angka" placeholder="Default: 123456" required>
                </div>
            </div>

            <!-- Peran Penghuni (Kunci jika Ketua Kos sudah ada) -->
            <div class="field-group-custom">
                <label class="label-custom" for="isKetuaKos">Peran Penghuni</label>
                <select id="isKetuaKos" name="is_ketua_kos" class="select-custom-pill">
                    <option value="0" selected>Penghuni Biasa</option>
                    <?php if ($ketuaAktif): ?>
                    <option value="1" disabled style="color: #9ca3af; background-color: #f3f4f6;">
                        Ketua Kost (Dijabat oleh: <?= htmlspecialchars($ketuaAktif['nama']); ?>)
                    </option>
                    <?php else: ?>
                    <option value="1">Ketua Kost (Akses Tambahan)</option>
                    <?php endif; ?>
                </select>
                <?php if ($ketuaAktif): ?>
                <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
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

            <!-- Foto KTP -->
            <div class="field-group-custom">
                <label class="label-custom">Foto KTP</label>
                <label class="upload-area-custom">
                    <input type="file" name="foto_ktp" id="fotoKtpInput" accept="image/*" required
                        onchange="previewUpload(this)">
                    <div class="upload-icon-circle">
                        <i class="fa-solid fa-arrow-up-from-bracket"></i>
                    </div>
                    <p class="upload-text-custom" id="uploadStatusText">Ketuk untuk pilih foto</p>
                </label>
            </div>

            <div class="actions-custom-row">
                <a href="?page=<?= htmlspecialchars($current_page) ?>" class="btn-custom-pill btn-batal-custom"
                    id="btnBatalModal">Batal</a>
                <button type="submit" name="simpan_penghuni" class="btn-custom-pill btn-simpan-custom"
                    id="btnSimpanModal">
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
    const text = document.getElementById('uploadStatusText');
    if (input.files && input.files[0]) {
        text.textContent = input.files[0].name;
    } else {
        text.textContent = 'Ketuk untuk pilih foto';
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

    if (formTambah) {
        formTambah.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!formTambah.checkValidity()) {
                return;
            }

            if (alertBox) alertBox.style.display = 'none';

            if (btnSimpan) {
                btnSimpan.disabled = true;
                btnSimpan.style.opacity = '0.75';
                btnSimpan.style.cursor = 'not-allowed';
            }
            if (btnBatal) {
                btnBatal.style.pointerEvents = 'none';
                btnBatal.style.opacity = '0.5';
            }
            if (btnSimpanText) {
                btnSimpanText.innerHTML =
                    '<i class="fa-solid fa-circle-notch fa-spin" style="margin-right: 8px;"></i>Menyimpan...';
            }

            const formData = new FormData(formTambah);
            formData.append('simpan_penghuni', '1');

            fetch(formTambah.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = '?page=<?= urlencode($current_page) ?>';
                    } else {
                        if (alertText && alertBox) {
                            alertText.textContent = data.message || 'Terjadi kesalahan sistem.';
                            alertBox.style.display = 'flex';
                        }
                        if (btnSimpan) {
                            btnSimpan.disabled = false;
                            btnSimpan.style.opacity = '1';
                            btnSimpan.style.cursor = 'pointer';
                        }
                        if (btnBatal) {
                            btnBatal.style.pointerEvents = 'auto';
                            btnBatal.style.opacity = '1';
                        }
                        if (btnSimpanText) btnSimpanText.textContent = 'Simpan';
                    }
                })
                .catch(err => {
                    if (alertText && alertBox) {
                        alertText.textContent = 'Gagal terhubung ke server. Silakan coba lagi.';
                        alertBox.style.display = 'flex';
                    }
                    if (btnSimpan) {
                        btnSimpan.disabled = false;
                        btnSimpan.style.opacity = '1';
                        btnSimpan.style.cursor = 'pointer';
                    }
                    if (btnBatal) {
                        btnBatal.style.pointerEvents = 'auto';
                        btnBatal.style.opacity = '1';
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
<?php endif; ?>