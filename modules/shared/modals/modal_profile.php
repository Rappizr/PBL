<?php
/** @var string|null $show_modal */
/** @var string $nama_user */
/** @var string|null $email_user */
/** @var string|null $no_wa */
/** @var string|null $kontak_wali */
/** @var string|null $no_rekening */
/** @var string $pin_aktif */
/** @var string $role_user */

$show_modal  = $show_modal  ?? ($_GET['modal'] ?? null);
$role_aktif  = $role_user   ?? ($_SESSION['user']['role'] ?? 'pemilik');
$nama_user   = $nama_user   ?? ($_SESSION['user']['nama'] ?? '');
$email_user  = $email_user  ?? ($_SESSION['user']['email'] ?? '');
$no_wa       = $no_wa       ?? ($_SESSION['user']['no_wa'] ?? '');
$kontak_wali = $kontak_wali ?? ($_SESSION['user']['kontak_wali'] ?? '');
$no_rekening = $no_rekening ?? ($_SESSION['user']['no_rekening'] ?? '');
$pin_aktif   = $pin_aktif   ?? ($_SESSION['user']['pin'] ?? '');
?>

<!-- =========================================================================
     MODAL 1: UBAH DATA PROFIL (INSTAN TANPA RELOAD)
     ========================================================================= -->
<div id="modalUbahProfil" class="modal-overlay <?= $show_modal === 'ubah_profil' ? 'active' : ''; ?>" onclick="tutupModalProfileInstan(event)">
    <div class="modal-card" onclick="event.stopPropagation()">
        <h3 class="modal-title">Edit Data Profil</h3>
        <form action="/?page=profile" method="POST" class="modal-form" id="formEditProfil">
            <input type="hidden" name="action" value="ubah_profil">
            
            <div class="modal-field">
                <label class="modal-label" for="edit_nama">Nama Lengkap</label>
                <input type="text" id="edit_nama" name="nama"
                       class="modal-input" placeholder="Masukkan nama lengkap"
                       value="<?= htmlspecialchars($nama_user); ?>" 
                       pattern="^[a-zA-Z\s'.]+$" 
                       title="Nama hanya boleh berisi huruf, spasi, tanda petik, dan titik."
                       oninput="this.value = this.value.replace(/[^a-zA-Z\s'.]/g, '')"
                       required autocomplete="name">
            </div>

            <div class="modal-field">
                <label class="modal-label" for="edit_wa">Nomor WhatsApp</label>
                <input type="tel" id="edit_wa" name="no_wa"
                       class="modal-input" placeholder="Contoh: 0812345678"
                       inputmode="numeric" pattern="[0-9]*" maxlength="15"
                       value="<?= htmlspecialchars($no_wa ?? ''); ?>">
            </div>

            <?php if ($role_aktif === 'pemilik'): ?>
            <div class="modal-field">
                <label class="modal-label" for="edit_rek">Nomor Rekening</label>
                <input type="text" id="edit_rek" name="no_rekening"
                       class="modal-input" placeholder="Contoh: 0000000000"
                       inputmode="numeric" pattern="[0-9]*" maxlength="25"
                       value="<?= htmlspecialchars($no_rekening ?? ''); ?>">
            </div>
            <?php else: ?>
            <div class="modal-field">
                <label class="modal-label" for="edit_wali">Kontak Wali</label>
                <input type="tel" id="edit_wali" name="kontak_wali"
                       class="modal-input" placeholder="Contoh: 08883199088"
                       inputmode="numeric" pattern="[0-9]*" maxlength="15"
                       value="<?= htmlspecialchars($kontak_wali ?? ''); ?>">
            </div>
            <?php endif; ?>

            <div class="modal-actions">
                <button type="button" onclick="tutupModalProfileInstan(event)" class="btn-modal-cancel">Batal</button>
                <button type="submit" class="btn-modal-submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL 2: UBAH PIN (INSTAN TANPA RELOAD)
     ========================================================================= -->
<div id="modalUbahPassword" class="modal-overlay <?= $show_modal === 'ubah_password' ? 'active' : ''; ?>" onclick="tutupModalProfileInstan(event)">
    <div class="modal-card modal-card-pin-custom" onclick="event.stopPropagation()">
        <h3 class="modal-title">Ubah Pin</h3>
        
        <form action="/?page=profile" method="POST" class="modal-form" id="formUbahPin">
            <input type="hidden" name="action" value="ubah_password">
            
            <div class="modal-field">
                <label class="modal-label" for="password_lama">Pin Lama</label>
                <div class="input-password-wrapper">
                    <input type="password" id="password_lama" name="password_lama"
                           class="modal-input input-pin-pad input-readonly-locked"
                           value="<?= htmlspecialchars($pin_aktif); ?>" readonly autocomplete="off">
                    <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility('password_lama', this)">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>
            </div>

            <div class="modal-field">
                <label class="modal-label" for="password_baru">Pin Baru</label>
                <div class="input-password-wrapper">
                    <input type="password" id="password_baru" name="password_baru"
                           class="modal-input input-pin-pad" pattern="[0-9]{6}" inputmode="numeric"
                           maxlength="6" placeholder="Masukkan 6 digit PIN baru" required autocomplete="new-password">
                    <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility('password_baru', this)">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>
                <span class="pin-hint-msg" id="msgPinBaru"></span>
            </div>

            <div class="modal-field">
                <label class="modal-label" for="password_konfirmasi">Konfirmasi Pin Baru</label>
                <div class="input-password-wrapper">
                    <input type="password" id="password_konfirmasi" name="password_konfirmasi"
                           class="modal-input input-pin-pad" pattern="[0-9]{6}" inputmode="numeric"
                           maxlength="6" placeholder="Ulangi 6 digit PIN baru" required autocomplete="new-password">
                    <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility('password_konfirmasi', this)">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>
                <span class="pin-hint-msg" id="msgPinKonfirmasi"></span>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="tutupModalProfileInstan(event)" class="btn-modal-cancel">Batal</button>
                <button type="submit" id="btnSubmitPin" class="btn-modal-submit btn-disabled" disabled>Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL 3: UBAH EMAIL (INSTAN TANPA RELOAD)
     ========================================================================= -->
<div id="modalUbahEmail" class="modal-overlay <?= $show_modal === 'ubah_email' ? 'active' : ''; ?>" onclick="tutupModalProfileInstan(event)">
    <div class="modal-card modal-card-pin-custom" onclick="event.stopPropagation()">
        <h3 class="modal-title">Ubah Email</h3>
        
        <form action="/?page=profile" method="POST" class="modal-form" id="formUbahEmail">
            <input type="hidden" name="action" value="ubah_email">
            
            <div class="modal-field">
                <label class="modal-label" for="email_lama">Email Saat Ini</label>
                <input type="email" id="email_lama" name="email_lama"
                       class="modal-input input-readonly-locked"
                       value="<?= htmlspecialchars($email_user ?: 'Belum diatur'); ?>" readonly autocomplete="off">
            </div>

            <div class="modal-field">
                <label class="modal-label" for="email_baru">Email Baru</label>
                <input type="email" id="email_baru" name="email_baru"
                       class="modal-input" placeholder="contoh@email.com"
                       required autocomplete="off">
                <span class="pin-hint-msg" id="msgEmailBaru"></span>
            </div>

            <div class="modal-field">
                <label class="modal-label" for="password_verifikasi">Konfirmasi dengan PIN</label>
                <div class="input-password-wrapper">
                    <input type="password" id="password_verifikasi" name="password_verifikasi"
                           class="modal-input input-pin-pad" pattern="[0-9]{6}" inputmode="numeric"
                           maxlength="6" placeholder="Masukkan 6 digit PIN akun" required autocomplete="new-password">
                    <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility('password_verifikasi', this)">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>
                <span class="pin-hint-msg" id="msgPinVerif"></span>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="tutupModalProfileInstan(event)" class="btn-modal-cancel">Batal</button>
                <button type="submit" id="btnSubmitEmail" class="btn-modal-submit btn-disabled" disabled>Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function bukaModalProfileInstan(e, modalId, modalParam) {
        if (e) e.preventDefault();
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.classList.add('active');
        const firstInput = modal.querySelector('input:not([type="hidden"]):not([readonly])');
        if (firstInput) setTimeout(() => firstInput.focus(), 50);

        const url = new URL(window.location.href);
        url.searchParams.set('modal', modalParam);
        window.history.replaceState({}, '', url);
    }

    function tutupModalProfileInstan(e) {
        if (e) e.preventDefault();
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));

        const url = new URL(window.location.href);
        url.searchParams.delete('modal');
        window.history.replaceState({}, '', url);
    }

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

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') tutupModalProfileInstan();
    });

    document.addEventListener('DOMContentLoaded', function() {
        // --- Validasi Form Profil ---
        const namaInput = document.getElementById('edit_nama');
        const waInput   = document.getElementById('edit_wa');
        const waliInput = document.getElementById('edit_wali');
        const rekInput  = document.getElementById('edit_rek');

        function filterHanyaHuruf() {
            this.value = this.value.replace(/[^a-zA-Z\s'.]/g, '');
        }

        function filterHanyaAngka() {
            this.value = this.value.replace(/[^0-9]/g, '');
        }

        if (namaInput) {
            namaInput.addEventListener('input', filterHanyaHuruf);
            namaInput.addEventListener('paste', function() {
                setTimeout(() => { this.value = this.value.replace(/[^a-zA-Z\s'.]/g, ''); }, 10);
            });
        }

        [waInput, waliInput, rekInput].forEach(function(input) {
            if (input) {
                input.addEventListener('input', filterHanyaAngka);
                input.addEventListener('paste', function() {
                    setTimeout(() => { this.value = this.value.replace(/[^0-9]/g, ''); }, 10);
                });
            }
        });

        // --- Validasi Form Ubah PIN ---
        const pinLamaEl    = document.getElementById('password_lama');
        const pinLamaVal   = pinLamaEl ? pinLamaEl.value.trim() : '';
        const pinBaru      = document.getElementById('password_baru');
        const pinKonf      = document.getElementById('password_konfirmasi');
        const btnSubmitPin = document.getElementById('btnSubmitPin');
        const msgBaru      = document.getElementById('msgPinBaru');
        const msgKonf      = document.getElementById('msgPinKonfirmasi');

        function sanitizeNumericPin() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            validasiFormPin();
        }

        if (pinBaru && pinKonf) {
            pinBaru.addEventListener('input', sanitizeNumericPin);
            pinKonf.addEventListener('input', sanitizeNumericPin);
        }

        function validasiFormPin() {
            const valBaru = pinBaru ? pinBaru.value : '';
            const valKonf = pinKonf ? pinKonf.value : '';

            let pinBaruValid = false;
            if (valBaru.length > 0 && valBaru.length < 6) {
                msgBaru.textContent = 'PIN baru harus 6 digit angka.';
                msgBaru.className = 'pin-hint-msg error';
            } else if (valBaru.length === 6) {
                if (valBaru === pinLamaVal) {
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

            if (btnSubmitPin) {
                btnSubmitPin.disabled = !(pinBaruValid && pinKonfValid);
                btnSubmitPin.classList.toggle('btn-disabled', !(pinBaruValid && pinKonfValid));
            }
        }

        // --- Validasi Form Ubah Email ---
        const emailLama      = "<?= strtolower(trim($email_user ?? '')); ?>";
        const emailBaruInput = document.getElementById('email_baru');
        const pinVerifInput  = document.getElementById('password_verifikasi');
        const btnSubmitEmail = document.getElementById('btnSubmitEmail');
        const msgEmail       = document.getElementById('msgEmailBaru');
        const msgPin         = document.getElementById('msgPinVerif');

        if (pinVerifInput) {
            pinVerifInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                validasiFormEmail();
            });
        }

        if (emailBaruInput) {
            emailBaruInput.addEventListener('input', validasiFormEmail);
        }

        function validasiFormEmail() {
            const emailVal = emailBaruInput ? emailBaruInput.value.trim().toLowerCase() : '';
            const pinVal   = pinVerifInput ? pinVerifInput.value : '';

            let emailValid = false;
            let pinValid   = false;

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailVal === '') {
                msgEmail.textContent = '';
                msgEmail.className = 'pin-hint-msg';
            } else if (!emailRegex.test(emailVal)) {
                msgEmail.textContent = 'Format email tidak valid.';
                msgEmail.className = 'pin-hint-msg error';
            } else if (emailVal === emailLama) {
                msgEmail.textContent = 'Email baru tidak boleh sama dengan email saat ini.';
                msgEmail.className = 'pin-hint-msg error';
            } else {
                msgEmail.textContent = 'Email valid.';
                msgEmail.className = 'pin-hint-msg success';
                emailValid = true;
            }

            if (pinVal.length === 0) {
                msgPin.textContent = '';
                msgPin.className = 'pin-hint-msg';
            } else if (pinVal.length < 6) {
                msgPin.textContent = 'PIN harus 6 digit angka.';
                msgPin.className = 'pin-hint-msg error';
            } else {
                msgPin.textContent = '';
                pinValid = true;
            }

            if (btnSubmitEmail) {
                btnSubmitEmail.disabled = !(emailValid && pinValid);
                btnSubmitEmail.classList.toggle('btn-disabled', !(emailValid && pinValid));
            }
        }
    });
</script>