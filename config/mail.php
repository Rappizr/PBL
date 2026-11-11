<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../misc/loadenv.php';

function kirim_email_otp(string $email_tujuan, string $nama_tujuan, string $kode_otp): bool
{
    $mail = new PHPMailer(true);

    try {
        // Konfigurasi SMTP Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('EMAIL_USERNAME') ?: ($_ENV['EMAIL_USERNAME'] ?? null);
        $mail->Password   = getenv('EMAIL_PASSWORD') ?: ($_ENV['EMAIL_PASSWORD'] ?? null);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];

        // Pengirim & Penerima
        $mail->setFrom('sitemankos@gmail.com', 'SITEMAN - KOS');
        $mail->addAddress($email_tujuan, $nama_tujuan);

        // Nomor Referensi Surat & Tanggal Realtime
        $no_surat = 'SK/OTP/' . date('Ymd') . '/' . strtoupper(bin2hex(random_bytes(3)));
        $tgl_surat = date('d F Y, H:i') . ' WIB';

        // Konten Email Berbentuk Surat Resmi
        $mail->isHTML(true);
        $mail->Subject = 'SURAT PEMBERITAHUAN: Kode Autentikasi Reset PIN (' . $kode_otp . ')';
        $mail->Body    = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Surat Pemberitahuan OTP</title>
        </head>
        <body style='margin: 0; padding: 30px 10px; background-color: #f1f5f9; font-family: \"Georgia\", serif; -webkit-font-smoothing: antialiased;'>
            <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 4px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;'>
                
                <!-- KOP SURAT RESMI -->
                <tr>
                    <td style='padding: 30px 36px 16px 36px; border-bottom: 3px double #4ca5b0;'>
                        <table width='100%' border='0' cellpadding='0' cellspacing='0'>
                            <tr>
                                <td style='vertical-align: middle;'>
                                    <h1 style='margin: 0; font-family: \"Arial\", sans-serif; font-size: 22px; font-weight: 900; letter-spacing: 2px; color: #1a4d55; text-transform: uppercase;'>SITEMAN - KOS</h1>
                                    <p style='margin: 4px 0 0 0; font-family: \"Arial\", sans-serif; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;'>Sistem Manajemen Hunian Kost Terpadu</p>
                                </td>
                                <td style='text-align: right; vertical-align: middle;'>
                                    <div style='display: inline-block; padding: 4px 12px; background-color: #f0fdfa; border: 1px solid #4ca5b0; border-radius: 20px; font-family: \"Arial\", sans-serif; font-size: 10px; font-weight: bold; color: #0d9488;'>
                                        DOKUMEN RAHASIA
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- BADAN SURAT -->
                <tr>
                    <td style='padding: 28px 36px;'>
                        <!-- Header Tanggal & Nomor -->
                        <table width='100%' border='0' cellpadding='0' cellspacing='0' style='font-family: \"Arial\", sans-serif; font-size: 12px; color: #475569; margin-bottom: 24px;'>
                            <tr>
                                <td><strong>No:</strong> {$no_surat}</td>
                                <td style='text-align: right;'>{$tgl_surat}</td>
                            </tr>
                            <tr>
                                <td colspan='2' style='padding-top: 4px;'><strong>Hal:</strong> Permintaan Kode Autentikasi Pengaturan Ulang PIN</td>
                            </tr>
                        </table>

                        <!-- Teks Pembuka -->
                        <p style='margin: 0 0 14px 0; font-size: 15px; color: #1e293b; line-height: 1.6;'>
                            Kepada Yth.<br>
                            <strong>Sdr/i. " . htmlspecialchars($nama_tujuan) . "</strong><br>
                            di Tempat
                        </p>

                        <p style='margin: 0 0 20px 0; font-size: 14px; color: #334155; line-height: 1.7; text-align: justify;'>
                            Sehubungan dengan adanya permohonan pengaturan ulang PIN akun Anda pada sistem <strong>SITEMAN-KOS</strong>, melalui surat elektronik ini kami sampaikan kode verifikasi (OTP) resmi untuk melanjutkan proses autentikasi keamanan:
                        </p>

                        <!-- KOTAK SEGEL KODE OTP -->
                        <div style='margin: 24px 0; padding: 18px; background-color: #f8fafc; border: 1.5px dashed #4ca5b0; border-radius: 8px; text-align: center;'>
                            <span style='display: block; font-family: \"Arial\", sans-serif; font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px;'>KODE VERIFIKASI (OTP)</span>
                            <span style='display: inline-block; font-family: \"Courier New\", monospace; font-size: 34px; font-weight: 900; letter-spacing: 8px; color: #164e63; padding: 6px 16px; background-color: #ffffff; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);'>{$kode_otp}</span>
                        </div>

                        <!-- PERINGATAN KETENTUAN SURAT -->
                        <div style='background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 14px; margin-bottom: 24px;'>
                            <p style='margin: 0; font-family: \"Arial\", sans-serif; font-size: 12px; color: #92400e; line-height: 1.5;'>
                                <strong>PENTING:</strong> Kode ini berlaku selama <strong>5 (lima) menit</strong> sejak surat ini diterbitkan. Mohon untuk tidak menyebarkan atau memberitahukan kode verifikasi ini kepada pihak mana pun demi menjaga keamanan hak akses akun Anda.
                            </p>
                        </div>

                        <p style='margin: 0 0 30px 0; font-size: 14px; color: #334155; line-height: 1.7;'>
                            Apabila Anda tidak merasa melakukan permintaan ini, abaikan pesan ini dan segera lakukan pemeriksaan berkala pada akun Anda.
                        </p>

                        <!-- TANDA TANGAN ELEKTRONIK -->
                        <table width='100%' border='0' cellpadding='0' cellspacing='0' style='font-family: \"Arial\", sans-serif;'>
                            <tr>
                                <td width='55%'></td>
                                <td style='text-align: left;'>
                                    <p style='margin: 0; font-size: 13px; color: #64748b;'>Hormat kami,</p>
                                    <p style='margin: 4px 0 28px 0; font-size: 13px; font-weight: bold; color: #0f172a;'>Pengelola SITEMAN - KOS</p>
                                    <p style='margin: 0; font-size: 11px; color: #94a3b8; font-style: italic;'>[Surat ini diterbitkan secara otomatis oleh sistem]</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- FOOTER RESMI -->
                <tr>
                    <td style='padding: 16px 36px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; border-radius: 0 0 4px 4px;'>
                        <p style='margin: 0; font-family: \"Arial\", sans-serif; font-size: 11px; color: #94a3b8;'>
                            &copy; " . date('Y') . " SITEMAN - KOS Management System. Seluruh hak cipta dilindungi undang-undang.
                        </p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['debug_mail_error'] = $mail->ErrorInfo;
        error_log("Gagal kirim email OTP: " . $mail->ErrorInfo);
        return false;
    }
}