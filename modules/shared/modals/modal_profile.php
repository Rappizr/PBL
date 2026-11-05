<?php
/** @var string|null $show_modal */
/** @var string $nama_user */
/** @var string|null $no_wa */
/** @var string|null $no_rekening */
/** @var string $pin_aktif */

$show_modal = $show_modal ?? ($_GET['modal'] ?? null);
$pin_aktif  = $pin_aktif  ?? ($_SESSION['user']['pin'] ?? '');
$nama_user  = $nama_user  ?? ($_SESSION['user']['nama'] ?? '');
?>

<!-- MODAL 1: UBAH DATA PROFIL -->
<?php if ($show_modal === 'ubah_profil'): ?>
    <div class="modal-overlay active">
        <div class="modal-card">
            <h3 class="modal-title">Edit Data Profil</h3>
            <form action="/?page=profile" method="POST" class="modal-form">
                <input type="hidden" name="action" value="ubah_profil">
                
                <div class="modal-field">
                    <label class="modal-label" for="edit_nama">Nama Lengkap</label>
                    <input type="text" id="edit_nama" name="nama"
                           class="modal-input" placeholder="Masukkan nama lengkap"
                           value="<?= htmlspecialchars($nama_user); ?>" required>
                </div>

                <div class="modal-field">
                    <label class="modal-label" for="edit_wa">Nomor WhatsApp</label>
                    <input type="tel" id="edit_wa" name="no_wa"
                           class="modal-input" placeholder="Contoh: 0812345678"
                           value="<?= htmlspecialchars($no_wa ?? ''); ?>">
                </div>

                <div class="modal-field">
                    <label class="modal-label" for="edit_rek">Nomor Rekening</label>
                    <input type="text" id="edit_rek" name="no_rekening"
                           class="modal-input" placeholder="Contoh: 0000000000"
                           value="<?= htmlspecialchars($no_rekening ?? ''); ?>">
                </div>

                <div class="modal-actions">
                    <a href="/?page=profile" class="btn-modal-cancel">Batal</a>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL 2: UBAH PIN DENGAN ICON MATA & VALIDASI REALTIME -->
<?php if ($show_modal === 'ubah_password'): ?>
    <div class="modal-overlay active">
        <div class="modal-card modal-card-pin-custom">
            <h3 class="modal-title">Ubah Pin</h3>
            
            <form action="/?page=profile" method="POST" class="modal-form" id="formUbahPin">
                <input type="hidden" name="action" value="ubah_password">
                <input type="hidden" id="current_pin_reference" value="<?= htmlspecialchars($pin_aktif); ?>">

                <!-- PIN Lama -->
                <div class="modal-field">
                    <label class="modal-label" for="password_lama">Pin Lama</label>
                    <div class="input-password-wrapper">
                        <input type="password" id="password_lama" name="password_lama"
                               class="modal-input input-pin-pad" pattern="[0-9]{6}" inputmode="numeric"
                               maxlength="6" placeholder="Masukkan 6 digit PIN lama" required autocomplete="off">
                        <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility('password_lama', this)">
                            <i class="fa-solid fa-eye-slash"></i>
                        </button>
                    </div>
                    <span class="pin-hint-msg" id="msgPinLama"></span>
                </div>

                <!-- PIN Baru -->
                <div class="modal-field">
                    <label class="modal-label" for="password_baru">Pin Baru</label>
                    <div class="input-password-wrapper">
                        <input type="password" id="password_baru" name="password_baru"
                               class="modal-input input-pin-pad input-disabled-style" pattern="[0-9]{6}" inputmode="numeric"
                               maxlength="6" placeholder="6 digit PIN baru" required autocomplete="off" disabled>
                        <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility('password_baru', this)">
                            <i class="fa-solid fa-eye-slash"></i>
                        </button>
                    </div>
                    <span class="pin-hint-msg" id="msgPinBaru"></span>
                </div>

                <!-- Konfirmasi PIN Baru -->
                <div class="modal-field">
                    <label class="modal-label" for="password_konfirmasi">Konfirmasi Pin Baru</label>
                    <div class="input-password-wrapper">
                        <input type="password" id="password_konfirmasi" name="password_konfirmasi"
                               class="modal-input input-pin-pad input-disabled-style" pattern="[0-9]{6}" inputmode="numeric"
                               maxlength="6" placeholder="Ulangi 6 digit PIN baru" required autocomplete="off" disabled>
                        <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility('password_konfirmasi', this)">
                            <i class="fa-solid fa-eye-slash"></i>
                        </button>
                    </div>
                    <span class="pin-hint-msg" id="msgPinKonfirmasi"></span>
                </div>

                <div class="modal-actions">
                    <a href="/?page=profile" class="btn-modal-cancel">Batal</a>
                    <button type="submit" id="btnSubmitPin" class="btn-modal-submit btn-disabled" disabled>Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const pinRef = document.getElementById('current_pin_reference').value.trim();
            const pinLama = document.getElementById('password_lama');
            const pinBaru = document.getElementById('password_baru');
            const pinKonf = document.getElementById('password_konfirmasi');
            const btnSubmit = document.getElementById('btnSubmitPin');

            const msgLama = document.getElementById('msgPinLama');
            const msgBaru = document.getElementById('msgPinBaru');
            const msgKonf = document.getElementById('msgPinKonfirmasi');

            function sanitizeNumeric() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                validasiForm();
            }

            pinLama.addEventListener('input', sanitizeNumeric);
            pinBaru.addEventListener('input', sanitizeNumeric);
            pinKonf.addEventListener('input', sanitizeNumeric);

            function validasiForm() {
                const valLama = pinLama.value;
                const valBaru = pinBaru.value;
                const valKonf = pinKonf.value;
                let pinLamaCocok = false;

                if (valLama.length === 0) {
                    msgLama.textContent = '';
                    msgLama.className = 'pin-hint-msg';
                } else if (valLama.length < 6) {
                    msgLama.textContent = 'Harus 6 digit angka.';
                    msgLama.className = 'pin-hint-msg error';
                } else {
                    if (valLama === pinRef) {
                        msgLama.textContent = 'PIN lama sesuai.';
                        msgLama.className = 'pin-hint-msg success';
                        pinLamaCocok = true;
                    } else {
                        msgLama.textContent = 'PIN lama tidak sesuai dengan akun saat ini.';
                        msgLama.className = 'pin-hint-msg error';
                    }
                }

                if (pinLamaCocok) {
                    pinBaru.disabled = false;
                    pinBaru.classList.remove('input-disabled-style');
                    pinKonf.disabled = false;
                    pinKonf.classList.remove('input-disabled-style');
                } else {
                    pinBaru.disabled = true;
                    pinBaru.classList.add('input-disabled-style');
                    pinBaru.value = '';
                    pinKonf.disabled = true;
                    pinKonf.classList.add('input-disabled-style');
                    pinKonf.value = '';

                    msgBaru.textContent = '';
                    msgKonf.textContent = '';
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('btn-disabled');
                    return;
                }

                let pinBaruValid = false;
                if (valBaru.length > 0 && valBaru.length < 6) {
                    msgBaru.textContent = 'PIN baru harus 6 digit angka.';
                    msgBaru.className = 'pin-hint-msg error';
                } else if (valBaru.length === 6) {
                    if (valBaru === valLama) {
                        msgBaru.textContent = 'PIN baru tidak boleh sama dengan PIN lama.';
                        msgBaru.className = 'pin-hint-msg error';
                    } else {
                        msgBaru.textContent = 'Format PIN baru valid.';
                        msgBaru.className = 'pin-hint-msg success';
                        pinBaruValid = true;
                    }
                } else {
                    msgBaru.textContent = '';
                }

                let pinKonfValid = false;
                if (valKonf.length > 0) {
                    if (valKonf !== valBaru) {
                        msgKonf.textContent = 'Konfirmasi PIN belum cocok.';
                        msgKonf.className = 'pin-hint-msg error';
                    } else {
                        msgKonf.textContent = 'PIN cocok!';
                        msgKonf.className = 'pin-hint-msg success';
                        pinKonfValid = true;
                    }
                } else {
                    msgKonf.textContent = '';
                }

                if (pinLamaCocok && pinBaruValid && pinKonfValid) {
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('btn-disabled');
                } else {
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('btn-disabled');
                }
            }
        });
    </script>
<?php endif; ?>

<!-- MODAL 3: UBAH EMAIL -->
<?php if ($show_modal === 'ubah_email'): ?>
    <div class="modal-overlay active">
        <div class="modal-card">
            <h3 class="modal-title">Ubah Email</h3>
            <form action="/?page=profile" method="POST" class="modal-form">
                <input type="hidden" name="action" value="ubah_email">
                <div class="modal-field">
                    <label class="modal-label" for="email_baru">Email Baru</label>
                    <input type="email" id="email_baru" name="email_baru"
                           class="modal-input" placeholder="contoh@email.com"
                           value="<?= htmlspecialchars($email_user ?? ''); ?>" required>
                </div>
                <div class="modal-field">
                    <label class="modal-label" for="password_verifikasi">Konfirmasi dengan PIN</label>
                    <input type="password" id="password_verifikasi" name="password_verifikasi"
                           class="modal-input" pattern="[0-9]{6}" inputmode="numeric"
                           maxlength="6" placeholder="Masukkan 6 digit PIN akun" required>
                </div>
                <div class="modal-actions">
                    <a href="/?page=profile" class="btn-modal-cancel">Batal</a>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>