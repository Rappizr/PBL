<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin           = trim($_POST['pin'] ?? '');
    $selected_role = trim($_POST['role'] ?? 'penghuni');

    if ($selected_role === 'pemilik') {
        if ($pin === '') {
            $_SESSION['login_error'] = 'PIN Pemilik wajib diisi!';
        } else {
            $stmtPemilik = $pdo->query("SELECT * FROM pemilik LIMIT 1");
            $data_pemilik = $stmtPemilik ? $stmtPemilik->fetch(PDO::FETCH_ASSOC) : null;

            if (!$data_pemilik || $pin !== trim((string)$data_pemilik['pin'])) {
                $_SESSION['login_error'] = 'PIN Pemilik salah!';
            } else {
                // Regenerasi ID sesi untuk mencegah session fixation / tumpang tindih akun
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id'    => $data_pemilik['id'],
                    'nama'  => $data_pemilik['nama'] ?? 'Bapak Kos',
                    'role'  => 'pemilik',
                    'kamar' => null,
                    'is_pj' => false
                ];
                header('Location: /?page=dashboard_pemilik');
                exit;
            }
        }
    } else {
        $raw_kamar = trim((string)($_POST['number'] ?? $_POST['no_kamar'] ?? $_POST['kamar'] ?? ''));

        if ($raw_kamar === '' || $pin === '') {
            $_SESSION['login_error'] = 'Nomor Kamar dan PIN wajib diisi!';
        } else {
            $clean_kamar  = ltrim($raw_kamar, '0');
            if ($clean_kamar === '') {
                $clean_kamar = '0';
            }
            $padded_kamar = str_pad($clean_kamar, 2, '0', STR_PAD_LEFT);

            try {
                $stmt = $pdo->prepare("
                    SELECT
                        p.id AS penghuni_id,
                        p.nama,
                        p.pin,
                        p.is_ketua_kos,
                        p.status_akun,
                        k.id AS kamar_id,
                        k.no_kamar,
                        pk.id AS penghuni_kamar_id
                    FROM kamar k
                    INNER JOIN penghuni_kamar pk ON pk.kamar_id = k.id
                    INNER JOIN penghuni p ON p.id = pk.penghuni_id
                    WHERE (k.no_kamar = :raw_kamar OR k.no_kamar = :clean_kamar OR k.no_kamar = :padded_kamar)
                      AND p.pin = :pin
                    LIMIT 1
                ");

                $stmt->execute([
                    ':raw_kamar'    => $raw_kamar,
                    ':clean_kamar'  => $clean_kamar,
                    ':padded_kamar' => $padded_kamar,
                    ':pin'          => $pin,
                ]);

                $penghuni = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$penghuni) {
                    $_SESSION['login_error'] = 'Nomor Kamar atau PIN tidak sesuai!';
                } elseif (isset($penghuni['status_akun']) && strtolower(trim($penghuni['status_akun'])) !== 'aktif') {
                    $_SESSION['login_error'] = 'Akun penghuni sedang tidak aktif!';
                } else {
                    // Regenerasi ID sesi agar hak akses bersih
                    session_regenerate_id(true);

                    $is_ketua = !empty($penghuni['is_ketua_kos']);

                    $_SESSION['user'] = [
                        'id'                => $penghuni['penghuni_id'],
                        'nama'              => $penghuni['nama'],
                        'role'              => 'penghuni',
                        'is_pj'             => $is_ketua,
                        'is_ketua_kos'      => $is_ketua,
                        'kamar'             => $penghuni['no_kamar'],      // Kunci untuk komponen header
                        'no_kamar'          => $penghuni['no_kamar'],      // Fallback
                        'kamar_id'          => $penghuni['kamar_id'],
                        'penghuni_kamar_id' => $penghuni['penghuni_kamar_id'],
                    ];
                    header('Location: /?page=dashboard_penghuni');
                    exit;
                }
            } catch (PDOException $e) {
                $_SESSION['login_error'] = 'Terjadi kesalahan sistem database: ' . $e->getMessage();
            }
        }
    }

    if (isset($_SESSION['login_error'])) {
        header('Location: /?page=login&role=' . urlencode($selected_role));
        exit;
    }
}

header('Location: /?page=login');
exit;