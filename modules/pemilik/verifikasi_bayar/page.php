<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Verifikasi Pembayaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="verifikasi-body">
    <div class="mobile-container">
        <div>
            <!-- Header Modular -->
            <?php require_once __DIR__ . '/../../components/header.php'; ?>

            <main class="verifikasi-container">
                <!-- Banner Judul -->
                <div class="banner-card">
                    <h2 class="banner-title">Verifikasi Pembayaran</h2>
                    <a href="/?page=dashboard_pemilik" class="btn-kembali">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card-list">
                    <!-- Item 1: Ridwan -->
                    <div class="verifikasi-card">
                        <div class="verifikasi-card-body">
                            <div class="bukti-wrapper">
                                <img src="https://picsum.photos/200" alt="Bukti Transfer" class="bukti-img">
                                <a href="#" class="btn-zoom">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                </a>
                            </div>
                            <div class="detail-wrapper">
                                <div class="user-info-row">
                                    <span class="nama-penghuni">Ridwan</span>
                                    <span class="badge-kamar">Kamar 2</span>
                                </div>
                                <div class="nominal-text">Rp. 75.000</div>
                                <div class="metode-tanggal">Transfer ~ 2026-09-07 08:00</div>
                            </div>
                        </div>
                        <div class="verifikasi-card-footer">
                            <a href="#" class="btn-action btn-chat">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                            <button type="button" class="btn-action btn-decline">
                                <i class="fa-solid fa-circle-xmark"></i> Tolak
                            </button>
                            <button type="button" class="btn-action btn-approve">
                                <i class="fa-solid fa-circle-check"></i> Terima
                            </button>
                        </div>
                    </div>

                    <!-- Item 2: Raffi -->
                    <div class="verifikasi-card">
                        <div class="verifikasi-card-body">
                            <div class="bukti-wrapper">
                                <img src="https://picsum.photos/201" alt="Bukti Transfer" class="bukti-img">
                                <a href="#" class="btn-zoom">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                </a>
                            </div>
                            <div class="detail-wrapper">
                                <div class="user-info-row">
                                    <span class="nama-penghuni">Raffi</span>
                                    <span class="badge-kamar">Kamar 5</span>
                                </div>
                                <div class="nominal-text">Rp. 75.000</div>
                                <div class="metode-tanggal">Transfer ~ 2026-10-17 18:00</div>
                            </div>
                        </div>
                        <div class="verifikasi-card-footer">
                            <a href="#" class="btn-action btn-chat">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                            <button type="button" class="btn-action btn-decline">
                                <i class="fa-solid fa-circle-xmark"></i> Tolak
                            </button>
                            <button type="button" class="btn-action btn-approve">
                                <i class="fa-solid fa-circle-check"></i> Terima
                            </button>
                        </div>
                    </div>

                    <!-- Item 3: Rofiq -->
                    <div class="verifikasi-card">
                        <div class="verifikasi-card-body">
                            <div class="bukti-wrapper">
                                <img src="https://picsum.photos/202" alt="Bukti Transfer" class="bukti-img">
                                <a href="#" class="btn-zoom">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                </a>
                            </div>
                            <div class="detail-wrapper">
                                <div class="user-info-row">
                                    <span class="nama-penghuni">Rofiq</span>
                                    <span class="badge-kamar">Kamar 3</span>
                                </div>
                                <div class="nominal-text">Rp. 75.000</div>
                                <div class="metode-tanggal">Transfer ~ 2026-07-05 06:00</div>
                            </div>
                        </div>
                        <div class="verifikasi-card-footer">
                            <a href="#" class="btn-action btn-chat">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                            <button type="button" class="btn-action btn-decline">
                                <i class="fa-solid fa-circle-xmark"></i> Tolak
                            </button>
                            <button type="button" class="btn-action btn-approve">
                                <i class="fa-solid fa-circle-check"></i> Terima
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Footer Modular -->
        <?php require_once __DIR__ . '/../../components/footer.php'; ?>
    </div>
</body>
</html>