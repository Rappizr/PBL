<?php
$modal_aktif  = $modal_aktif ?? ($_GET['modal'] ?? '');
$current_page = $current_page ?? ($_GET['page'] ?? 'manajemen_kamar');
$list_skema   = $list_skema ?? [];

$pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
unset($_SESSION['pesan_sukses']);
?>
<?php if ($modal_aktif === 'kelola_skema'): ?>
    <div class="modal-overlay active">
        <div class="modal-card-penghuni-custom modal-skema-container">
            <h3 class="modal-penghuni-title">Kelola Skema Sewa</h3>
            <p class="modal-skema-sub">
                Tambahkan atau ubah format sewa kost Anda (misal: Bulanan, Harian, 3 Bulan, atau Tahunan).
            </p>

            <?php if (!empty($pesan_sukses)): ?>
                <div class="notif-sukses-kamar">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= htmlspecialchars($pesan_sukses); ?></span>
                </div>
            <?php endif; ?>

            <form action="?page=<?= htmlspecialchars($current_page) ?>" method="POST" class="skema-form-card" id="formKelolaSkema">
                <input type="hidden" name="id_skema" id="skemaFormId" value="0">

                <span class="skema-form-heading" id="skemaFormHeading">+ Tambah Skema Baru</span>
                
                <div class="field-group-custom skema-group-sm">
                    <label class="label-custom skema-label-sm">Nama Skema</label>
                    <input type="text" name="nama_skema" id="skemaFormNama" class="input-custom-pill skema-input-sm" placeholder="Contoh: Sewa Harian / 3 Bulan" required>
                </div>

                <div class="skema-grid-row">
                    <div class="field-group-custom skema-group-sm">
                        <label class="label-custom skema-label-sm">Jenis Periode</label>
                        <select name="tipe_periode" id="pilihTipePeriode" class="select-custom-pill skema-select-sm" onchange="sesuaikanLabelDurasi()">
                            <option value="bulan">Bulan</option>
                            <option value="hari">Hari</option>
                        </select>
                    </div>
                    <div class="field-group-custom skema-group-sm">
                        <label class="label-custom skema-label-sm" id="labelDurasiNilai">Berapa Bulan</label>
                        <input type="text" name="durasi_nilai" id="skemaFormDurasi" class="input-custom-pill skema-input-sm" 
                               placeholder="Contoh: 1 / 3 / 12" inputmode="numeric" maxlength="4" value="1" required>
                    </div>
                </div>

                <div class="field-group-custom skema-group-sm">
                    <label class="label-custom skema-label-sm">Harga Sewa (Rp)</label>
                    <div class="kamar-input-rp-box">
                        <span class="kamar-rp-label">Rp</span>
                        <input type="text" name="nominal" id="skemaFormHarga" class="kamar-rp-field" placeholder="Contoh: 5.000.000" required oninput="formatRupiahInput(this)">
                    </div>
                </div>

                <button type="submit" name="simpan_skema_baru" id="skemaBtnSubmit" class="btn-custom-pill btn-simpan-custom btn-skema-submit">
                    <i class="fa-solid fa-plus"></i> Simpan Skema
                </button>
                <button type="button" id="skemaBtnBatalEdit" class="btn-batal-edit-skema" onclick="resetFormSkema()">
                    Batal Edit
                </button>
            </form>

            <span class="skema-list-label">Skema yang Tersedia:</span>
            <div class="skema-scroll-list">
                <?php if (empty($list_skema)): ?>
                    <div class="kamar-empty-box">Belum ada skema yang dibuat.</div>
                <?php else: ?>
                    <?php foreach ($list_skema as $skm): ?>
                        <div class="skema-item-box">
                            <div>
                                <div class="skema-item-name"><?= htmlspecialchars($skm['nama_skema']); ?></div>
                                <div class="skema-item-detail">
                                    Durasi: <?= (int)$skm['durasi_nilai']; ?> <?= htmlspecialchars($skm['tipe_periode']); ?>
                                    &bull; <strong>Rp <?= number_format((float)($skm['nominal'] ?? 0), 0, ',', '.'); ?></strong>
                                </div>
                            </div>
                            <div class="skema-item-actions">
                                <button type="button" class="btn-edit-skema" title="Edit Skema" onclick="isiFormEditSkema(<?= (int)$skm['id']; ?>, '<?= htmlspecialchars(addslashes($skm['nama_skema'])); ?>', '<?= htmlspecialchars($skm['tipe_periode']); ?>', <?= (int)$skm['durasi_nilai']; ?>, <?= (float)($skm['nominal'] ?? 0); ?>)">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn-trash-skema" title="Hapus Skema" onclick="bukaModalHapusSkema(<?= (int)$skm['id']; ?>, '<?= htmlspecialchars(addslashes($skm['nama_skema'])); ?>')">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="actions-custom-row">
                <a href="?page=<?= htmlspecialchars($current_page) ?>" class="btn-custom-pill btn-batal-custom btn-tutup-skema">Tutup</a>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS SKEMA KUSTOM -->
    <div id="modalKonfirmasiHapusSkema" class="modal-overlay modal-confirm-skema">
        <div class="modal-card-konfirmasi-kamar">
            <div class="konfirmasi-icon-wrapper">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="konfirmasi-title">Hapus Skema?</h3>
            <p class="konfirmasi-desc">
                Apakah Anda yakin ingin menghapus skema <strong id="namaSkemaDihapus">-</strong>?
            </p>

            <form action="?page=<?= htmlspecialchars($current_page); ?>" method="POST">
                <input type="hidden" name="id_skema" id="inputIdSkemaHapus" value="">
                <div class="konfirmasi-actions">
                    <button type="button" class="btn-konfirmasi-batal" onclick="tutupModalHapusSkema()">Batal</button>
                    <button type="submit" name="hapus_skema_item" class="btn-konfirmasi-hapus">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function formatRupiahInput(input) {
            let value = input.value.replace(/\D/g, '');
            input.value = value ? new Intl.NumberFormat('id-ID').format(value) : '';
        }

        function formatAngkaTitik(angka) {
            if (!angka || isNaN(angka)) return '';
            return new Intl.NumberFormat('id-ID').format(angka);
        }

        function sesuaikanLabelDurasi() {
            const select = document.getElementById('pilihTipePeriode');
            const label = document.getElementById('labelDurasiNilai');
            if (select && label) {
                label.textContent = select.value === 'hari' ? 'Berapa Hari' : 'Berapa Bulan';
            }
        }

        function isiFormEditSkema(id, nama, tipe, durasi, harga) {
            document.getElementById('skemaFormId').value = id;
            document.getElementById('skemaFormNama').value = nama;
            document.getElementById('pilihTipePeriode').value = tipe;
            document.getElementById('skemaFormDurasi').value = durasi;
            document.getElementById('skemaFormHarga').value = formatAngkaTitik(harga);

            sesuaikanLabelDurasi();

            document.getElementById('skemaFormHeading').textContent = '✏️ Edit Skema: ' + nama;
            document.getElementById('skemaBtnSubmit').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Update Skema';
            document.getElementById('skemaBtnBatalEdit').style.display = 'block';

            document.getElementById('skemaFormNama').focus();
        }

        function resetFormSkema() {
            document.getElementById('skemaFormId').value = '0';
            document.getElementById('skemaFormNama').value = '';
            document.getElementById('pilihTipePeriode').value = 'bulan';
            document.getElementById('skemaFormDurasi').value = '1';
            document.getElementById('skemaFormHarga').value = '';

            sesuaikanLabelDurasi();

            document.getElementById('skemaFormHeading').textContent = '+ Tambah Skema Baru';
            document.getElementById('skemaBtnSubmit').innerHTML = '<i class="fa-solid fa-plus"></i> Simpan Skema';
            document.getElementById('skemaBtnBatalEdit').style.display = 'none';
        }

        function bukaModalHapusSkema(id, nama) {
            const m = document.getElementById('modalKonfirmasiHapusSkema');
            const inp = document.getElementById('inputIdSkemaHapus');
            const txt = document.getElementById('namaSkemaDihapus');
            if (m && inp && txt) {
                inp.value = id;
                txt.textContent = nama;
                m.classList.add('active');
            }
        }

        function tutupModalHapusSkema() {
            const m = document.getElementById('modalKonfirmasiHapusSkema');
            if (m) m.classList.remove('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const durasiInput = document.getElementById('skemaFormDurasi');
            if (durasiInput) {
                durasiInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value === '0') this.value = '1';
                });
            }
        });
    </script>
<?php endif; ?>