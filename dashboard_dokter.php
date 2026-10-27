<?php include 'koneksi.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Ruang Kerja Dokter</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, sans-serif; background: #f0f4f8; padding: 50px; text-align: center; }
        .login-box { background: white; padding: 40px; border-radius: 12px; max-width: 500px; margin: 0 auto; box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; margin-top: 0; border-bottom: 2px solid #3b82f6; padding-bottom: 15px; }
        select, button { width: 100%; padding: 15px; font-size: 16px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #cbd5e1; box-sizing: border-box; }
        button { background: #10b981; color: white; border: none; font-weight: bold; cursor: pointer; transition: 0.2s; }
        button:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>👨‍⚕️ Portal Pelayanan Medis</h2>
        <p style="color: #64748b; margin-bottom: 30px;">Silakan pilih identitas Anda untuk melihat antrean pasien hari ini.</p>
        
        <form action="ruang_periksa.php" method="GET">
            <select name="dpjp" required>
                <option value="">-- Pilih Nama Dokter --</option>
                <?php
                // Menarik daftar dokter yang sudah diinput admin
                $q_dok = mysqli_query($koneksi, "SELECT DISTINCT nama_dokter, poli FROM jadwal_dokter ORDER BY nama_dokter ASC");
                while($dok = mysqli_fetch_assoc($q_dok)) {
                    echo "<option value='".$dok['nama_dokter']."'>".$dok['nama_dokter']." (".$dok['poli'].")</option>";
                }
                ?>
            </select>
            <button type="submit">Masuk ke Ruang Periksa (CPPT) ➔</button>
        </form>
    </div>
</body>
</html>