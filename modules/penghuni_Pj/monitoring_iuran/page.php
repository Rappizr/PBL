<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Monitoring Iuran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body>
    <div class="flex width-[19.1rem] height-[30.5rem] p-[1.5rem] flex-col align-start gap-[1.5rem] rounded-xl bg-[#F3CA81] shadow-[0_4px_9px_0_rgba(0,0,0,0.25)]">
        <p class="text-[#155560] font-inter text-base font-bold leading-normal">Bayar Tagihan</p>
        <div class="flex flex-col justify-center items-center gap-[0.5625rem] self-stretch p-[0.5rem_0.375rem] rounded-xl bg-white shadow-[2px_2px_0_0_rgba(0,0,0,0.25)]">
            <span class="text-[#4E545C] font-inter text-[0.8125rem] font-semibold leading-[normal]">Batas bayar 11-10-26</span>
            <span class="text-[#155560] font-inter text-base font-bold leading-[normal]">Rp 400.000</span>
        </div>
        <div class="flex flex-col items-start gap-1.5 self-stretch">
            <div class="text-[#4E545C] font-inter text-[0.8125rem] font-semibold leading-[normal]">
                Transfer ke rekening berikut:
            </div>

            <div class="flex p-2.5 justify-center items-center gap-2.5 self-stretch rounded-xl bg-white shadow-[2px_2px_0_0_rgba(0,0,0,0.25)] text-[#4E545C] font-inter text-[0.8125rem] font-semibold leading-[normal]">
                BCA 12345678 a.n Bapak Kos
            </div>
        </div>
        <div class="flex flex-col items-start gap-1.5 self-stretch">
            <div class="text-[#4E545C] font-inter text-[0.8125rem] font-semibold leading-[normal]">
                Upload Bukti Pembayaran
            </div>
            <div class="flex h-[11.625rem] flex-col justify-center items-center gap-3 self-stretch rounded-xl border-2 border-dashed border-[#4CA5B0] bg-white shadow-[2px_2px_0_0_rgba(0,0,0,0.25)]">
                <!-- Icon -->
                <div class="w-[2.625rem] h-[2.625rem] shrink-0 aspect-square">
                    <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 42 42" fill="none">
                        <path d="M16.4062 13.0306H19.4373V26.9062C19.4373 27.0866 19.585 27.2343 19.7654 27.2343H22.2264C22.4068 27.2343 22.5545 27.0866 22.5545 26.9062V13.0306H25.5938C25.8686 13.0306 26.0203 12.7148 25.8521 12.5015L21.2584 6.68547C21.2277 6.64625 21.1885 6.61453 21.1437 6.59272C21.0989 6.57091 21.0498 6.55957 21 6.55957C20.9502 6.55957 20.9011 6.57091 20.8563 6.59272C20.8115 6.61453 20.7723 6.64625 20.7416 6.68547L16.1479 12.4974C15.9797 12.7148 16.1314 13.0306 16.4062 13.0306ZM36.0117 25.6757H33.5508C33.3703 25.6757 33.2227 25.8234 33.2227 26.0038V32.3202H8.77734V26.0038C8.77734 25.8234 8.62969 25.6757 8.44922 25.6757H5.98828C5.80781 25.6757 5.66016 25.8234 5.66016 26.0038V34.1249C5.66016 34.8509 6.24668 35.4374 6.97266 35.4374H35.0273C35.7533 35.4374 36.3398 34.8509 36.3398 34.1249V26.0038C36.3398 25.8234 36.1922 25.6757 36.0117 25.6757Z" fill="#4CA5B0" />
                    </svg>
                </div>

                <!-- Label -->
                <p class="text-[#4CA5B0] text-center font-inter text-[0.625rem] font-normal leading-[normal]">
                    Ketuk untuk pilih foto
                </p>
            </div>
        </div>
        <div class="flex items-center gap-8 self-stretch">
            <!-- Button 1 (outlined/secondary) -->
            <div class="flex py-2 px-1 justify-center items-center gap-2.5 flex-1 basis-0 rounded-xl bg-white shadow-[4px_4px_0_0_rgba(0,0,0,0.25)]">
                <span class="text-[#4CA5B0] text-center font-inter text-[0.8125rem] font-bold leading-[normal]">
                    Batal
                </span>
            </div>

            <!-- Button 2 (filled/primary) -->
            <div class="flex py-2 px-1 justify-center items-center gap-2.5 flex-1 basis-0 rounded-xl bg-[#4CA5B0] shadow-[4px_4px_0_0_rgba(0,0,0,0.25)]">
                <span class="text-white text-center font-inter text-[0.8125rem] font-bold leading-[normal]">
                    Kirim Bukti
                </span>
            </div>
        </div>
    </div>
</body>

</html>