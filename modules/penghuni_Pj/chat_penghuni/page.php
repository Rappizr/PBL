<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nama penghuni dari session atau dummy
// $nama_penghuni = $_SESSION['user']['nama'];
$nama_penghuni = "Mas Rafi";

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Chat Penghuni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="flex w-[23.125rem] h-[55.75rem] py-11 px-6 flex-col justify-between items-center shrink-0">
    <div class="flex flex-col items-start gap-11 self-stretch">
        
    </div>
    <?php require_once __DIR__ . '/../../components/footer.php'; ?>
</body>
</html>