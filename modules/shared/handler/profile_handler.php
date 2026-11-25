<?php
require_once __DIR__ . '/../../../config/init.php';
/** @var PDO $pdo */

$role_user = $role_user ?? ($_SESSION['user']['role'] ?? 'pemilik');
$user_id   = $user_id   ?? (int)($_SESSION['user']['id'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Ubah Password / PIN
    if ($action === 'ubah_password') {
        $password_lama = trim($_POST['password_lama'] ?? '');
        $password_baru = trim($_POST['password_baru'] ?? '');
        $konfirmasi    = trim($_POST['password_konfirmasi'] ?? '');

        if ($password_baru !== $konfirmasi) {
            $_SESSION['pesan_error'] = 'Konfirmasi PIN baru tidak cocok.';
        } elseif (!preg_match('/^[0-9]{6}$/', $password_baru)) {
            $_SESSION['pesan_error'] = 'PIN harus terdiri dari tepat 6 digit angka.';
        } else {
            if ($role_user === 'pemilik') {
                $stmtCek = $pdo->prepare("SELECT id, pin FROM pemilik WHERE id = :id LIMIT 1");
                $stmtCek->execute([':id' => $user_id]);
                $currentPemilik = $stmtCek->fetch(PDO::FETCH_ASSOC);

                if (!$currentPemilik) {
                    $stmtCek = $pdo->query("SELECT id, pin FROM pemilik ORDER BY id ASC LIMIT 1");
                    $currentPemilik = $stmtCek->fetch(PDO::FETCH_ASSOC);
                    if ($currentPemilik) {
                        $user_id = (int)$currentPemilik['id'];
                    }
                }

                $pinDatabase = trim((string)($currentPemilik['pin'] ?? ''));

                if ($pinDatabase !== $password_lama) {
                    $_SESSION['pesan_error'] = 'PIN lama yang Anda masukkan salah.';
                } else {
                    $updateStmt = $pdo->prepare("UPDATE pemilik SET pin = :pin WHERE id = :id");
                    $updateStmt->execute([':pin' => $password_baru, ':id' => $user_id]);

                    $_SESSION['user']['id']  = $user_id;
                    $_SESSION['user']['pin'] = $password_baru;
                    $_SESSION['pesan_sukses'] = 'PIN akun berhasil diperbarui!';
                }
            } else {
                $stmtCek = $pdo->prepare("SELECT id, pin FROM penghuni WHERE id = :id LIMIT 1");
                $stmtCek->execute([':id' => $user_id]);
                $currentPenghuni = $stmtCek->fetch(PDO::FETCH_ASSOC);

                $pinDatabase = trim((string)($currentPenghuni['pin'] ?? ''));

                if ($pinDatabase !== $password_lama) {
                    $_SESSION['pesan_error'] = 'PIN lama yang Anda masukkan salah.';
                } else {
                    $updateStmt = $pdo->prepare("UPDATE penghuni SET pin = :pin WHERE id = :id");
                    $updateStmt->execute([':pin' => $password_baru, ':id' => $user_id]);

                    $_SESSION['user']['pin'] = $password_baru;
                    $_SESSION['pesan_sukses'] = 'PIN akun berhasil diperbarui!';
                }
            }
        }

        header('Location: /?page=profile');
        exit;
    }

    // 2. Ubah Email
    if ($action === 'ubah_email') {
        $email_baru          = strtolower(trim($_POST['email_baru'] ?? ''));
        $password_verifikasi = trim($_POST['password_verifikasi'] ?? '');

        if (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['pesan_error'] = 'Format email baru tidak valid.';
        } else {
            if ($role_user === 'pemilik') {
                $stmt = $pdo->prepare("SELECT pin FROM pemilik WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $user_id]);
                $pinDatabase = trim((string)$stmt->fetchColumn());

                if ($pinDatabase !== $password_verifikasi) {
                    $_SESSION['pesan_error'] = 'Konfirmasi PIN salah. Perubahan email dibatalkan.';
                } else {
                    try {
                        $updateStmt = $pdo->prepare("UPDATE pemilik SET email = :email WHERE id = :id");
                        $updateStmt->execute([':email' => $email_baru, ':id' => $user_id]);
                        $_SESSION['user']['email'] = $email_baru;
                        $_SESSION['pesan_sukses'] = 'Email akun berhasil diperbarui!';
                    } catch (PDOException $e) {
                        $_SESSION['pesan_error'] = 'Email tersebut sudah digunakan oleh akun lain.';
                    }
                }
            } else {
                $stmt = $pdo->prepare("SELECT pin FROM penghuni WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $user_id]);
                $pinDatabase = trim((string)$stmt->fetchColumn());

                if ($pinDatabase !== $password_verifikasi) {
                    $_SESSION['pesan_error'] = 'Konfirmasi PIN salah. Perubahan email dibatalkan.';
                } else {
                    try {
                        $updateStmt = $pdo->prepare("UPDATE penghuni SET email = :email WHERE id = :id");
                        $updateStmt->execute([':email' => $email_baru, ':id' => $user_id]);
                        $_SESSION['user']['email'] = $email_baru;
                        $_SESSION['pesan_sukses'] = 'Email akun berhasil diperbarui!';
                    } catch (PDOException $e) {
                        $_SESSION['pesan_error'] = 'Email tersebut sudah digunakan oleh akun lain.';
                    }
                }
            }
        }

        header('Location: /?page=profile');
        exit;
    }

    // 3. Ubah Data Profil
    if ($action === 'ubah_profil') {
        $nama_baru        = trim($_POST['nama'] ?? '');
        $no_wa_baru       = preg_replace('/[^0-9]/', '', trim($_POST['no_wa'] ?? ''));
        $kontak_wali_baru = preg_replace('/[^0-9]/', '', trim($_POST['kontak_wali'] ?? ''));
        $nama_bank_baru   = strtoupper(trim(preg_replace('/[^a-zA-Z0-9\s\-]/', '', $_POST['nama_bank'] ?? '')));
        $no_rekening_baru = preg_replace('/[^0-9]/', '', trim($_POST['no_rekening'] ?? ''));

        if ($nama_baru === '') {
            $_SESSION['pesan_error'] = 'Nama lengkap tidak boleh kosong.';
        } elseif (!preg_match("/^[a-zA-Z\s'.]+$/", $nama_baru)) {
            $_SESSION['pesan_error'] = 'Nama lengkap hanya boleh berisi huruf, spasi, tanda petik, atau titik (tidak boleh ada angka).';
        } else {
            $nama_baru = ucwords(strtolower($nama_baru));

            if ($role_user === 'pemilik') {
                try {
                    $updateStmt = $pdo->prepare("
                        UPDATE pemilik 
                        SET nama = :nama, 
                            no_wa = :no_wa, 
                            nama_bank = :nama_bank, 
                            no_rekening = :no_rekening 
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        ':nama'        => $nama_baru,
                        ':no_wa'       => $no_wa_baru,
                        ':nama_bank'   => ($nama_bank_baru !== '') ? $nama_bank_baru : null,
                        ':no_rekening' => ($no_rekening_baru !== '') ? $no_rekening_baru : null,
                        ':id'          => $user_id
                    ]);
                } catch (PDOException $e) {
                    $updateStmt = $pdo->prepare("
                        UPDATE pemilik 
                        SET nama = :nama, 
                            no_wa = :no_wa, 
                            no_rekening = :no_rekening 
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        ':nama'        => $nama_baru,
                        ':no_wa'       => $no_wa_baru,
                        ':no_rekening' => ($no_rekening_baru !== '') ? $no_rekening_baru : null,
                        ':id'          => $user_id
                    ]);
                }

                $_SESSION['user']['nama']        = $nama_baru;
                $_SESSION['user']['no_wa']       = $no_wa_baru;
                $_SESSION['user']['nama_bank']   = ($nama_bank_baru !== '') ? $nama_bank_baru : null;
                $_SESSION['user']['no_rekening'] = ($no_rekening_baru !== '') ? $no_rekening_baru : null;
                $_SESSION['pesan_sukses']        = 'Data profil pemilik berhasil diperbarui!';
            } else {
                // Verifikasi status Ketua Kos langsung dari database agar penghuni biasa tidak bisa menyimpan rekening
                $stmtCekKetua = $pdo->prepare("SELECT is_ketua_kos FROM penghuni WHERE id = :id LIMIT 1");
                $stmtCekKetua->execute([':id' => $user_id]);
                $isKetuaDb = (bool)$stmtCekKetua->fetchColumn();

                if ($isKetuaDb) {
                    if ($nama_bank_baru === '' || $no_rekening_baru === '') {
                        $_SESSION['pesan_error'] = 'Ketua Kos wajib mengisi Nama Bank dan Nomor Rekening kas iuran.';
                        header('Location: /?page=profile&modal=ubah_profil');
                        exit;
                    }

                    try {
                        $updateStmt = $pdo->prepare("
                            UPDATE penghuni 
                            SET nama = :nama, 
                                no_wa = :no_wa, 
                                kontak_wali = :kontak_wali,
                                nama_bank = :nama_bank,
                                no_rekening = :no_rekening
                            WHERE id = :id
                        ");
                        $updateStmt->execute([
                            ':nama'        => $nama_baru,
                            ':no_wa'       => $no_wa_baru,
                            ':kontak_wali' => ($kontak_wali_baru !== '') ? $kontak_wali_baru : null,
                            ':nama_bank'   => $nama_bank_baru,
                            ':no_rekening' => $no_rekening_baru,
                            ':id'          => $user_id
                        ]);
                    } catch (PDOException $e) {
                        $updateStmt = $pdo->prepare("
                            UPDATE penghuni 
                            SET nama = :nama, 
                                no_wa = :no_wa, 
                                kontak_wali = :kontak_wali
                            WHERE id = :id
                        ");
                        $updateStmt->execute([
                            ':nama'        => $nama_baru,
                            ':no_wa'       => $no_wa_baru,
                            ':kontak_wali' => ($kontak_wali_baru !== '') ? $kontak_wali_baru : null,
                            ':id'          => $user_id
                        ]);
                    }

                    $_SESSION['user']['nama']        = $nama_baru;
                    $_SESSION['user']['no_wa']       = $no_wa_baru;
                    $_SESSION['user']['kontak_wali'] = $kontak_wali_baru;
                    $_SESSION['user']['nama_bank']   = $nama_bank_baru;
                    $_SESSION['user']['no_rekening'] = $no_rekening_baru;
                    $_SESSION['pesan_sukses']        = 'Data profil & rekening Ketua Kos berhasil diperbarui!';
                } else {
                    $updateStmt = $pdo->prepare("
                        UPDATE penghuni 
                        SET nama = :nama, 
                            no_wa = :no_wa, 
                            kontak_wali = :kontak_wali 
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        ':nama'        => $nama_baru,
                        ':no_wa'       => $no_wa_baru,
                        ':kontak_wali' => ($kontak_wali_baru !== '') ? $kontak_wali_baru : null,
                        ':id'          => $user_id
                    ]);

                    $_SESSION['user']['nama']        = $nama_baru;
                    $_SESSION['user']['no_wa']       = $no_wa_baru;
                    $_SESSION['user']['kontak_wali'] = $kontak_wali_baru;
                    $_SESSION['pesan_sukses']        = 'Data profil penghuni berhasil diperbarui!';
                }
            }
        }

        header('Location: /?page=profile');
        exit;
    }
}