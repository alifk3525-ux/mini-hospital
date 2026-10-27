<?php
session_start();

// Proteksi Sesi Login Modul
if (!isset($_SESSION['login_modul_k3rs']) && !isset($_SESSION['login_simrs'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Monitoring Kasus K3RS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { display: flex; height: 100vh; overflow: hidden; background-color: #f8fafc; }

        /* SIDEBAR NAVIGASI */
        .sidebar {
            width: 240px;
            background-color: #03256c;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-shrink: 0;
            z-index: 10;
        }
        .sidebar-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h3 { font-size: 16px; color: #ffffff; font-weight: 700; }
        .sidebar-header p { font-size: 11px; color: #93c5fd; margin-top: 2px; }

        .sidebar-menu { display: flex; flex-direction: column; padding-top: 15px; }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 24px;
            color: #93c5fd;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .sidebar-menu a:hover { background-color: #1e40af; color: #ffffff; }
        .sidebar-menu a.active {
            background-color: #1d4ed8;
            color: #ffffff;
            border-radius: 0 25px 25px 0;
            margin-right: 15px;
        }

        .sidebar-footer { padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { color: #fca5a5; text-decoration: none; font-size: 13.5px; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .sidebar-footer a:hover { color: #ef4444; }

        /* AREA KONTEN KANAN */
        .content-area { flex: 1; height: 100vh; position: relative; }
        iframe { width: 100%; height: 100%; border: none; display: block; }
    </style>
</head>
<body>

    <!-- SIDEBAR KIRI -->
    <div class="sidebar">
        <div>
            <div class="sidebar-header">
                <h3>🔬 Modul</h3>
                <p>Penelusuran Kematian</p>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard1.php" target="frame_k3rs" class="menu-item active">
                    📊 Dashboard
                </a>
                <a href="daftar_kasus.php" target="frame_k3rs" class="menu-item">
                    📋 Daftar Kasus
                </a>
                <a href="pengaturan.php" target="frame_k3rs" class="menu-item">
                    ⚙️ Pengaturan
                </a>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="../portal_petugas.php">
                ⬅️ Kembali ke Portal SIMRS
            </a>
        </div>
    </div>

    <!-- AREA KONTEN KANAN MEMANGGIL DASHBOARD.PHP -->
    <div class="content-area">
        <iframe name="frame_k3rs" src="dashboard.php"></iframe>
    </div>

    <script>
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>