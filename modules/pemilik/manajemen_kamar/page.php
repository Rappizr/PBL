<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'pemilik') {
//     header("Location: ../../public/index.php?page=login&role=pemilik");
//     exit;
// }

// data dummy Kamar
$daftar_kamar = [
    ['nomor' => '01', 'penghuni'=> 'Ridwan', 'status' => 'lunas'],
    ['nomor' => '02', 'penghuni'=> 'Rafi', 'status' => 'lunas'],
    ['nomor' => '03', 'penghuni'=> 'Bahlil', 'status' => 'belum'],
    ['nomor' => '04', 'penghuni'=> 'Belum terisi', 'status' => 'kosong'],
    ['nomor' => '05', 'penghuni'=> 'Joko', 'status' => 'lunas'],
    ['nomor' => '06', 'penghuni'=> 'Bowo', 'status' => 'lunas'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Manajemen Kamar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        /* ===== Modal Overlay ===== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            padding: 1rem;
        }

        .modal-overlay.active {
            display: flex;
        }

        /* ===== Modal Box ===== */
        .modal-box {
            width: 19.1rem;
            max-width: 100%;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 1.25rem;
            border-radius: 0.75rem;
            background: #F3CA81;
            box-shadow: 0 4px 9px 0 rgba(0, 0, 0, 0.25);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .modal-title {
            color: #155560;
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

        /* Form Group */
        .modal-form-group {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
            width: 100%;
        }

        .modal-label {
            color: #4E545C;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .modal-input,
        .modal-select {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border-radius: 0.75rem;
            border: none;
            background: #fff;
            box-shadow: 2px 2px 0 0 rgba(0, 0, 0, 0.25);
            color: #4E545C;
            font-size: 0.875rem;
            font-weight: 500;
            outline: none;
            box-sizing: border-box;
        }

        .modal-input:focus,
        .modal-select:focus {
            box-shadow: 2px 2px 0 0 rgba(76, 165, 176, 0.6);
        }

        /* Upload Area */
        .upload-area {
            display: flex;
            height: 9rem;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            border-radius: 0.75rem;
            border: 2px dashed #4CA5B0;
            background: #fff;
            box-shadow: 2px 2px 0 0 rgba(0, 0, 0, 0.25);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .upload-area input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-area .upload-icon {
            width: 2.25rem;
            height: 2.25rem;
        }

        .upload-area .upload-text {
            color: #4CA5B0;
            font-size: 0.625rem;
            font-weight: 400;
            margin: 0;
            text-align: center;
        }

        .upload-preview {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: none;
        }

        .upload-area.has-file .upload-icon,
        .upload-area.has-file .upload-text {
            display: none;
        }

        .upload-area.has-file .upload-preview {
            display: block;
        }

        /* Buttons */
        .modal-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
            margin-top: 0.25rem;
        }

        .btn-modal {
            display: flex;
            padding: 0.55rem 0.25rem;
            justify-content: center;
            align-items: center;
            flex: 1;
            border-radius: 0.75rem;
            cursor: pointer;
            border: none;
            font-family: inherit;
            transition: transform 0.1s, box-shadow 0.1s;
        }

        .btn-modal-batal {
            background: #fff;
            box-shadow: 4px 4px 0 0 rgba(0, 0, 0, 0.25);
        }

        .btn-modal-batal span {
            color: #4CA5B0;
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .btn-modal-simpan {
            background: #4CA5B0;
            box-shadow: 4px 4px 0 0 rgba(0, 0, 0, 0.25);
        }

        .btn-modal-simpan span {
            color: #fff;
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .btn-modal:active {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 0 rgba(0, 0, 0, 0.25);
        }
    </style>
</head>
<body class="kamar-body">
    <div class="mobile-container">
        <div>
            <?php require_once __DIR__ . '/../../components/header.php'; ?>

            <div class="kamar-title-card">
                <h2>Manajemen Kamar &amp; Penghuni</h2>
                <a href="/?page=dashboard_pemilik" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="kamar-actions">
                <button type="button" class="btn-add-outline" id="btnTambahKamar">
                    <span class="plus-icon">+</span> Tambah Kamar
                </button>
                <button type="button" class="btn-add-outline" id="btnTambahPenghuni">
                    <span class="plus-icon">+</span> Tambah Penghuni
                </button>
            </div>

            <div class="kamar-grid">
                <?php foreach ($daftar_kamar as $kamar): ?>
                    <div class="kamar-card">
                        <?php if ($kamar['status'] === 'lunas'): ?>
                            <span class="badge-status badge-lunas">Lunas</span>
                        <?php elseif ($kamar['status'] === 'belum'): ?>
                            <span class="badge-status badge-belum">Belum</span>
                        <?php else: ?>
                            <span class="badge-status badge-kosong">Kosong</span>
                        <?php endif; ?>

                        <div class="kamar-nomor"><?= htmlspecialchars($kamar['nomor']) ?></div>
                        <div class="kamar-nama"><?= htmlspecialchars($kamar['penghuni']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php require_once __DIR__ . '/../../components/footer.php'; ?>
    </div>

    <!-- ==================== MODAL TAMBAH KAMAR ==================== -->
    <div class="modal-overlay" id="modalTambahKamar">
        <div class="modal-box">
            <p class="modal-title">Tambah Kamar</p>

            <div class="modal-form-group">
                <label class="modal-label" for="nomorKamar">Nomor Kamar</label>
                <input type="text" id="nomorKamar" class="modal-input" placeholder="Contoh: 07" maxlength="5">
            </div>

            <div class="modal-form-group">
                <label class="modal-label" for="hargaSewa">Harga Sewa (Rp)</label>
                <input type="number" id="hargaSewa" class="modal-input" placeholder="Contoh: 450000" min="0">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal btn-modal-batal" id="btnBatalKamar">
                    <span>Batal</span>
                </button>
                <button type="button" class="btn-modal btn-modal-simpan" id="btnSimpanKamar">
                    <span>Simpan</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL TAMBAH PENGHUNI ==================== -->
    <div class="modal-overlay" id="modalTambahPenghuni">
        <div class="modal-box">
            <p class="modal-title">Tambah Penghuni</p>

            <div class="modal-form-group">
                <label class="modal-label" for="namaLengkap">Nama Lengkap</label>
                <input type="text" id="namaLengkap" class="modal-input" placeholder="Masukkan nama lengkap">
            </div>

            <div class="modal-form-group">
                <label class="modal-label" for="pilihKamar">Pilih Kamar</label>
                <select id="pilihKamar" class="modal-select">
                    <option value="">-- Pilih Kamar --</option>
                    <?php foreach ($daftar_kamar as $kamar): ?>
                        <?php if ($kamar['status'] === 'kosong'): ?>
                            <option value="<?= htmlspecialchars($kamar['nomor']) ?>">
                                Kamar <?= htmlspecialchars($kamar['nomor']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-form-group">
                <label class="modal-label" for="nomorHp">Nomor HP</label>
                <input type="tel" id="nomorHp" class="modal-input" placeholder="08xxxxxxxxxx">
            </div>

            <div class="modal-form-group">
                <label class="modal-label">Upload Foto KTP</label>
                <label class="upload-area" id="uploadKtpArea">
                    <input type="file" id="fotoKtp" accept="image/*">
                    <div class="upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 42 42" fill="none">
                            <path d="M16.4062 13.0306H19.4373V26.9062C19.4373 27.0866 19.585 27.2343 19.7654 27.2343H22.2264C22.4068 27.2343 22.5545 27.0866 22.5545 26.9062V13.0306H25.5938C25.8686 13.0306 26.0203 12.7148 25.8521 12.5015L21.2584 6.68547C21.2277 6.64625 21.1885 6.61453 21.1437 6.59272C21.0989 6.57091 21.0498 6.55957 21 6.55957C20.9502 6.55957 20.9011 6.57091 20.8563 6.59272C20.8115 6.61453 20.7723 6.64625 20.7416 6.68547L16.1479 12.4974C15.9797 12.7148 16.1314 13.0306 16.4062 13.0306ZM36.0117 25.6757H33.5508C33.3703 25.6757 33.2227 25.8234 33.2227 26.0038V32.3202H8.77734V26.0038C8.77734 25.8234 8.62969 25.6757 8.44922 25.6757H5.98828C5.80781 25.6757 5.66016 25.8234 5.66016 26.0038V34.1249C5.66016 34.8509 6.24668 35.4374 6.97266 35.4374H35.0273C35.7533 35.4374 36.3398 34.8509 36.3398 34.1249V26.0038C36.3398 25.8234 36.1922 25.6757 36.0117 25.6757Z" fill="#4CA5B0"/>
                        </svg>
                    </div>
                    <p class="upload-text">Ketuk untuk pilih foto KTP</p>
                    <img class="upload-preview" id="ktpPreview" alt="Preview KTP">
                </label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal btn-modal-batal" id="btnBatalPenghuni">
                    <span>Batal</span>
                </button>
                <button type="button" class="btn-modal btn-modal-simpan" id="btnSimpanPenghuni">
                    <span>Simpan</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ========== Modal Tambah Kamar ==========
            const modalKamar = document.getElementById('modalTambahKamar');
            const btnTambahKamar = document.getElementById('btnTambahKamar');
            const btnBatalKamar = document.getElementById('btnBatalKamar');
            const btnSimpanKamar = document.getElementById('btnSimpanKamar');

            btnTambahKamar.addEventListener('click', () => {
                document.getElementById('nomorKamar').value = '';
                document.getElementById('hargaSewa').value = '';
                modalKamar.classList.add('active');
                document.body.style.overflow = 'hidden';
            });

            function closeModalKamar() {
                modalKamar.classList.remove('active');
                document.body.style.overflow = '';
            }

            btnBatalKamar.addEventListener('click', closeModalKamar);
            modalKamar.addEventListener('click', (e) => {
                if (e.target === modalKamar) closeModalKamar();
            });

            btnSimpanKamar.addEventListener('click', () => {
                const nomor = document.getElementById('nomorKamar').value.trim();
                const harga = document.getElementById('hargaSewa').value.trim();

                if (!nomor || !harga) {
                    alert('Nomor kamar dan harga sewa wajib diisi.');
                    return;
                }

                // Simulasi simpan (ganti dengan AJAX / form submit)
                alert(`Kamar berhasil ditambahkan!\nNomor: ${nomor}\nHarga: Rp ${Number(harga).toLocaleString('id-ID')}`);
                closeModalKamar();
            });

            // ========== Modal Tambah Penghuni ==========
            const modalPenghuni = document.getElementById('modalTambahPenghuni');
            const btnTambahPenghuni = document.getElementById('btnTambahPenghuni');
            const btnBatalPenghuni = document.getElementById('btnBatalPenghuni');
            const btnSimpanPenghuni = document.getElementById('btnSimpanPenghuni');
            const fotoKtp = document.getElementById('fotoKtp');
            const uploadKtpArea = document.getElementById('uploadKtpArea');
            const ktpPreview = document.getElementById('ktpPreview');

            btnTambahPenghuni.addEventListener('click', () => {
                document.getElementById('namaLengkap').value = '';
                document.getElementById('pilihKamar').value = '';
                document.getElementById('nomorHp').value = '';
                fotoKtp.value = '';
                uploadKtpArea.classList.remove('has-file');
                ktpPreview.src = '';
                modalPenghuni.classList.add('active');
                document.body.style.overflow = 'hidden';
            });

            function closeModalPenghuni() {
                modalPenghuni.classList.remove('active');
                document.body.style.overflow = '';
            }

            btnBatalPenghuni.addEventListener('click', closeModalPenghuni);
            modalPenghuni.addEventListener('click', (e) => {
                if (e.target === modalPenghuni) closeModalPenghuni();
            });

            // Preview KTP
            fotoKtp.addEventListener('change', function () {
                const file = this.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        ktpPreview.src = e.target.result;
                        uploadKtpArea.classList.add('has-file');
                    };
                    reader.readAsDataURL(file);
                }
            });

            btnSimpanPenghuni.addEventListener('click', () => {
                const nama = document.getElementById('namaLengkap').value.trim();
                const kamar = document.getElementById('pilihKamar').value;
                const hp = document.getElementById('nomorHp').value.trim();

                if (!nama || !kamar || !hp) {
                    alert('Nama, kamar, dan nomor HP wajib diisi.');
                    return;
                }
                if (!fotoKtp.files.length) {
                    alert('Foto KTP wajib diupload.');
                    return;
                }

                // Simulasi simpan
                alert(`Penghuni berhasil ditambahkan!\nNama: ${nama}\nKamar: ${kamar}\nHP: ${hp}`);
                closeModalPenghuni();
            });

            // Escape key menutup modal manapun yang terbuka
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (modalKamar.classList.contains('active')) closeModalKamar();
                    if (modalPenghuni.classList.contains('active')) closeModalPenghuni();
                }
            });
        });
    </script>
</body>
</html>