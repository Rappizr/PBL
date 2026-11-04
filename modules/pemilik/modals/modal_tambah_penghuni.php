<?php
$modal_aktif  = $modal_aktif ?? ($_GET['modal'] ?? '');
$current_page = $current_page ?? ($_GET['page'] ?? 'manajemen_kamar');
$daftar_kamar = $daftar_kamar ?? [];
$list_skema   = $list_skema ?? [];
?>
<?php if ($modal_aktif === 'tambah_penghuni'): ?>
    <div class="modal-overlay active">
        <div class="modal-card-penghuni-custom modal-size-md">
            <h3 class="modal-penghuni-title">Tambah Penghuni</h3>
            
            <form action="?page=<?= htmlspecialchars($current_page) ?>" method="POST" enctype="multipart/form-data" class="form-penghuni-custom">
                
                <div class="field-group-custom">
                    <label class="label-custom" for="namaLengkap">Nama Lengkap</label>
                    <input type="text" id="namaLengkap" name="nama" class="input-custom-pill" placeholder="Masukkan nama lengkap" required>
                </div>

                <div class="field-group-custom">
                    <label class="label-custom" for="pilihKamar">Pilih Kamar</label>
                    <select id="pilihKamar" name="kamar_nomor" class="select-custom-pill" required>
                        <option value="">-- Pilih Kamar Kosong --</option>
                        <?php foreach ($daftar_kamar as $kamar): ?>
                            <?php if ($kamar['status_kamar'] === 'kosong' || empty($kamar['nama_penghuni'])): ?>
                                <option value="<?= htmlspecialchars($kamar['no_kamar']) ?>" <?= (isset($_GET['kamar']) && $_GET['kamar'] === $kamar['no_kamar']) ? 'selected' : ''; ?>>
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
                        <input type="tel" id="nomorHp" name="no_wa" class="input-custom-pill" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="field-group-custom flex-1">
                        <label class="label-custom" for="emailPenghuni">Email</label>
                        <input type="email" id="emailPenghuni" name="email" class="input-custom-pill" placeholder="nama@email.com">
                    </div>
                </div>

                <!-- Kontak Wali & PIN Akun -->
                <div class="row-input-dual">
                    <div class="field-group-custom flex-1">
                        <label class="label-custom" for="kontakWali">No. Kontak Wali</label>
                        <input type="tel" id="kontakWali" name="kontak_wali" class="input-custom-pill" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="field-group-custom flex-1">
                        <label class="label-custom" for="pinAkun">PIN Akun (Login)</label>
                        <input type="text" id="pinAkun" name="pin" class="input-custom-pill" value="123456" maxlength="6" placeholder="Default: 123456" required>
                    </div>
                </div>

                <!-- Status Peran: Ketua Kost -->
                <div class="field-group-custom">
                    <label class="label-custom" for="isKetuaKos">Peran Penghuni</label>
                    <select id="isKetuaKos" name="is_ketua_kos" class="select-custom-pill">
                        <option value="0" selected>Penghuni Biasa</option>
                        <option value="1">Ketua Kost (Akses Tambahan)</option>
                    </select>
                </div>

                <!-- Pilihan Skema Sewa -->
                <div class="field-group-custom">
                    <label class="label-custom" for="durasiSewaDinamis">Pilihan Durasi Sewa</label>
                    <select id="durasiSewaDinamis" name="skema_id" class="select-custom-pill" required onchange="hitungTanggalSelesaiDinamis()">
                        <option value="" disabled selected>-- Pilih Skema Sewa --</option>
                        <?php foreach ($list_skema as $skm): ?>
                            <option value="<?= $skm['id']; ?>" data-tipe="<?= htmlspecialchars($skm['tipe_periode']); ?>" data-nilai="<?= (int)$skm['durasi_nilai']; ?>">
                                <?= htmlspecialchars($skm['nama_skema']); ?> (<?= (int)$skm['durasi_nilai']; ?> <?= htmlspecialchars($skm['tipe_periode']); ?> - Rp <?= number_format((float)($skm['harga_default'] ?? 0), 0, ',', '.'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tanggal Mulai & Tanggal Selesai -->
                <div class="row-tanggal-custom">
                    <div class="field-group-custom flex-1">
                        <label class="label-custom" for="tglMulai">Tanggal Mulai</label>
                        <div class="input-date-wrapper">
                            <input type="date" id="tglMulai" name="tgl_mulai" class="input-custom-pill date-input" value="<?= date('Y-m-d'); ?>" required onchange="hitungTanggalSelesaiDinamis()">
                        </div>
                    </div>

                    <div class="field-group-custom flex-1">
                        <label class="label-custom" for="tglSelesai">Tanggal Selesai</label>
                        <div class="input-date-wrapper">
                            <input type="text" id="tglSelesai" name="tgl_selesai" class="input-custom-pill date-input input-readonly-dashed" placeholder="dd / mm / yyyy" readonly required>
                        </div>
                    </div>
                </div>

                <!-- Foto KTP -->
                <div class="field-group-custom">
                    <label class="label-custom">Foto KTP</label>
                    <label class="upload-area-custom">
                        <input type="file" name="foto_ktp" id="fotoKtpInput" accept="image/*" required onchange="previewUpload(this)">
                        <div class="upload-icon-circle">
                            <i class="fa-solid fa-arrow-up-from-bracket"></i>
                        </div>
                        <p class="upload-text-custom" id="uploadStatusText">Ketuk untuk pilih foto</p>
                    </label>
                </div>

                <div class="actions-custom-row">
                    <a href="?page=<?= htmlspecialchars($current_page) ?>" class="btn-custom-pill btn-batal-custom">Batal</a>
                    <button type="submit" name="simpan_penghuni" class="btn-custom-pill btn-simpan-custom">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function hitungTanggalSelesaiDinamis() {
            const sel = document.getElementById('durasiSewaDinamis');
            const selectedOpt = sel.options[sel.selectedIndex];
            const tipe = selectedOpt.getAttribute('data-tipe') || 'bulan';
            const nilai = parseInt(selectedOpt.getAttribute('data-nilai') || '1');
            const tglMulaiVal = document.getElementById('tglMulai').value;
            const tglSelesaiInput = document.getElementById('tglSelesai');

            if (!tglMulaiVal) {
                tglSelesaiInput.value = '';
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
    </script>
<?php endif; ?>