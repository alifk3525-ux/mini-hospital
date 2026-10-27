<!DOCTYPE html>
<html>
<head>
    <title>Cetak Antrean - QR Code</title>
    <!-- Memanggil library pembuat QR Code dari internet -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            text-align: center;
            width: 80mm;
            margin: 0 auto;
            padding-top: 10px;
        }
        @page { margin: 0; }
        .nomor {
            font-size: 60px;
            font-weight: bold;
            margin: 10px 0;
        }
        .garis {
            border-top: 2px dashed black;
            margin: 10px 0;
        }
        /* Mengatur posisi QR Code agar pas di tengah */
        .qr-container {
            display: flex;
            justify-content: center;
            margin: 15px 0;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>

<body>
    
    <h2>Mini Hospital</h2>
    <div class="garis"></div>
    
    <p style="margin-bottom: 5px; font-size: 16px;">Nomor Antrean:</p>
    <div class="nomor">
        <?php 
        $nomor = isset($_GET['nomor']) ? $_GET['nomor'] : '-';
        echo $nomor; 
        ?>
    </div>
    
    <!-- Tempat QR Code akan otomatis digambar -->
    <div class="qr-container" id="qrcode"></div>
    <p style="font-size: 12px; margin-top: 5px;">Scan barcode ini di loket pendaftaran</p>
    
    <div class="garis"></div>
    <p style="font-size: 12px;" id="waktu"></p>
    
    <button class="no-print" onclick="kembaliKeApm()" style="padding: 10px 20px; margin-top: 20px; cursor: pointer; font-size: 16px;">
        Kembali ke Beranda
    </button>

    <script>
        // 1. Tampilkan Waktu Cetak Real-Time
        document.getElementById("waktu").innerHTML = new Date().toLocaleString('id-ID');
        
        // 2. Buat Gambar QR Code
        let teksAntrean = "<?php echo $nomor; ?>";
        if (teksAntrean !== "-") {
            new QRCode(document.getElementById("qrcode"), {
                text: teksAntrean,
                width: 120, // Lebar QR Code
                height: 120, // Tinggi QR Code
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        }

        // 3. Fungsi Satu Pintu untuk Kembali ke APM
        let sudahPindah = false;
        function kembaliKeApm() {
            if (!sudahPindah) {
                sudahPindah = true;
                window.location.href = 'apm.php';
            }
        }

        // 4. Deteksi Perangkat (Desktop vs Android / Mobile)
        let isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

        if (isMobile) {
            // KHUSUS ANDROID:
            // Kita TIDAK memakai onafterprint karena langsung terpicu sebelum spooler selesai.
            // Sebagai gantinya, manfaatkan event blur & focus sistem operasi.
            let dialogCetakAktif = false;

            // Saat dialog cetak Android muncul -> browser kehilangan fokus (blur)
            window.addEventListener('blur', function() {
                dialogCetakAktif = true;
            });

            // Saat tombol Cetak / Batal ditekan dan layar kembali ke browser -> browser mendapat fokus (focus)
            window.addEventListener('focus', function() {
                if (dialogCetakAktif) {
                    setTimeout(kembaliKeApm, 500); // Beri jeda 0.5 detik agar mulus
                }
            });
        } else {
            // KHUSUS WINDOWS / PC:
            // onafterprint bawaan bekerja dengan sempurna di PC
            window.onafterprint = function() {
                kembaliKeApm();
            };
        }

        // 5. Picu Print Setelah QR Code Selesai Dimuat di Layar (Jeda 600ms)
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 600);
        });
    </script>

</body>
</html>