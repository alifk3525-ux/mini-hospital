<!DOCTYPE html>
<html>
<head>
    <title>Pilih Jenis Layanan - Mini Hospital</title>
    <style>
        /* Latar Belakang Hijau Army */
        body {
            font-family: Arial, sans-serif; 
            text-align: center; 
            margin: 0;
            padding-top: 40px; 
            background-color: #4B5320; 
        }
        
        /* Teks Header Identitas */
        .teks-welcome { font-size: 22px; color: white; margin-bottom: 5px; }
        .teks-kuning {
            color: #FFD700; font-size: 45px; font-weight: bold; margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .teks-hitam {
            color: #000000; font-size: 18px; font-weight: bold; display: block;
            margin-top: 5px; margin-bottom: 30px;
            text-shadow: 1px 1px 3px rgba(255,255,255,0.8); 
        }
        
        /* Teks Instruksi */
        .teks-instruksi { color: white; font-size: 18px; margin-bottom: 20px; }

        /* Desain Tombol Layanan */
        .btn {
            display: inline-block; padding: 30px; font-size: 22px; font-weight: bold;
            color: white; text-decoration: none; border-radius: 15px; margin: 10px;
            width: 200px; text-align: center; box-shadow: 0 6px 12px rgba(0,0,0,0.4);
            transition: 0.2s;
        }
        .btn:active { transform: scale(0.95); } /* Efek tertekan saat diklik */
        
        /* Warna Masing-masing Tombol */
        .btn-rj { background-color: #28a745; } 
        .btn-ri { background-color: #ffc107; color: black; } 
        .btn-g  { background-color: #17a2b8; } 
        
        /* Desain Tombol Kembali */
        .btn-kembali {
            display: inline-block; padding: 10px 20px; font-size: 16px; 
            color: white; background-color: rgba(0,0,0,0.5); text-decoration: none;
            border-radius: 5px; margin-top: 40px; border: 1px solid white;
            transition: 0.3s;
        }
        .btn-kembali:hover { background-color: rgba(0,0,0,0.8); }
    </style>
</head>
<body>
    
    <!-- Bagian Identitas Kampus & Rumah Sakit -->
    <div class="teks-welcome">Layanan Antrean</div>
    <div class="teks-kuning">Mini Hospital</div>
    <div class="teks-hitam">
        Institut Teknologi Sains dan Kesehatan<br>
        RS dr. SOEPRAOEN Kesdam V Brawijaya
    </div>

    <div class="teks-instruksi">Silakan tekan layar sesuai tujuan Anda:</div>
    
    <!-- Tombol Pilihan Layanan -->
    <a href="proses_antrean.php?jenis=RJ" class="btn btn-rj">RAWAT JALAN<br><small style="font-weight: normal; font-size: 16px;">(Kode: RJ)</small></a>
    
    <a href="proses_antrean.php?jenis=RI" class="btn btn-ri">RAWAT INAP<br><small style="font-weight: normal; font-size: 16px;">(Kode: RI)</small></a>
    
    <a href="proses_antrean.php?jenis=G" class="btn btn-g">INFORMASI<br><small style="font-weight: normal; font-size: 16px;">(Kode: G)</small></a>

    <br>
    
    <!-- Tombol Kembali ke Awal -->
    <a href="apm.php" class="btn-kembali">Kembali ke Menu Utama</a>

</body>
</html>