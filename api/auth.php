<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/shared/handler/sewa_reminder_helper.php';
require_once __DIR__ . '/../modules/shared/handler/iuran_reminder_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=login');
    exit;
}

$pin           = preg_replace('/[^0-9]/', '', trim((string)($_POST['pin'] ?? '')));
$selected_role = trim((string)($_POST['role'] ?? 'penghuni'));
if (!in_array($selected_role, ['pemilik', 'penghuni'], true)) {
    $selected_role = 'penghuni';
}

if ($selected_role === 'pemilik') {
    if ($pin === '') {
        $_SESSION['login_error'] = 'PIN Pemilik wajib diisi!';
    } elseif (strlen($pin) !== 6) {
        $_SESSION['login_error'] = 'PIN Pemilik harus 6 digit angka!';
    } else {
        try {
            $stmtPemilik = $pdo->prepare("
                SELECT id, nama, email, no_wa, no_rekening, pin 
                FROM pemilik 
                WHERE TRIM(pin) = :pin 
                ORDER BY id ASC 
                LIMIT 1
            ");
            $stmtPemilik->execute([':pin' => $pin]);
            $data_pemilik = $stmtPemilik->fetch(PDO::FETCH_ASSOC);

            if (!$data_pemilik) {
                $_SESSION['login_error'] = 'PIN Pemilik salah!';
            } else {
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id'          => (int)$data_pemilik['id'],
                    'nama'        => $data_pemilik['nama'] ?? 'Bapak Kos',
                    'email'       => $data_pemilik['email'] ?? '',
                    'no_wa'       => $data_pemilik['no_wa'] ?? '',
                    'no_rekening' => $data_pemilik['no_rekening'] ?? '',
                    'role'        => 'pemilik',
                    'kamar'       => null,
                    'is_pj'       => false
                ];
                header('Location: /?page=dashboard_pemilik');
                exit;
            }
        } catch (PDOException $e) {
            error_log('[auth_pemilik] ' . $e->getMessage());
            $_SESSION['login_error'] = 'Terjadi gangguan koneksi database. Silakan coba lagi.';
        }
    }
} else {
    $raw_kamar = preg_replace('/[^0-9]/', '', trim((string)($_POST['number'] ?? $_POST['no_kamar'] ?? $_POST['kamar'] ?? '')));

    if ($raw_kamar === '' || $pin === '') {
        $_SESSION['login_error'] = 'Nomor Kamar dan PIN wajib diisi!';
    } elseif (strlen($pin) !== 6) {
        $_SESSION['login_error'] = 'PIN harus tepat 6 digit angka!';
    } else {
        $clean_kamar = ltrim($raw_kamar, '0');
        if ($clean_kamar === '') {
            $clean_kamar = '0';
        }
        $padded_kamar = str_pad($clean_kamar, 2, '0', STR_PAD_LEFT);

        try {
            $stmt = $pdo->prepare("
                SELECT
                    p.id AS penghuni_id,
                    p.nama,
                    p.is_ketua_kos,
                    p.status_akun,
                    k.id AS kamar_id,
                    k.no_kamar,
                    pk.id AS penghuni_kamar_id
                FROM kamar k
                INNER JOIN penghuni_kamar pk ON pk.kamar_id = k.id
                INNER JOIN penghuni p ON p.id = pk.penghuni_id
                WHERE k.no_kamar IN (:raw_kamar, :clean_kamar, :padded_kamar)
                  AND LOWER(TRIM(k.status_kamar)) = 'terisi'
                  AND TRIM(p.pin) = :pin
                ORDER BY pk.id DESC
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
            } else {
                $penghuniId = (int)$penghuni['penghuni_id'];

                evaluasiStatusSewaPenghuni($pdo, $penghuniId);
                evaluasiStatusIuranPenghuni($pdo, $penghuniId);

                $stmtStatus = $pdo->prepare("SELECT LOWER(TRIM(COALESCE(status_akun, 'aktif'))) FROM penghuni WHERE id = :pid");
                $stmtStatus->execute([':pid' => $penghuniId]);
                $statusAkun = $stmtStatus->fetchColumn() ?: 'aktif';

                if (!in_array($statusAkun, ['aktif', 'freeze'], true)) {
                    $_SESSION['login_error'] = 'Akun penghuni sedang tidak aktif!';
                } else {
                    session_regenerate_id(true);

                    $is_ketua = !empty($penghuni['is_ketua_kos']);

                    $_SESSION['user'] = [
                        'id'                => $penghuniId,
                        'nama'              => $penghuni['nama'],
                        'role'              => 'penghuni',
                        'status_akun'       => $statusAkun,
                        'is_pj'             => $is_ketua,
                        'is_ketua_kos'      => $is_ketua,
                        'kamar'             => $penghuni['no_kamar'],
                        'no_kamar'          => $penghuni['no_kamar'],
                        'kamar_id'          => (int)$penghuni['kamar_id'],
                        'penghuni_kamar_id' => (int)$penghuni['penghuni_kamar_id'],
                    ];

                    if ($statusAkun === 'freeze') {
                        header('Location: /?page=akun_freeze');
                    } else {
                        header('Location: /?page=dashboard_penghuni');
                    }
                    exit;
                }
            }
        } catch (PDOException $e) {
            error_log('[auth_penghuni] ' . $e->getMessage());
            $_SESSION['login_error'] = 'Terjadi gangguan koneksi database. Silakan coba lagi.';
        }
    }
}

header('Location: /?page=login&role=' . urlencode($selected_role));
exit;