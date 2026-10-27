<?php
session_start();
include '../koneksi.php';

// Memastikan user sudah login di SIMRS Utama
if (!isset($_SESSION['login_simrs'])) {
    header("Location: ../index.php");
    exit;
}

// Sinkronisasi data kematian dari SIMRS
$q_sync = mysqli_query($koneksi, "
    SELECT k.no_registrasi, k.no_rm 
    FROM kunjungan k 
    LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
    WHERE (k.status_kunjungan = 'Selesai (Meninggal)' 
           OR k.tujuan_layanan LIKE '%Jenazah%' 
           OR rm.status_keluar LIKE '%Meninggal%')
      AND k.no_registrasi NOT IN (SELECT no_registrasi FROM kasus_kematian_kerja)
");

while ($d_sync = mysqli_fetch_assoc($q_sync)) {
    $reg_sync = mysqli_real_escape_string($koneksi, $d_sync['no_registrasi']);
    $rm_sync  = mysqli_real_escape_string($koneksi, $d_sync['no_rm']);
    mysqli_query($koneksi, "INSERT INTO kasus_kematian_kerja (no_registrasi, no_rm) VALUES ('$reg_sync', '$rm_sync')");
}

// Ambil Ringkasan Statistik
$q_stat = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) AS total_kasus,
        SUM(IF(hasil_identifikasi_pekerjaan = 'Belum Dapat Ditentukan', 1, 0)) AS perlu_identifikasi,
        SUM(IF(hasil_identifikasi_pekerjaan = 'Terindikasi Terkait Pekerjaan', 1, 0)) AS terindikasi_kerja,
        SUM(IF(status_verifikasi = 'Diverifikasi', 1, 0)) AS diverifikasi
    FROM kasus_kematian_kerja
");
$stat = mysqli_fetch_assoc($q_stat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Ringkasan Kematian Pasien</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f8fafc; padding: 25px; margin: 0; color: #1e293b; }
        
        .hero-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white; padding: 25px 30px; border-radius: 12px; margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .hero-banner h2 { margin: 0 0 8px 0; font-size: 22px; color: #f8fafc; }
        .hero-banner p { margin: 0; color: #94a3b8; font-size: 13px; line-height: 1.5; }

        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .stat-title { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .stat-value { font-size: 28px; font-weight: bold; color: #0f172a; margin-top: 8px; }

        .launch-box {
            background: #eff6ff; border: 2px dashed #2563eb; padding: 30px;
            border-radius: 12px; text-align: center;
        }
        .launch-box h3 { margin: 0 0 10px 0; color: #1e40af; font-size: 20px; }
        .launch-box p { color: #3b82f6; font-size: 13px; margin-bottom: 20px; }
        
        .btn-launch {
            background: #2563eb; color: white; text-decoration: none; padding: 14px 28px;
            border-radius: 8px; font-weight: bold; font-size: 15px; display: inline-block;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); transition: all 0.2s ease;
        }
        .btn-launch:hover { background: #1d4ed8; transform: translateY(-2px); }
    </style>
</head>
<body>

    <div class="hero-banner">
        <h2>🏥 Ringkasan Manajemen Data Kematian Pasien</h2>
        <p>
            Modul pemantauan statistik kematian terintegrasi SIMRS. Berfungsi mengompilasi seluruh kasus kematian terdaftar di unit Rawat Inap, IGD, maupun Kamar Jenazah untuk mendukung proses penelusuran klinis dan koding rekam medis.
        </p>
    </div>

    <div class="stat-grid">
        <div class="stat-card" style="border-top: 4px solid #3b82f6;">
            <div class="stat-title">Total Pasien Meninggal</div>
            <div class="stat-value"><?php echo (int)$stat['total_kasus']; ?> <span style="font-size: 14px; color: #64748b;">Kasus</span></div>
        </div>
        <div class="stat-card" style="border-top: 4px solid #f59e0b;">
            <div class="stat-title">Perlu Identifikasi</div>
            <div class="stat-value"><?php echo (int)$stat['perlu_identifikasi']; ?> <span style="font-size: 14px; color: #64748b;">Kasus</span></div>
        </div>
        <div class="stat-card" style="border-top: 4px solid #ef4444;">
            <div class="stat-title">Terindikasi Akibat Kerja</div>
            <div class="stat-value"><?php echo (int)$stat['terindikasi_kerja']; ?> <span style="font-size: 14px; color: #64748b;">Kasus</span></div>
        </div>
        <div class="stat-card" style="border-top: 4px solid #10b981;">
            <div class="stat-title">Telah Diverifikasi</div>
            <div class="stat-value"><?php echo (int)$stat['diverifikasi']; ?> <span style="font-size: 14px; color: #64748b;">Kasus</span></div>
        </div>
    </div>

    <div class="launch-box">
        <h3>🔬 Modul Penelitian & Penelusuran Kematian</h3>
        <p>
            Masuk ke sistem penelusuran detail untuk mengidentifikasi keterkaitan pekerjaan, menyusun kronologi kejadian, menyusun Rangkaian Penyebab Kematian (Cause of Death Sequence), dan mengidentifikasi kandidat External Cause (ICD-10).
        </p>
        <!-- Membuka halaman login modul penelitian di tab/window baru penuh -->
        <a href="login.php" target="_blank" class="btn-launch">
            🚀 Buka Sistem Manajemen Identifikasi Kematian ➔
        </a>
    </div>

</body>
</html>