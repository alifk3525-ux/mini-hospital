<!DOCTYPE html>
<html>
<head>
    <title>Mesin Antrean - Mini Hospital</title>
    <style>
        body {
            font-family: Arial, sans-serif; 
            text-align: center; 
            margin: 0;
            padding-top: 80px;
            background-color: #4B5320; 
        }
        .teks-welcome { font-size: 28px; color: white; margin-bottom: 5px; }
        .teks-kuning {
            color: #FFD700; font-size: 55px; font-weight: bold; margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5); 
        }
        .teks-hitam {
            color: #000000; font-size: 22px; font-weight: bold; display: block;
            margin-top: 10px; margin-bottom: 50px;
            text-shadow: 1px 1px 3px rgba(255,255,255,0.8); 
        }
        .btn-menu {
            display: inline-block; padding: 20px 40px; font-size: 22px; font-weight: bold;
            text-decoration: none; border-radius: 10px; margin: 10px;
            transition: 0.3s; box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            background: #FFD700; color: #000000; 
        }
        .btn-menu:hover { background: #e6c200; }
    </style>
</head>
<body>
    
    <div class="teks-welcome">Selamat Datang di</div>
    <div class="teks-kuning">Mini Hospital</div>
    <div class="teks-hitam">
        Institut Teknologi Sains dan Kesehatan<br>
        RS dr. SOEPRAOEN Kesdam V Brawijaya
    </div>
    
    <a href="pilih_antrean.php" class="btn-menu">
        AMBIL TIKET ANTREAN
    </a>
    
    <a href="lihat_jadwal.php" class="btn-menu" style="background: white; color: #4B5320;">
        INFORMASI JADWAL DOKTER
    </a>
    
    <a href="lihat_kamar.php" class="btn-menu" style="background: #0000FF; color: #FFFFFF;">
        KETERSEDIAAN KAMAR
    </a>

</body>
</html>