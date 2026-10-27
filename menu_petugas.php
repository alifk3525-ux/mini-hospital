<!DOCTYPE html>
<html>
<head>
    <title>Portal Petugas - Mini Hospital</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #e9ecef; margin: 0; padding: 40px; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { color: #2c3e50; margin: 0; font-size: 32px; }
        .header p { color: #6c757d; font-size: 16px; margin-top: 5px; }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            padding: 30px 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: 0.3s;
            border-top: 5px solid #3b82f6;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.15); }
        
        .card.antrean { border-color: #28a745; }
        .card.admin { border-color: #ffc107; }
        .card.stiker { border-color: #3b82f6; }
        .card.sep { border-color: #6c757d; background: #f8f9fa; cursor: not-allowed; }
        
        .icon { font-size: 40px; margin-bottom: 15px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .desc { font-size: 13px; color: #666; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Manajemen Operasional Terpadu</h1>
        <p>Pilih modul layanan yang ingin diakses</p>
    </div>

    <div class="grid-container">
        <!-- Modul 1: Pemanggilan Antrean -->
        <a href="dasbor_petugas.php" class="card antrean">
            <div class="icon">📢</div>
            <div class="title">Pemanggilan Antrean</div>
            <div class="desc">Akses dasbor kontrol loket (RJ, RI, G)</div>
        </a>

        <!-- Modul 2: Cetak Stiker (Fitur Barumu) -->
        <a href="cetak_stiker.php" class="card stiker">
            <div class="icon">🏷️</div>
            <div class="title">Cetak Stiker Pasien</div>
            <div class="desc">Print label rekam medis 80x30mm</div>
        </a>

        <!-- Modul 3: Manajemen Admin (Asumsi file sudah ada) -->
        <a href="admin.php" class="card admin">
            <div class="icon">⚙️</div>
            <div class="title">Manajemen Data</div>
            <div class="desc">Kelola jadwal dokter & kamar</div>
        </a>

        <!-- Modul 4: Cetak SEP (Coming Soon) -->
        <a href="#" class="card sep" onclick="alert('Fitur Cetak SEP sedang dalam pengembangan!'); return false;">
            <div class="icon">📄</div>
            <div class="title">Cetak SEP BPJS</div>
            <div class="desc">Segera Hadir</div>
        </a>
    </div>

</body>
</html>