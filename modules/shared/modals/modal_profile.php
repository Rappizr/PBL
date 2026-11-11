<?php
/** @var string|null $show_modal */
/** @var string $nama_user */
/** @var string|null $no_wa */
/** @var string|null $kontak_wali */
/** @var string|null $no_rekening */
/** @var string $pin_aktif */

$show_modal = $show_modal ?? ($_GET['modal'] ?? null);
$nama_user  = $nama_user  ?? ($_SESSION['user']['nama'] ?? '');

global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../../config/database.php',
        __DIR__ . '/../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php'
    ];
    foreach ($possible_paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            if (isset($pdo) && ($pdo instanceof PDO)) break;
        }
    }
}

$user_id_aktif = (int)($_SESSION['user']['id'] ?? 1);
$role_aktif    = $_SESSION['user']['role'] ?? 'pemilik';

if (isset($pdo) && ($pdo instanceof PDO)) {
    if ($role_aktif === 'pemilik') {
        $stmtPin = $pdo->prepare("SELECT pin, email FROM pemilik WHERE id = :id LIMIT 1");
    } else {
        $stmtPin = $pdo->prepare("SELECT pin, email, kontak_wali FROM penghuni WHERE id = :id LIMIT 1");
    }
    $stmtPin->execute([':id' => $user_id_aktif]);
    $dataDb = $stmtPin->fetch(PDO::FETCH_ASSOC);

    if ($dataDb) {
        $pin_aktif   = trim((string)$dataDb['pin']);
        $email_user  = trim((string)$dataDb['email']);
        if ($role_aktif !== 'pemilik' && isset($dataDb['kontak_wali'])) {
            $kontak_wali = $dataDb['kontak_wali'];
        }
        $_SESSION['user']['pin']   = $pin_aktif;
        $_SESSION['user']['email'] = $email_user;
    } else {
        $pin_aktif   = $pin_aktif  ?? ($_SESSION['user']['pin'] ?? '');
        $email_user  = $email_user ?? ($_SESSION['user']['email'] ?? '');
    }
}
?>

<!-- =========================================================================
     MODAL 1: UBAH DATA PROFIL (DISESUAIKAN BERDASARKAN ROLE)
     ========================================================================= -->
<?php if ($show_modal === 'ubah_profil'): ?>
    <div class="modal-overlay active">
        <div class="modal-card">
            <h3 class="modal-title">Edit Data Profil</h3>
            <form action="/?page=profile" method="POST" class="modal-form" id="formEditProfil">
                <input type="hidden" name="action" value="ubah_profil">
                
                <!-- Input Nama: Angka dilarang total -->
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

                <!-- Input Nomor WhatsApp -->
                <div class="modal-field">
                    <label class="modal-label" for="edit_wa">Nomor WhatsApp</label>
                    <input type="tel" id="edit_wa" name="no_wa"
                           class="modal-input" placeholder="Contoh: 0812345678"
                           inputmode="numeric" pattern="[0-9]*" maxlength="15"
                           value="<?= htmlspecialchars($no_wa ?? ''); ?>">
                </div>

                <!-- Jika Pemilik: Tampilkan Nomor Rekening -->
                <?php if ($role_aktif === 'pemilik'): ?>
                <div class="modal-field">
                    <label class="modal-label" for="edit_rek">Nomor Rekening</label>
                    <input type="text" id="edit_rek" name="no_rekening"
                           class="modal-input" placeholder="Contoh: 0000000000"
                           inputmode="numeric" pattern="[0-9]*" maxlength="25"
                           value="<?= htmlspecialchars($no_rekening ?? ''); ?>">
                </div>
                <?php else: ?>
                <!-- Jika Penghuni/PJ: Tampilkan Kontak Wali (Bukan Rekening) -->
                <div class="modal-field">
                    <label class="modal-label" for="edit_wali">Kontak Wali</label>
                    <input type="tel" id="edit_wali" name="kontak_wali"
                           class="modal-input" placeholder="Contoh: 08883199088"
                           inputmode="numeric" pattern="[0-9]*" maxlength="15"
                           value="<?= htmlspecialchars($kontak_wali ?? ''); ?>">
                </div>
                <?php endif; ?>

                <div class="modal-actions">
                    <a href="/?page=profile" class="btn-modal-cancel">Batal</a>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
<?php endif; ?>

<!-- =========================================================================
     MODAL 2: UBAH PIN
     ========================================================================= -->
<?php if ($show_modal === 'ubah_password'): ?>
    <div class="modal-overlay active">
        <div class="modal-card modal-card-pin-custom">
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
            const pinLamaEl = document.getElementById('password_lama');
            const pinLamaVal = pinLamaEl ? pinLamaEl.value.trim() : '';
            const pinBaru = document.getElementById('password_baru');
            const pinKonf = document.getElementById('password_konfirmasi');
            const btnSubmit = document.getElementById('btnSubmitPin');

            const msgBaru = document.getElementById('msgPinBaru');
            const msgKonf = document.getElementById('msgPinKonfirmasi');

            function sanitizeNumeric() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                validasiForm();
            }

            if (pinBaru && pinKonf) {
                pinBaru.addEventListener('input', sanitizeNumeric);
                pinKonf.addEventListener('input', sanitizeNumeric);
            }

            function validasiForm() {
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

                if (pinBaruValid && pinKonfValid) {
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

<!-- =========================================================================
     MODAL 3: UBAH EMAIL
     ========================================================================= -->
<?php if ($show_modal === 'ubah_email'): ?>
    <div class="modal-overlay active">
        <div class="modal-card modal-card-pin-custom">
            <h3 class="modal-title">Ubah Email</h3>
            
            <form action="/?page=profile" method="POST" class="modal-form" id="formUbahEmail">
                <input type="hidden" name="action" value="ubah_email">
                
                <div class="modal-field">
                    <label class="modal-label" for="email_lama">Email Saat Ini</label>
                    <input type="email" id="email_lama" name="email_lama"
                           class="modal-input input-readonly-locked"
                           value="<?= htmlspecialchars($email_user ?? 'Belum diatur'); ?>" readonly autocomplete="off">
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
                    <a href="/?page=profile" class="btn-modal-cancel">Batal</a>
                    <button type="submit" id="btnSubmitEmail" class="btn-modal-submit btn-disabled" disabled>Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailLama = "<?= strtolower(trim($email_user ?? '')); ?>";
            const emailBaruInput = document.getElementById('email_baru');
            const pinVerifInput = document.getElementById('password_verifikasi');
            const btnSubmit = document.getElementById('btnSubmitEmail');

            const msgEmail = document.getElementById('msgEmailBaru');
            const msgPin = document.getElementById('msgPinVerif');

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
                const pinVal = pinVerifInput ? pinVerifInput.value : '';

                let emailValid = false;
                let pinValid = false;

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

                if (emailValid && pinValid) {
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