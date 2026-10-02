<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Analisis Kas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>

<body class="dashboard-body">
    <div class="mobile-container">
        <header class="dashboard-header">
            <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>
            <div class="kotak-kapsul-pemilik">
                <span class="lingkaran-user-abu">
                    <i class="fa-solid fa-user"></i>
                </span>
                <span class="tulisan-nama">Bapak Kos</span>
            </div>
        </header>

        <main class="analisis-container">

            <div class="banner-card">
                <h2 class="banner-title">Analisis Kas</h2>
                <a href="/?page=dashboard_pemilik" class="btn-kembali">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="kas-action-row">
                <a href="/?page=tambah-pengeluaran" class="btn-tambah-pengeluaran">
                    <i class="fa-solid fa-plus"></i> Tambah Pengeluaran
                </a>
                <div class="saldo-box">
                    <i class="fa-regular fa-credit-card"></i>
                    Rp. 1.253.500
                </div>
            </div>

            <div class="card-list">

                <div class="transaksi-card">
                    <div class="transaksi-icon">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <div class="transaksi-info">
                        <span class="transaksi-nama">Pembelian alat kebersihan</span>
                        <span class="transaksi-tanggal">2026-09-07 08:00</span>
                    </div>
                    <span class="transaksi-nominal-pill status-merah">Rp. -150.000</span>
                </div>

                <div class="transaksi-card">

                    <div class="transaksi-icon">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <div class="transaksi-info">
                        <span class="transaksi-nama">Pembelian alat kebersihan</span>
                        <span class="transaksi-tanggal">2026-09-07 08:00</span>
                    </div>
                    <span class="transaksi-nominal-pill status-hijau">Rp. -150.000</span>
                </div>

                <div class="transaksi-card">
                    <div class="transaksi-icon">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <div class="transaksi-info">
                        <span class="transaksi-nama">Pembelian alat kebersihan</span>
                        <span class="transaksi-tanggal">2026-09-07 08:00</span>
                    </div>
                    <span class="transaksi-nominal-pill status-merah">Rp. -150.000</span>
                </div>
        </main>
        <?php require_once __DIR__ . '/../../components/footer.php'; ?>
    </div>
</body>

</html>