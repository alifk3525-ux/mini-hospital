<?php
session_start();

// Koneksi ke database
$path_koneksi = __DIR__ . '/../koneksi.php';
if (file_exists($path_koneksi)) {
    include $path_koneksi;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard K3RS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f8fafc; color: #0f172a; padding: 24px; }
        
        .welcome-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .welcome-card h2 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .welcome-card p { font-size: 13.5px; color: #64748b; margin-top: 4px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }
        .stat-label { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; }
        .stat-value { font-size: 28px; font-weight: 700; color: #2563eb; margin-top: 6px; }
    </style>
</head>
<body>

    <div class="welcome-card">
        <h2>📊 Dashboard Monitoring K3RS</h2>
        <p>Selamat datang di Modul Penelusuran Kematian Akibat Kerja.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Kasus Kematian</div>
            <div class="stat-value">12</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Dalam Penelusuran</div>
            <div class="stat-value">4</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Terverifikasi K3RS</div>
            <div class="stat-value">8</div>
        </div>
    </div>

</body>
</html>