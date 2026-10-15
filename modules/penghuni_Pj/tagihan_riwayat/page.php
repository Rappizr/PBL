<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nama_penghuni = "Mas Dafa";

// Data dummy sesuai mockup
$daftar_tagihan_saya = [
    [
        'id' => 1,
        'jenis' => 'Sewa',
        'nominal' => 450000,
        'jatuh_tempo' => '11-10-26'
    ],
    [
        'id' => 2,
        'jenis' => 'Iuran',
        'nominal' => 450000,
        'jatuh_tempo' => '11-10-26'
    ]
];

$daftar_riwayat = [
    [
        'id' => 101,
        'judul' => 'Sewa Bulanan',
        'tanggal' => '05 September 2026',
        'nominal' => 1200000
    ],
    [
        'id' => 102,
        'judul' => 'Iuran Bulanan',
        'tanggal' => '05 September 2026',
        'nominal' => 1200000
    ]
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Tagihan &amp; Riwayat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>

<body class="dashboard-body">
    <div class="mobile-container">
        <div>
            <!-- Header Top Bar -->
            <header class="dashboard-header">
                <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>
                <div class="kotak-kapsul-pemilik">
                    <span class="lingkaran-user-abu">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <span class="tulisan-nama"><?= htmlspecialchars($nama_penghuni); ?></span>
                </div>
            </header>

            <main class="page-content">
                <!-- Banner Tagihan & Riwayat + Tombol Kembali -->
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Tagihan &amp; Riwayat</h2>
                    <a href="/dashboard/penghuni" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Section: Tagihan Saya -->
                <h3 class="section-title-white">Tagihan Saya</h3>
                <div class="section-group">
                    <?php foreach ($daftar_tagihan_saya as $t): ?>
                        <div class="item-tagihan-card">
                            <div class="item-tagihan-header">
                                <h4 class="item-tagihan-name"><?= htmlspecialchars($t['jenis']) ?></h4>
                                <span class="badge-status-krem">Belum bayar</span>
                            </div>

                            <div class="item-tagihan-body">
                                <div class="item-tagihan-left">
                                    <span>Total tagihan</span>
                                    <span>Batas bayar</span>
                                </div>
                                <div class="item-tagihan-right">
                                    <span class="nominal-highlight">Rp <?= number_format($t['nominal'], 0, ',', '.') ?></span>
                                    <span><?= htmlspecialchars($t['jatuh_tempo']) ?></span>
                                </div>
                            </div>

                            <!-- Tombol Bayar → buka modal -->
                            <button type="button"
                                    class="btn-card-action btn-bayar"
                                    data-id="<?= $t['id'] ?>"
                                    data-jenis="<?= htmlspecialchars($t['jenis']) ?>"
                                    data-nominal="<?= $t['nominal'] ?>"
                                    data-jatuh-tempo="<?= htmlspecialchars($t['jatuh_tempo']) ?>">
                                Bayar
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Section: Riwayat Transaksi -->
                <h3 class="section-title-white">Riwayat Transaksi</h3>
                <div class="section-group">
                    <?php foreach ($daftar_riwayat as $r): ?>
                        <div class="item-tagihan-card">
                            <div class="item-tagihan-header">
                                <h4 class="item-tagihan-name"><?= htmlspecialchars($r['judul']) ?></h4>
                                <span class="badge-status-lunas">Lunas</span>
                            </div>

                            <div class="item-tagihan-body">
                                <div class="item-tagihan-left">
                                    <span><?= htmlspecialchars($r['tanggal']) ?></span>
                                </div>
                                <div class="item-tagihan-right">
                                    <span class="nominal-highlight">Rp <?= number_format($r['nominal'], 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <a href="/?page=cetak-invoice&id=<?= $r['id'] ?>" target="_blank" class="btn-card-action">
                                Cetak Invoice
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <!-- Footer Modular -->
        <?php require_once __DIR__ . '/../../components/footer.php'; ?>
    </div>

    <!-- ==================== MODAL BAYAR TAGIHAN ==================== -->
    <div class="modal-overlay" id="modalBayar">
        <div class="modal-bayar">
            <p class="modal-title">Bayar Tagihan</p>

            <div class="modal-info-box">
                <span class="batas" id="modalBatas">Batas bayar 11-10-26</span>
                <span class="nominal" id="modalNominal">Rp 450.000</span>
            </div>

            <div class="modal-section">
                <div class="modal-section-label">Transfer ke rekening berikut:</div>
                <div class="rekening-box">
                    BCA 12345678 a.n Bapak Kos
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-label">Upload Bukti Pembayaran</div>
                <label class="upload-area" id="uploadArea">
                    <input type="file" id="buktiPembayaran" accept="image/*">
                    <div class="upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 42 42" fill="none">
                            <path d="M16.4062 13.0306H19.4373V26.9062C19.4373 27.0866 19.585 27.2343 19.7654 27.2343H22.2264C22.4068 27.2343 22.5545 27.0866 22.5545 26.9062V13.0306H25.5938C25.8686 13.0306 26.0203 12.7148 25.8521 12.5015L21.2584 6.68547C21.2277 6.64625 21.1885 6.61453 21.1437 6.59272C21.0989 6.57091 21.0498 6.55957 21 6.55957C20.9502 6.55957 20.9011 6.57091 20.8563 6.59272C20.8115 6.61453 20.7723 6.64625 20.7416 6.68547L16.1479 12.4974C15.9797 12.7148 16.1314 13.0306 16.4062 13.0306ZM36.0117 25.6757H33.5508C33.3703 25.6757 33.2227 25.8234 33.2227 26.0038V32.3202H8.77734V26.0038C8.77734 25.8234 8.62969 25.6757 8.44922 25.6757H5.98828C5.80781 25.6757 5.66016 25.8234 5.66016 26.0038V34.1249C5.66016 34.8509 6.24668 35.4374 6.97266 35.4374H35.0273C35.7533 35.4374 36.3398 34.8509 36.3398 34.1249V26.0038C36.3398 25.8234 36.1922 25.6757 36.0117 25.6757Z" fill="#4CA5B0" />
                        </svg>
                    </div>
                    <p class="upload-text">Ketuk untuk pilih foto</p>
                    <img class="upload-preview" id="uploadPreview" alt="Preview bukti">
                </label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal btn-modal-batal" id="btnBatal">
                    <span>Batal</span>
                </button>
                <button type="button" class="btn-modal btn-modal-kirim" id="btnKirim">
                    <span>Kirim Bukti</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('modalBayar');
            const modalBatas = document.getElementById('modalBatas');
            const modalNominal = document.getElementById('modalNominal');
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('buktiPembayaran');
            const uploadPreview = document.getElementById('uploadPreview');
            const btnBatal = document.getElementById('btnBatal');
            const btnKirim = document.getElementById('btnKirim');

            let currentTagihanId = null;

            // Format Rupiah
            function formatRupiah(angka) {
                return 'Rp ' + Number(angka).toLocaleString('id-ID');
            }

            // Buka modal saat tombol Bayar diklik
            document.querySelectorAll('.btn-bayar').forEach(btn => {
                btn.addEventListener('click', function () {
                    currentTagihanId = this.dataset.id;
                    const jatuhTempo = this.dataset.jatuhTempo;
                    const nominal = this.dataset.nominal;

                    modalBatas.textContent = 'Batas bayar ' + jatuhTempo;
                    modalNominal.textContent = formatRupiah(nominal);

                    // Reset upload
                    fileInput.value = '';
                    uploadArea.classList.remove('has-file');
                    uploadPreview.src = '';

                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden'; // cegah scroll background
                });
            });

            // Tutup modal
            function closeModal() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                currentTagihanId = null;
            }

            btnBatal.addEventListener('click', closeModal);

            // Klik di luar modal (overlay) juga tutup
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            // Preview gambar saat dipilih
            fileInput.addEventListener('change', function () {
                const file = this.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        uploadPreview.src = e.target.result;
                        uploadArea.classList.add('has-file');
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Tombol Kirim Bukti
            btnKirim.addEventListener('click', function () {
                if (!fileInput.files.length) {
                    alert('Silakan upload bukti pembayaran terlebih dahulu.');
                    return;
                }

                // Di sini bisa diganti dengan AJAX / form submit ke server
                // Untuk sementara: simulasi sukses
                alert('Bukti pembayaran berhasil dikirim!\nTagihan ID: ' + currentTagihanId);
                closeModal();

                // Contoh: jika nanti mau redirect
                // window.location.href = '/?page=penghuni-bayar&id=' + currentTagihanId;
            });

            // Tutup dengan tombol Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>