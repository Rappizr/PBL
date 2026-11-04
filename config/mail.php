<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function kirim_email_otp(string $email_tujuan, string $nama_tujuan, string $kode_otp): bool
{
    $mail = new PHPMailer(true);

    try {
        // Konfigurasi SMTP Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sitemankos@gmail.com';
        $mail->Password   = 'wjhazhuiipdqpetb'; 
        
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

        // Konten Email
            $mail->isHTML(true);
            $mail->Subject = 'Kode Verifikasi Ubah PIN - SITEMAN KOS';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; background-color: #f7f9fa; padding: 24px;'>
                    <div style='max-width: 450px; background-color: #ffffff; border-radius: 12px; padding: 24px; margin: auto; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                        <h2 style='color: #4ca5b0; margin-top: 0;'>SITEMAN - KOS</h2>
                        <p style='color: #333333;'>Halo <strong>{$nama_tujuan}</strong>,</p>
                        <p style='color: #555555;'>Anda menerima pesan ini karena ada permintaan untuk mengatur ulang PIN akun Anda.</p>
                        <div style='background-color: #e8f6f8; border-radius: 8px; padding: 14px; text-align: center; margin: 20px 0;'>
                            <span style='font-size: 28px; font-weight: bold; letter-spacing: 5px; color: #1a4d55;'>{$kode_otp}</span>
                        </div>
                        <p style='color: #777777; font-size: 13px;'>Kode ini hanya berlaku selama 5 menit. Jangan berikan kode ini kepada siapa pun.</p>
                    </div>
                </div>
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