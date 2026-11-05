<?php
$modal_aktif  = $modal_aktif ?? ($_GET['modal'] ?? '');
$current_page = $current_page ?? ($_GET['page'] ?? 'manajemen_kamar');
?>
<?php if ($modal_aktif === 'tambah_kamar'): ?>
    <div class="modal-overlay active">
        <div class="modal-card-penghuni-custom modal-size-sm">
            <h3 class="modal-penghuni-title modal-title-spacing">Tambah Kamar</h3>
            
            <form action="?page=<?= htmlspecialchars($current_page) ?>" method="POST" class="form-penghuni-custom">
                <div class="field-group-custom">
                    <label class="label-custom" for="nomorKamar">Nomor Kamar</label>
                    <input type="text" id="nomorKamar" name="no_kamar" class="input-custom-pill input-hanya-angka" 
                           placeholder="Contoh: 04" maxlength="10" inputmode="numeric" pattern="[0-9]+" required autofocus>
                </div>

                <div class="actions-custom-row actions-spacing-top">
                    <a href="?page=<?= htmlspecialchars($current_page) ?>" class="btn-custom-pill btn-batal-custom">Batal</a>
                    <button type="submit" name="simpan_kamar" class="btn-custom-pill btn-simpan-custom">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const noKamarInput = document.getElementById('nomorKamar');
            if (noKamarInput) {
                noKamarInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }
        });
    </script>
<?php endif; ?>