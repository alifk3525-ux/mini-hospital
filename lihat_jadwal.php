<?php include 'koneksi.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Jadwal Dokter - Mini Hospital</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            text-align: center; 
            margin: 0; 
            padding: 40px; 
            background-color: #4B5320; /* Hijau Army */
            color: white; 
        }
        .judul { 
            color: #FFD700; 
            font-size: 35px; 
            font-weight: bold; 
            margin-bottom: 30px; 
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5); 
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            text-align: left;
        }
        /* Desain Header Per Poli */
        .poli-header {
            background-color: #FFD700;
            color: #333816;
            padding: 12px 20px;
            font-size: 22px;
            font-weight: bold;
            border-radius: 8px 8px 0 0;
            margin-top: 25px;
            border-left: 8px solid #333816;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: white; 
            color: black; 
            border-radius: 0 0 8px 8px; 
            overflow: hidden; 
            box-shadow: 0 6px 12px rgba(0,0,0,0.3); 
            margin-bottom: 20px;
        }
        th, td { 
            padding: 12px 15px; 
            border-bottom: 1px solid #ddd; 
            font-size: 16px; 
        }
        th { 
            background: #333816; 
            color: white; 
            text-align: left;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f1f1f1; }
        .btn-kembali { 
            display: inline-block; 
            padding: 12px 25px; 
            margin-top: 40px; 
            background: rgba(0,0,0,0.6); 
            color: white; 
            text-decoration: none; 
            font-size: 18px;
            border: 2px solid white; 
            border-radius: 8px; 
            transition: 0.3s;
        }
        .btn-kembali:hover { background: #FFD700; color: black; border-color: #FFD700; }
    </style>
</head>
<body>

    <div class="judul">JADWAL PRAKTIK DOKTER SPESIALIS</div>
    <div class="container">
        
        <?php
        // Ambil semua data dokter dan urutkan berdasarkan Nama Poli terlebih dahulu
        $query = mysqli_query($koneksi, "SELECT * FROM jadwal_dokter ORDER BY poli ASC, nama_dokter ASC");
        
        // Logika PHP untuk mengelompokkan data ke dalam Array berdasarkan Poli
        $jadwal_per_poli = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $jadwal_per_poli[$row['poli']][] = $row;
        }

        // Looping (Perulangan) untuk mencetak tabel per Poli
        foreach ($jadwal_per_poli as $nama_poli => $daftar_dokter) {
            echo "<div class='poli-header'>🏥 " . strtoupper($nama_poli) . "</div>";
            echo "<table>";
            echo "<tr><th width='40%'>Nama Dokter</th><th width='30%'>Hari Praktik</th><th width='30%'>Jam Praktik</th></tr>";
            
            foreach ($daftar_dokter as $dokter) {
                echo "<tr>";
                echo "<td style='font-weight: bold; color: #2c3e50;'>" . $dokter['nama_dokter'] . "</td>";
                echo "<td>" . $dokter['hari'] . "</td>";
                echo "<td>" . $dokter['jam_praktik'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        ?>

    </div>

    <a href="apm.php" class="btn-kembali">Kembali ke Beranda Utama</a>

</body>
</html>