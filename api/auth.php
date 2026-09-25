<?php
/**
 * Login handler — POST /api/auth
 */
session_start();

require_once __DIR__ . '/../config/database.php';

$data_pemilik = $pdo->query("SELECT * FROM pemilik LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin           = trim($_POST['pin'] ?? '');
    $selected_role = $_POST['role'] ?? 'penghuni';

    if ($selected_role === 'pemilik') {
        if ($pin === '') {
            $_SESSION['login_error'] = 'PIN Pemilik wajib diisi!';
        } elseif (!$data_pemilik || $pin !== $data_pemilik['pin']) {
            $_SESSION['login_error'] = 'PIN Pemilik salah!';
        } else {
            $_SESSION['user'] = [
                'id'   => $data_pemilik['id'],
                'nama' => $data_pemilik['nama'] ?? 'Bapak Kos',
                'role' => 'pemilik',
            ];
            header('Location: /?page=dashboard_pemilik');
            exit;
        }
    } else {
        // Login penghuni
        $no_kamar = trim($_POST['number'] ?? '');

        if ($no_kamar === '' || $pin === '') {
            $_SESSION['login_error'] = 'Nomor Kamar dan PIN wajib diisi!';
        } else {
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
                WHERE k.no_kamar = :no_kamar
                  AND p.pin = :pin
                LIMIT 1
            ");

            $stmt->execute([
                ':no_kamar' => $no_kamar,
                ':pin'      => $pin,
            ]);

            $penghuni = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$penghuni) {
                $_SESSION['login_error'] = 'Nomor Kamar atau PIN tidak sesuai / kamar tidak aktif!';
            } else {
                $_SESSION['user'] = [
                    'id'                => $penghuni['penghuni_id'],
                    'is_ketua_kos'      => $penghuni['is_ketua_kos'],
                    'nama'              => $penghuni['nama'],
                    'role'              => 'penghuni',
                    'kamar_id'          => $penghuni['kamar_id'],
                    'no_kamar'          => $penghuni['no_kamar'],
                    'penghuni_kamar_id' => $penghuni['penghuni_kamar_id'],
                ];
                header('Location: /?page=dashboard_penghuni');
                exit;
            }
        }
    }

    // Error → back to login
    if (isset($_SESSION['login_error'])) {
        header('Location: /?page=login&role=' . urlencode($selected_role));
        exit;
    }
}

// Fallback
header('Location: /?page=login');
exit;
